<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

use yesbabylon\accounting\Invoice;
use yesbabylon\accounting\InvoiceLine;
use yesbabylon\accounting\InvoiceLineGroup;
use yesbabylon\service\Service;

list($params, $providers) = announce([
    'description'   => "Generate an invoice for a service.",
    'params'        => [
    ],
    'response'      => [
        'content-type'  => 'application/json',
        'charset'       => 'utf-8',
        'accept-origin' => '*'
    ],
    'providers'     => ['context', 'orm', 'auth']
]);


list($context, $orm, $auth) = [$providers['context'], $providers['orm'], $providers['auth']];

// Check if the next invoice laps (5days before real next invoice) is passed
$service_ids = $orm->search(Service::getType(), [["next_invoice", "<", strtotime('+5 days')]]);

$service;

foreach($service_ids as $service_id){
        $service = Service::id($service_id)
        ->read([
            'name',
            'is_recurring',
            'last_invoice',
            'next_invoice',
            'customer_id' => ['id', 'rate_class_id', 'lang_id' => ['code']],
            'service_lines_ids' => [
                'name',
                'product_id',
                'description',
                'price',
                'unit_price',
                'vat_rate',
                'qty',
                'total'
            ]
        ])
        ->first();

        if(!$service) {
            throw new Exception("unknown_service", QN_ERROR_UNKNOWN_OBJECT);
        }


        /*
            Check consistency
        */

        $errors = [];


        // still needed ??

        // check customer details completeness
        // $data = eQual::run('do', 'lodging_booking_check-customer', ['id' => $booking['id']]);
        // if(is_array($data) && count($data)) {
        //     // response array is not empty: missing customer details
        //     $errors[] = 'uncomplete_customer';
        // }

        // raise an exception with first error (alerts should have been issued in the check controllers)
        foreach($errors as $error) {
            throw new Exception($error, QN_ERROR_INVALID_PARAM);
        }

        /*
            Generate the invoice
        */

        // #todo - use settings for selecting the suitable payment terms

        // remember all booking lines involved
        $booking_lines_ids = [];

        // create invoice and invoice lines
        $invoice = Invoice::create([
                'date'              => time(),
                'service_id'        => $service_id,
                // allow to invoice to a "payer" partner distinct from customer
                'partner_id'        => (isset($params['partner_id']))?$params['partner_id']:$service['customer_id']['id']
            ])
            ->read(['id'])
            ->first();

        foreach($service['service_lines_ids'] as $lid => $line) {
            $service_lines_ids[] = $lid;
            // create line in several steps (not to overwrite final values from the line)
            InvoiceLine::create([
                    'invoice_id'                => $invoice['id'],
                    'service_id'                => $service_id,
                    'description'               => $line['description'],
                    'price'                     => $line['price']
                ])
                ->update([
                    'vat_rate'                  => $line['vat_rate'],
                    'unit_price'                => $line['unit_price'],
                    'qty'                       => $line['qty']
                ])
                ->update([
                    'total'                     => $line['total'],
                ])
                ->update([
                    'price'                     => $line['price'],
                ]);
        }

        $customer_lang = DEFAULT_LANG;
        if(isset($service['customer_id']['lang_id']['code'])) {
            $customer_lang = $service['customer_id']['lang_id']['code'];
        }
        // mark the booking as invoiced, whatever its status
        Service::id($service_id)->update(['last_invoice' => time(), 'next_invoice' => null]);
}
// read booking object





$context->httpResponse()
        ->status(204)
        ->send();