<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
use equal\email\Email;
use equal\email\EmailAttachment;

use communication\TemplateAttachment;
use documents\Document;
use lodging\sale\booking\Booking;
use lodging\sale\booking\Contract;
use core\setting\Setting;
use core\Mail;
use core\Lang;

// announce script and fetch parameters values
list($params, $providers) = announce([
    'description'	=>	"Send an instant email with given details with a booking contract as attachment.",
    'params' 		=>	[
        'booking_id' => [
            'description'   => 'Booking related to the sending of the email.',
            'type'          => 'integer',
            'required'      => true
        ],
        'title' =>  [
            'description'   => 'Title of the message.',
            'type'          => 'string',
            'required'      => true
        ],
        'message' => [
            'description'   => 'Body of the message.',
            'type'          => 'string',
            'required'      => true
        ],
        'sender_email' => [
            'description'   => 'Email address FROM.',
            'type'          => 'string',
            'usage'         => 'email',
            'required'      => true
        ],
        'recipient_email' => [
            'description'   => 'Email address TO.',
            'type'          => 'string',
            'usage'         => 'email',
            'required'      => true
        ],
        'attachments_ids' => [
            'description'   => 'List of identitiers of attachments to join.',
            'type'          => 'array',
            'default'       => []
        ],
        'mode' =>  [
            'description'   => 'Mode in which document has to be rendered: simple or detailed.',
            'type'          => 'string',
            'selection'     => ['simple', 'grouped', 'detailed'],
            'default'       => 'grouped'
        ],
        'lang' =>  [
            'description'   => 'Language for multilang contents (2 letters ISO 639-1).',
            'type'          => 'string',
            'default'       => DEFAULT_LANG
        ]
    ],
    'access' => [
        'groups'            => ['booking.default.user'],
    ],
    'response'      => [
        'content-type'      => 'application/json',
        'charset'           => 'utf-8',
        'accept-origin'     => '*'
    ],
    'providers'     => ['context']
]);


// initalise local vars with inputs
list($context) = [ $providers['context'] ];

$booking = Booking::id($params['booking_id'])->read(['center_id', 'has_contract', 'contracts_ids'])->first();

if(!$booking) {
    throw new Exception("unknown_booking", QN_ERROR_UNKNOWN_OBJECT);
}

if(!$booking['has_contract'] || empty($booking['contracts_ids'])) {
    throw new Exception("incompatible_status", QN_ERROR_INVALID_PARAM);
}

// by convention the most recent contract is listed first (see schema in lodging/classes/sale/booking/Booking.class.php)
$contract_id = array_shift($booking['contracts_ids']);

// generate attachment
$attachment = eQual::run('get', 'lodging_booking_print-contract', [
    'id'        => $contract_id ,
    'view_id'   =>'print.default',
    'lang'      => $params['lang'],
    'mode'      => $params['mode']
]);

// get 'contract' term transaltion
$main_attachment_name = Lang::get_term('sale', 'contract', 'contract', $params['lang']);

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

$params['message'] .= $signature;

/** @var EmailAttachment[] */
$attachments = [];

// push main attachment
$attachments[] = new EmailAttachment($main_attachment_name.'.pdf', (string) $attachment, 'application/pdf');

// add attachments whose ids have been received as param ($params['attachments_ids'])
if(count($params['attachments_ids'])) {
    $params['attachments_ids'] = array_unique($params['attachments_ids']);
    $template_attachments = TemplateAttachment::ids($params['attachments_ids'])->read(['name', 'document_id'])->get();
    foreach($template_attachments as $tid => $tdata) {
        $document = Document::id($tdata['document_id'])->read(['name', 'data', 'type'])->first();
        if($document) {
            $attachments[] = new EmailAttachment($document['name'], $document['data'], $document['type']);
        }
    }
}

// create message
$message = new Email();
$message->setTo($params['recipient_email'])
        ->setSubject($params['title'])
        ->setContentType("text/html")
        ->setBody(str_replace(['<br>', '<p></p>'], '', $params['message']));

foreach($attachments as $attachment) {
    $message->addAttachment($attachment);
}

// queue message
Mail::queue($message, 'lodging\sale\booking\Booking', $params['booking_id']);

/*
    Update contract status (mark as sent, if not already)
*/

$collection = Contract::id($contract_id)->read(['status']);
$contract = $collection->first();
if($contract['status'] == 'pending') {
    $collection->update(['status' => 'sent']);
}

$context->httpResponse()
        ->status(204)
        ->send();