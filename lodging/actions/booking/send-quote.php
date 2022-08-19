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
use core\setting\Setting;
use core\Lang;

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
        'documents_ids' => [
            'description'   => 'List of identitiers of documents to join.',
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
            'description'   => 'Language to use for multilang contents.',
            'type'          => 'string',
            'usage'         => 'language/iso-639',
            'default'       => DEFAULT_LANG
        ]
    ],
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


// generate attachment
$attachment = eQual::run('get', 'lodging_booking_print-booking', [
    'id'        => $params['booking_id'],
    'view_id'   =>'print.default',
    'lang'      => $params['lang'],
    'mode'      => $params['mode']
]);

// get 'quote' term transaltion
$main_attachment_name = Lang::get_term('sale', 'quote', 'quote', $params['lang']);

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


$attachments = [];

// push main attachment
$attachments[] = new Swift_Attachment($attachment, $main_attachment_name.'.pdf', 'application/pdf');

// add attachments whose ids have been received as param ($params['attachments_ids'])
if(count($params['attachments_ids'])) {
    $params['attachments_ids'] = array_unique($params['attachments_ids']);
    $template_attachments = TemplateAttachment::ids($params['attachments_ids'])->read(['name', 'document_id'])->get();
    foreach($template_attachments as $tid => $tdata) {
        $document = Document::id($tdata['document_id'])->read(['name', 'data', 'type'])->first();
        if($document) {
            $attachments[] = new Swift_Attachment($document['data'], $document['name'], $document['type']);
        }
    }
}

if(count($params['documents_ids'])) {
    foreach($params['documents_ids'] as $oid) {
        $document = Document::id($oid)->read(['name', 'data', 'type'])->first();
        if($document) {
            $attachments[] = new Swift_Attachment($document['data'], $document['name'], $document['type']);
        }
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
        ->setBody(str_replace(['<br>', '<p></p>'], '', $params['message']))
        ->setFrom([$params['sender_email'] => EMAIL_SMTP_ACCOUNT_DISPLAYNAME]);

foreach($attachments as $attachment) {
    $message->attach($attachment);
}

$mailer = new Swift_Mailer($transport);
$result = $mailer->send($message);


/*
    Setup a scheduled job to remind the customer about the quote (if still in 'quote' within the delay)
*/

$limit = Setting::get_value('sale', 'booking', 'quote.remind_delay', 7);

// add a task to the CRON
$cron->schedule(
    "booking.quote.reminder.{$params['booking_id']}",
    time() + $limit * 86400,
    'lodging_booking_remind-quote',
    [ 'id' => $params['booking_id'], 'lang' => $params['lang'] ]
);

$context->httpResponse()
        ->status(204)
        ->send();