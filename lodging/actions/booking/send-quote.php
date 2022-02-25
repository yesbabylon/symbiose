<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
if(!file_exists(QN_BASEDIR.'/vendor/swiftmailer/swiftmailer/lib/swift_required.php')) {
    throw new Exception("missing_dependency", QN_ERROR_INVALID_CONFIG);
}
require_once QN_BASEDIR.'/vendor/swiftmailer/swiftmailer/lib/swift_required.php';

use \Swift_SmtpTransport as Swift_SmtpTransport;
use \Swift_Message as Swift_Message;
use \Swift_Mailer as Swift_Mailer;
use \Swift_Attachment as Swift_Attachment;

use communication\TemplateAttachment;
use documents\Document;
use lodging\sale\booking\Booking;


// announce script and fetch parameters values
list($params, $providers) = announce([
    'description'	=>	"Send an instant email with given details with a booking quote as attachment.",
    'params' 		=>	[
        'booking_id' => [
            'description'   => 'Identifier of the booking related to the sending of the email.',
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
            'usage'         => 'email',
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
            'description'   => 'Email address TO.',
            'type'          => 'array',
            'default'       => []
        ],
        'lang' =>  [
            'description'   => 'Language to use for multilang contents.',
            'type'          => 'string',
            'usage'         => 'language/iso-639',
            'default'       => DEFAULT_LANG
        ]
    ],
    'access' => [
        'visibility'        => 'public',
        'users'             => [ROOT_USER_ID],
        'groups'            => ['sales.bookings.users'],
    ],
    'response'      => [
        'content-type'      => 'application/json',
        'charset'           => 'utf-8',
        'accept-origin'     => '*'
    ],
    'providers'     => ['context', 'orm', 'auth', 'access']
]);


// init local vars with inputs
list($om, $context, $auth, $access) = [ $providers['orm'], $providers['context'], $providers['auth'], $providers['access'] ];


$booking = Booking::id($params['booking_id'])->read(['center_id'])->first();

if(!$booking) {
    throw new Exception("unknown_booking", QN_ERROR_UNKNOWN_OBJECT);
}


// generate attachment
$result = run('get', 'lodging_booking_print-booking', [
    'id'        => $params['booking_id'],
    'view_id'   =>'print.default',
    'lang'      => $params['lang']
]);

$data = json_decode($result, true);
if($data != null && isset($data['errors'])) {
    // raise an exception with returned error code
    foreach($data['errors'] as $name => $message) {
        throw new Exception($message, qn_error_code($name));
    }
}

// #todo - store these terms in i18n
$main_attachment_name = 'quote';
switch(substr($params['lang'], 0, 2)) {
    case 'fr': $main_attachment_name = 'devis';
        break;
    case 'nl': $main_attachment_name = 'offerte';
        break;
    case 'en': $main_attachment_name = 'quote';
        break;
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
catch(Exception $e) {}

$params['message'] .= $signature;


// #todo - add attachments that are specific to quote.mail => $params['attachments_ids']

$params['attachments_ids'] = array_unique($params['attachments_ids']);

$attachments = [];

// push main attachment
$attachments[] = new Swift_Attachment($result, $main_attachment_name.'.pdf', 'application/pdf');

if(count($params['attachments_ids'])) {
    $template_attachments = TemplateAttachment::ids($params['attachments_ids'])->read(['name', 'document_id'])->get();
    foreach($template_attachments as $tid => $tdata) {
        $document = Document::id($tdata['document_id'])->read(['name', 'data', 'type'])->first();
        $attachments[] = new Swift_Attachment($document['data'], $document['name'], $document['type']);
    }
}

// send message
$transport = new Swift_SmtpTransport(EMAIL_SMTP_HOST, EMAIL_SMTP_PORT /*, 'ssl'*/);

$transport->setUsername(EMAIL_SMTP_ACCOUNT_USERNAME)
          ->setPassword(EMAIL_SMTP_ACCOUNT_PASSWORD);

$message = new Swift_Message();
$message->setTo($params['recipient_email'])
        ->setSubject($params['title'])
        ->setContentType("text/html")
        ->setBody($params['message'])
        ->setFrom([$params['sender_email'] => EMAIL_SMTP_ACCOUNT_DISPLAYNAME]);

foreach($attachments as $attachment) {
    $message->attach($attachment);
}

$mailer = new Swift_Mailer($transport);
$result = $mailer->send($message);

$context->httpResponse()
        ->body([])
        ->send();