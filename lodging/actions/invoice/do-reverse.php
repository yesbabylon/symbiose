<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
use lodging\sale\booking\Funding;
use lodging\sale\booking\Invoice;
use lodging\sale\booking\InvoiceLine;
use lodging\sale\booking\InvoiceLineGroup;
use lodging\sale\booking\Booking;

list($params, $providers) = announce([
    'description'   => "Reverse an invoice by creating a credit note (only invoices -not credit notes- can be reversed).",
    'params'        => [
        'id' =>  [
            'description'   => 'Identifier of the invoice to reverse.',
            'type'          => 'integer',
            'min'           => 1,
            'required'      => true
        ],
    ],
    'access' => [
        'visibility'        => 'protected',
        'groups'            => ['finance.default.user', 'booking.default.user'],
    ],
    'response'      => [
        'content-type'  => 'application/json',
        'charset'       => 'utf-8',
        'accept-origin' => '*'
    ],
    'providers'     => ['context', 'orm', 'auth']
]);

/**
 * @var \equal\php\Context                  $context
 * @var \equal\orm\ObjectManager            $orm
 * @var \equal\auth\AuthenticationManager   $auth
 */
list($context, $orm, $auth) = [$providers['context'], $providers['orm'], $providers['auth']];

// emit the invoice : changing status will trigger an invoice number assignation
$invoice = Invoice::id($params['id'])
    ->read([
        'booking_id',
        'funding_id',
        'center_office_id',
        'organisation_id',
        'partner_id',
        'is_paid',
        'invoice_line_groups_ids' => [
            'invoice_lines_ids' => [
                'product_id',
                'price_id',
                'vat_rate',
                'unit_price',
                'qty',
                'free_qty',
                'discount'
            ]
        ]
    ])
    ->first();

if(!$invoice) {
    throw new Exception("unknown_invoice", QN_ERROR_UNKNOWN_OBJECT);
}

/* 
    1) create an identical proforma invoice, but of type 'credit_note'
*/

// create invoice 
$reversed_invoice = Invoice::create([
        'type'              => 'credit_note',
        'status'            => 'proforma',        
        'date'              => time(),
        'booking_id'        => $invoice['booking_id'],
        'center_office_id'  => $invoice['center_office_id'],
        'organisation_id'   => $invoice['organisation_id'],
        'partner_id'        => $invoice['partner_id']
    ])->first();

// create groups and lines
foreach($invoice['invoice_line_groups_ids'] as $gid => $group) {

    $reversed_group = InvoiceLineGroup::create([
        'name'              => $group['name'],
        'invoice_id'        => $reversed_invoice['id']
    ])->first();

    // create group
    foreach($group['invoice_lines_ids'] as $lid => $line) {
        $reversed_line = [
            'invoice_id'                => $reversed_invoice['id'],
            'invoice_line_group_id'     => $reversed_group['id'],
            'product_id'                => $line['product_id'],
            'price_id'                  => $line['price_id'],
            'vat_rate'                  => $line['vat_rate'],
            'unit_price'                => $line['unit_price'],
            'qty'                       => $line['qty'],
            'free_qty'                  => $line['free_qty'],
            'discount'                  => $line['discount']
        ];
        InvoiceLine::create($reversed_line);
    }
}

/* 
    2) update credit note's status to 'invoice'
*/

if(!$invoice['is_paid']) {
    // if invoice hadn't been paid, mark credit note as paid (nothing to do)
    Invoice::id($reversed_invoice['id'])->update(['is_paid' => true]);
}
else {
    // nothing to do here: accounting service will have to do the reimbursement
} 

// remove funding associated to the invoice, if any
if(!is_null($invoice['funding_id'])) { 
    Funding::id($invoice['funding_id'])->delete(true);
}

// mark original invoice as paid and cancelled (reversed)
Invoice::id($params['id'])->update(['status' => 'cancelled', 'is_paid' => true, 'reversed_invoice_id' => $reversed_invoice['id']]);

// update invoice status (this will trigger the creation of the accounting entries)
Invoice::id($reversed_invoice['id'])->update(['status' => 'invoice']);


$context->httpResponse()
        ->status(204)
        ->send();