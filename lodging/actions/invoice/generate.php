<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
use core\setting\Setting;
use core\Lang;
use lodging\sale\booking\Invoice;
use lodging\sale\booking\InvoiceLine;
use lodging\sale\booking\InvoiceLineGroup;
use lodging\sale\booking\Booking;
use lodging\sale\booking\Funding;
use lodging\sale\catalog\Product;

list($params, $providers) = announce([
    'description'   => "Generate the proforma final invoice of a booking with remaining due balance.",
    'params'        => [
        'id' =>  [
            'description'   => 'Identifier of the booking for which the invoice has to be generated.',
            'type'          => 'integer',
            'min'           => 1,
            'required'      => true
        ],
        'partner_id' =>  [
            'description'   => 'Identifier of the partner to which the invoice must be addressed, if not set defaults to customer_id.',
            'type'          => 'integer'
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
                            'price_id',
                            'vat_rate',
                            'unit_price',
                            'qty',
                            'nb_nights',
                            'nb_pers',
                            'booking_lines_ids' => [
                                'product_id',
                                'description',
                                'price_id',
                                'unit_price',
                                'vat_rate',
                                'qty',
                                'free_qty',
                                'discount'
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
    // response array is not empty: missing customer details
    $errors[] = 'uncomplete_customer';
}

// raise an exception with first error (alerts should have been issued in the check controllers)
foreach($errors as $error) {
    throw new Exception($error, QN_ERROR_INVALID_PARAM);
}


// if a 'proforma' balance invoice exists, delete it
Invoice::search([['booking_id', '=', $params['id']], ['funding_id', '=', null]])->delete(true);


/*
    Generate the invoice
*/

// #todo - use settings for selecting the suitable payment terms

// remember all booking lines involved
$booking_lines_ids = [];

// create invoice and invoice lines
$invoice = Invoice::create([
        'date'              => time(),
        'organisation_id'   => $booking['center_office_id']['organisation_id'],
        'booking_id'        => $params['id'],
        'center_office_id'  => $booking['center_office_id']['id'],
        'status'            => 'proforma',
        // allow to invoice to a "payer" partner distinct from customer
        'partner_id'        => (isset($params['partner_id']))?$params['partner_id']:$booking['customer_id']['id']
    ])
    ->read(['id'])
    ->first();

// append invoice lines base on booking lines
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
    ])
    ->read(['id'])
    ->first();


    if($group['has_pack'] && $group['is_locked'] ) {
        // invoice group with a single line

        // create a line based on the booking Line Group
        $i_line = [
            'invoice_id'                => $invoice['id'],
            'invoice_line_group_id'     => $invoice_line_group['id'],
            'product_id'                => $group['pack_id']['id'],
            'price_id'                  => $group['price_id'],
            'vat_rate'                  => $group['vat_rate'],
            'unit_price'                => $group['unit_price'],
            'qty'                       => $group['qty']
        ];

        InvoiceLine::create($i_line);
    }
    else {
        // create as many lines as the group booking_lines
        foreach($group['booking_lines_ids'] as $lid => $line) {
            $booking_lines_ids[] = $lid;

            $i_line = [
                'invoice_id'                => $invoice['id'],
                'invoice_line_group_id'     => $invoice_line_group['id'],
                'product_id'                => $line['product_id'],
                'description'               => $line['description'],
                'price_id'                  => $line['price_id'],
                'vat_rate'                  => $line['vat_rate'],
                'unit_price'                => $line['unit_price'],
                'qty'                       => $line['qty'],
                'free_qty'                  => $line['free_qty'],
                'discount'                  => $line['discount']
            ];

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

    $invoice_label = Lang::get_term('sale', 'invoice', 'invoice', $customer_lang);
    $installment_label = Lang::get_term('sale', 'installment', 'downpayment', $customer_lang);

    foreach($fundings as $fid => $funding) {
        if($funding['type'] == 'installment') {
            if($funding['paid_amount'] == 0) {
                // remove non-invoice non-paid fundings
                Funding::id($fid)->delete(true);
                continue;
            }
            $i_line = [
                'invoice_id'                => $invoice['id'],
                'description'               => ucfirst($installment_label).' '.date('Y-m'),
                'product_id'                => $downpayment_product_id,
                'vat_rate'                  => 0.0,
                // by convention, price is always positive value (so that price, credit and debit remain positive at all time)
                'unit_price'                => $funding['paid_amount'],
                // and quantity is set as negative value when something is deducted
                'qty'                       => -1
                // #memo - we don't assign a price : downpayments will be identified as such and use a specific accounting rule
            ];
            $new_line = InvoiceLine::create($i_line)->read(['id'])->first();
            $i_lines_ids[] = $new_line['id'];
        }
        else if($funding['type'] == 'invoice') {
            $funding_invoice = Invoice::id($funding['invoice_id'])->read(['id', 'created', 'name', 'partner_id', 'total', 'invoice_lines_ids' => ['vat_rate', 'product_id', 'total']])->first();

            if(!$funding_invoice) {
                // inconsistency detected
                trigger_error("QN_DEBUG_APP::unable to fetch lodging\sale\booking\Invoice[{$funding['invoice_id']}]", QN_REPORT_WARNING);
                continue;
            }

            // payer and customer are the same
            if($funding_invoice['partner_id'] == $booking['customer_id']['id']) {
                // there should be only one line
                foreach($funding_invoice['invoice_lines_ids'] as $lid => $line) {
                    $i_line = [
                        'invoice_id'                => $invoice['id'],
                        'name'                      => $installment_label.' '.$funding_invoice['name'],
                        // product should be the downpayment product
                        'product_id'                => $line['product_id'],
                        // vat_rate depends on the organisation : VAT is due with arbitrary amount (default VAT rate applied)
                        'vat_rate'                  => $line['vat_rate'],
                        // by convention, price is always positive value (so that price, credit and debit remain positive at all time)
                        'unit_price'                => $line['total'],
                        // and quantity is set as negative value when something is deducted
                        'qty'                       => -1
                        // #memo - we don't assign a price : downpayments will be identified as such and use a specific accounting rule
                    ];
                    $new_line = InvoiceLine::create($i_line)->read(['id'])->first();
                    $i_lines_ids[] = $new_line['id'];
                }
            }
            // payer and customer are distincts
            else {
                // consider the invoice as a paid downpayment
                $i_line = [
                    'invoice_id'                => $invoice['id'],
                    'description'               => ucfirst($installment_label).' '.date('Y-m', $funding_invoice['created']),
                    'product_id'                => $downpayment_product_id,
                    'vat_rate'                  => 0.0,
                    // by convention, price is always positive value (so that price, credit and debit remain positive at all time)
                    'unit_price'                => $funding_invoice['total'],
                    // and quantity is set as negative value when something is deducted
                    'qty'                       => -1
                    // #memo - we don't assign a price : downpayments will be identified as such and use a specific accounting rule
                ];
                $new_line = InvoiceLine::create($i_line)->read(['id'])->first();
                $i_lines_ids[] = $new_line['id'];
            }
        }
    }

    // get the group name according to requested lang
    $group_label = ucfirst(Lang::get_term('sale', 'downpayments', 'downpayments', $customer_lang));

    InvoiceLineGroup::create([
        'name'              => $group_label,
        'invoice_id'        => $invoice['id'],
        'invoice_lines_ids' => $i_lines_ids
    ]);
}

// mark the booking as invoiced, whatever its status
Booking::id($params['id'])->update(['is_invoiced' => true]);

$context->httpResponse()
        ->status(204)
        ->send();