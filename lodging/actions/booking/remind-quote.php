<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
use equal\email\Email;
use equal\email\EmailAttachment;

use communication\Template;
use lodging\sale\booking\Booking;
use core\setting\Setting;
use core\Mail;

// announce script and fetch parameters values
list($params, $providers) = announce([
    'description'	=>	"Send an instant email with given details with a booking quote as attachment.",
    'params' 		=>	[
        'id' => [
            'description'   => 'Identifier of the booking related to the sending of the email.',
            'type'          => 'integer',
            'required'      => true
        ],
        'lang' =>  [
            'description'   => 'Language to use for multilang contents.',
            'type'          => 'string',
            'usage'         => 'language/iso-639',
            'default'       => constant('DEFAULT_LANG')
        ]
    ],
    'constants'             => ['DEFAULT_LANG'],
    'access' => [
        'groups'            => ['booking.default.user'],
    ],
    'response' => [
        'content-type'      => 'application/json',
        'charset'           => 'utf-8',
        'accept-origin'     => '*'
    ],
    'providers' => ['context', 'cron']
]);


// init local vars with inputs
list($context, $cron) = [ $providers['context'], $providers['cron'] ];

$booking = Booking::id($params['booking_id'])->read(['center_id'])->first();

if(!$booking) {
    throw new Exception("unknown_booking", QN_ERROR_UNKNOWN_OBJECT);
}


// retrieve boody and title

$title = '';
$body = '';

$template = Template::search([
                        ['category_id', '=', $booking['center_id']['template_category_id']],
                        ['code', '=', 'expired'],
                        ['type', '=', 'quote']
                    ])
                    ->read(['parts_ids' => ['name', 'value']], $params['lang'])
                    ->first();

foreach($template['parts_ids'] as $part_id => $part) {
    if($part['name'] == 'header') {
        $title = $part['value'];
    }
    else if($part['name'] == 'body') {
        $body = $part['value'];
    }
}


// generate signature
$signature = '';
try {
    $data = eQual::run('get', 'lodging_identity_center-signature', [
        'center_id'     => $booking['center_id'],
        'lang'          => $params['lang']
    ]);
    $signature = (isset($data['signature']))?$data['signature']:'';
}
catch(Exception $e) {
    // ignore errors
}

$body .= $signature;

// create message
$message = new Email();
$message->setTo($params['recipient_email'])
        ->setSubject($title)
        ->setContentType("text/html")
        ->setBody($body);

// queue message
Mail::queue($message, 'lodging\sale\booking\Booking', $params['id']);

$context->httpResponse()
        ->status(204)
        ->send();