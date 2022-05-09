<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

use finance\accounting\InvoiceLine;
use finance\accounting\InvoiceLineGroup;
use lodging\sale\booking\Invoice;
use lodging\sale\booking\Booking;
use lodging\sale\booking\BookingLine;


list($params, $providers) = announce([
    'description'   => "Invoice due balance related to a booking.",
    'params'        => [
        'id' =>  [
            'description'   => 'Identifier of the booking for which the composition has to be generated.',
            'type'          => 'integer',
            'min'           => 1,
            'required'      => true
        ],
    ],
    'access' => [
        'visibility'        => 'public',		// 'public' (default) or 'private' (can be invoked by CLI only)		
        'groups'            => ['booking.default.user'],// list of groups ids or names granted 
    ],
    'response'      => [
        'content-type'  => 'application/json',
        'charset'       => 'utf-8',
        'accept-origin' => '*'
    ],
    'providers'     => ['context', 'orm', 'auth'] 
]);


list($context, $orm, $auth) = [$providers['context'], $providers['orm'], $providers['auth']];



// search for an invoice for this booking with status 'invoice' (there should be none)
$invoice = Invoice::search([['booking_id', '=', $params['id']], ['status', '=', 'invoice'], ['funding_id', '=', null]])->read(['id'])->first();
if($invoice) {
    throw new Exception("invoice_already_exists", QN_ERROR_NOT_ALLOWED);
}

// if a 'proforma' invoice exists, delete it
Invoice::search([['booking_id', '=', $params['id']], ['funding_id', '=', null]])->delete(true);



// read booking object
$booking = Booking::id($params['id'])
                  ->read([
                        'status',
                        'type',
                        'date_from',
                        'date_to',
                        'price',
                        'center_office_id' => ['id', 'organisation_id'],
                        'customer_id' => ['id', 'rate_class_id'],
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

if($booking['status'] != 'checkedout') {
    throw new Exception("incompatible_status", QN_ERROR_INVALID_PARAM);
}



/*
    Generate the invoice
*/

// #todo - setting for default payment terms

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


// mark all booking lines as invoiced
BookingLine::ids($booking_lines_ids)->update(['is_invoiced' => true]);

// Update booking status
Booking::id($params['id'])->update(['status' => 'balanced']);

// #todo - handle 'debit_balance', 'credit_balance', 'balanced'

$context->httpResponse()
        // ->status(204)
        ->status(200)
        ->body([])
        ->send();