<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
use core\setting\Setting;
use finance\accounting\InvoiceLine;
use finance\accounting\InvoiceLineGroup;
use lodging\sale\booking\Invoice;
use lodging\sale\booking\Booking;
use lodging\sale\booking\BookingLine;
use lodging\sale\booking\Funding;
use lodging\sale\catalog\Product;

list($params, $providers) = announce([
    'description'   => "Generate the final invoice of a booking with remaining due balance.",
    'params'        => [
        'id' =>  [
            'description'   => 'Identifier of the booking for which the invoice has to be generated.',
            'type'          => 'integer',
            'min'           => 1,
            'required'      => true
        ]
    ],
    'access' => [
        'visibility'        => 'protected',
        'groups'            => ['booking.default.user']
    ],
    'response'      => [
        'content-type'  => 'application/json',
        'charset'       => 'utf-8',
        'accept-origin' => '*'
    ],
    'providers'     => ['context', 'orm', 'auth']
]);


list($context, $orm, $auth) = [$providers['context'], $providers['orm'], $providers['auth']];


// #memo - final invoice :
// - is standalone (never relates to a funding)
// - is released after checkout (unless payment plan holds a balance invoice)


// search for an invoice for this booking with status 'invoice' (there should be none)
$invoice = Invoice::search([['booking_id', '=', $params['id']], ['status', '=', 'invoice'], ['funding_id', '=', null]])->read(['id'])->first();
if($invoice) {
    throw new Exception("invoice_already_exists", QN_ERROR_NOT_ALLOWED);
}

// read booking object
$booking = Booking::id($params['id'])
                  ->read([
                        'status',
                        'type',
                        'date_from',
                        'date_to',
                        'price',
                        'center_office_id' => ['id', 'organisation_id'],
                        'customer_id' => ['id', 'rate_class_id', 'lang_id' => ['code']],
                        'booking_lines_groups_ids' => [
                            'name',
                            'date_from',
                            'date_to',
                            'has_pack',
                            'is_locked',
                            'pack_id' => ['id', 'display_name'],
                            'vat_rate',
                            'unit_price',
                            'qty',
                            'nb_nights',
                            'nb_pers',
                            'booking_lines_ids' => [
                                'product_id',
                                'unit_price',
                                'vat_rate',
                                'qty',
                                'price_adapters_ids' => ['type', 'value', 'is_manual_discount']
                            ]
                        ]
                  ])
                  ->first();

if(!$booking) {
    throw new Exception("unknown_booking", QN_ERROR_UNKNOWN_OBJECT);
}

if(!in_array($booking['status'], ['confirmed', 'checkedout'])) {
    throw new Exception("incompatible_status", QN_ERROR_INVALID_PARAM);
}

/*
    Check consistency
*/

$errors = [];

// check customer details completeness
$data = eQual::run('do', 'lodging_booking_check-customer', ['id' => $booking['id']]);
if(is_array($data) && count($data)) {
    $errors[] = 'uncomplete_customer';
}

// raise an exception with first error (alerts should have been issued in the check controllers)
foreach($errors as $error) {
    throw new Exception($error, QN_ERROR_INVALID_PARAM);
}


// if a 'proforma' invoice exists, delete it
Invoice::search([['booking_id', '=', $params['id']], ['funding_id', '=', null]])->delete(true);


/*
    Generate the invoice
*/

// #todo - setting for suitable payment terms

// remember all booking lines involved
$booking_lines_ids = [];

// create invoice and invoice lines
$invoice = Invoice::create([
        'date'              => time(),
        'organisation_id'   => $booking['center_office_id']['organisation_id'],
        'booking_id'        => $params['id'],
        'center_office_id'  => $booking['center_office_id']['id'],
        'status'            => 'proforma',
        'partner_id'        => $booking['customer_id']['id']
    ])
    ->first();


foreach($booking['booking_lines_groups_ids'] as $group_id => $group) {
    $group_label = $group['name'].' : ';

    if($group['date_from'] == $group['date_to']) {
        $group_label .= date('d/m/y', $group['date_from']);
    }
    else {
        $group_label .= date('d/m/y', $group['date_from']).' - '.date('d/m/y', $group['date_to']);
    }

    $group_label .= ' - '.$group['nb_pers'].' p.';

    $invoice_line_group = InvoiceLineGroup::create([
        'name'              => $group_label,
        'invoice_id'        => $invoice['id']
    ])->first();


    if($group['has_pack'] && $group['is_locked'] ) {
        // invoice group with a single line

        // create a line based on the booking Line Group
        $i_line = [
            'invoice_id'                => $invoice['id'],
            'invoice_line_group_id'     => $invoice_line_group['id'],
            'product_id'                => $group['pack_id']['id'],
            'vat_rate'                  => $group['vat_rate'],
            'unit_price'                => $group['unit_price'],
            'qty'                       => $group['qty']
        ];

        $contract_line = InvoiceLine::create($i_line)->first();
    }
    else {
        // create as many lines as the group booking_lines
        foreach($group['booking_lines_ids'] as $lid => $line) {
            $booking_lines_ids[] = $lid;

            $i_line = [
                'invoice_id'                => $invoice['id'],
                'invoice_line_group_id'     => $invoice_line_group['id'],
                'product_id'                => $line['product_id'],
                'vat_rate'                  => $line['vat_rate'],
                'unit_price'                => $line['unit_price'],
                'qty'                       => $line['qty']
            ];

            $disc_value = 0;
            $disc_percent = 0;
            $free_qty = 0;
            foreach($line['price_adapters_ids'] as $aid => $adata) {
                if($adata['is_manual_discount']) {
                    if($adata['type'] == 'amount') {
                        $disc_value += $adata['value'];
                    }
                    else if($adata['type'] == 'percent') {
                        $disc_percent += $adata['value'];
                    }
                    else if($adata['type'] == 'freebie') {
                        $free_qty += $adata['value'];
                    }
                }
                // auto granted freebies are displayed as manual discounts
                else {
                    if($adata['type'] == 'freebie') {
                        $free_qty += $adata['value'];
                    }
                }
            }
            // convert discount value to a percentage
            $disc_value = $disc_value / (1 + $line['vat_rate']);
            $price = $line['unit_price'] * $line['qty'];
            $disc_value_perc = ($price) ? ($price - $disc_value) / $price : 0;
            $disc_percent += (1-$disc_value_perc);

            $i_line['free_qty'] = $free_qty;
            $i_line['discount'] = $disc_percent;
            InvoiceLine::create($i_line);
        }

    }

}

$customer_lang = DEFAULT_LANG;
if(isset($booking['customer_id']['lang_id']['code'])) {
    $customer_lang = $booking['customer_id']['lang_id']['code'];
}

/*
    Add lines relating to fundings, if any (paid installments and invoice downpayments)
*/

// find all fundings of given booking
$fundings = Funding::search(['booking_id', '=', $params['id']])->read(['type', 'due_amount', 'paid_amount', 'invoice_id'])->get();

if($fundings) {

    // retrieve downpayment product
    $downpayment_product_id = 0;

    $downpayment_sku = Setting::get_value('sale', 'invoice', 'downpayment.sku.'.$booking['center_office_id']['organisation_id']);
    if($downpayment_sku) {
        $product = Product::search(['sku', '=', $downpayment_sku])->read(['id'])->first();
        if($product) {
            $downpayment_product_id = $product['id'];
        }
    }

    $i_lines_ids = [];
    $invoice_label = Setting::get_value('sale', 'locale', 'terms.invoice', 'Invoice', 0, $customer_lang);

    foreach($fundings as $fid => $funding) {
        if($funding['type'] == 'installment') {
            if($funding['paid_amount'] == 0) {
                // ignore non-invoice non-paid fundings
                continue;
            }
            $i_line = [
                'invoice_id'                => $invoice['id'],
                'product_id'                => $downpayment_product_id,
                'vat_rate'                  => 0.0,
                // by convention, price is always positive value (so that price, credit and debit remain positive at all time)                
                'unit_price'                => $funding['paid_amount'],
                // and quantity is set as negative value when something is deducted 
                'qty'                       => -1
            ];
            $new_line = InvoiceLine::create($i_line)->first();
            $i_lines_ids[] = $new_line['id'];
        }
        else if($funding['type'] == 'invoice') {
            $funding_invoice = Invoice::id($funding['invoice_id'])->read(['id', 'name', 'invoice_lines_ids' => ['vat_rate', 'product_id', 'total']])->first();
            
            if($funding_invoice) {
                // there should be only one line
                foreach($funding_invoice['invoice_lines_ids'] as $lid => $line) {
                    $i_line = [
                        'invoice_id'                => $invoice['id'],
                        'name'                      => $invoice_label.' '.$funding_invoice['name'],
                        // product should be the downpayment product
                        'product_id'                => $line['product_id'],
                        // vat_rate depends on the organisation : VAT is due with arbitrary amount (default VAT rate applied)
                        'vat_rate'                  => 0.0,
                        'unit_price'                => $line['total'],
                        'qty'                       => -1
                    ];
                    $new_line = InvoiceLine::create($i_line)->first();
                    $i_lines_ids[] = $new_line['id'];
                }
            }
        }
    }

    // get the group name according to requested language
    $group_label = ucfirst(Setting::get_value('sale', 'locale', 'terms.downpayments', 'Downpayments', 0, $customer_lang));

    $invoice_line_group = InvoiceLineGroup::create([
        'name'              => $group_label,
        'invoice_id'        => $invoice['id'],
        'invoice_lines_ids' => $i_lines_ids
    ])->first();
}

// mark the booking as invoiced, whatever its status
Booking::id($params['id'])->update(['is_invoiced' => true]);

$context->httpResponse()
        ->status(204)
        ->send();