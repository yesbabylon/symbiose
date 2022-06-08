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
    'description'   => "Generate final invoice with remaining due balance related to a booking.",
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


// read booking object
$booking = Booking::id($params['id'])
                  ->read(['status','booking_lines_ids'])
                  ->first();

if(!$booking) {
    throw new Exception("unknown_booking", QN_ERROR_UNKNOWN_OBJECT);
}

if($booking['status'] != 'checkedout') {
    throw new Exception("incompatible_status", QN_ERROR_INVALID_PARAM);
}

// generate balance invoice (proforma) (raise exception on failure)
eQual::run('do', 'lodging_invoice_generate', ['id' => $params['id']]);

// mark all booking lines as invoiced
BookingLine::ids($booking_lines_ids)->update(['is_invoiced' => true]);

// update booking status
Booking::id($params['id'])->update(['status' => 'invoiced']);

$context->httpResponse()
        ->status(204)
        ->send();