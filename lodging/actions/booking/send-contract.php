<?php
if(!file_exists(QN_BASEDIR.'/vendor/swiftmailer/swiftmailer/lib/swift_required.php')) {
    throw new Exception("missing_dependency", QN_ERROR_INVALID_CONFIG);
}
require_once QN_BASEDIR.'/vendor/swiftmailer/swiftmailer/lib/swift_required.php';

use \Swift_SmtpTransport as Swift_SmtpTransport;
use \Swift_Message as Swift_Message;
use \Swift_Mailer as Swift_Mailer;
use \Swift_Attachment as Swift_Attachment;

use lodging\sale\booking\Booking;
use lodging\sale\booking\Contract;

use core\User;

// announce script and fetch parameters values
list($params, $providers) = announce([
    'description'	=>	"Send an email with given details with a booking contract as attachment.",
    'params' 		=>	[
        'booking_id' => [
            'description'   => 'Booking related to the sending of the email.',
            'type'          => 'string',
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
        'lang' =>  [
            'description'   => 'Language for multilang contents (2 letters ISO 639-1).',
            'type'          => 'string', 
            'default'       => DEFAULT_LANG
        ]        
    ],
    'response'      => [
        'content-type'      => 'application/json',
        'charset'           => 'utf-8',
        'accept-origin'     => '*'
    ],
    'providers'     => ['context', 'orm', 'auth']
]);


// initalise local vars with inputs
list($om, $context, $auth) = [ $providers['orm'], $providers['context'], $providers['auth'] ];

$user_id = $auth->userId();

if($user_id <= 0) {
    // restricted to identified users
    throw new Exception('unknown_user', QN_ERROR_NOT_ALLOWED);
}

$booking = Booking::id($params['booking_id'])->read(['has_contract', 'contracts_ids' => ['status']])->first();


if(!$booking) {
    throw new Exception("unknown_booking", QN_ERROR_UNKNOWN_OBJECT);
}

if(!$booking['has_contract'] || empty($booking['contracts_ids'])) {
    throw new Exception("incompatible_status", QN_ERROR_INVALID_PARAM);
}

// by convention the most recent contract is listed first (see schema in lodging/classes/sale/booking/Booking.class.php)
$contract_id = array_shift($booking['contracts_ids']);

$result = run('get', 'lodging_booking_print-contract', [
    'id'        => $contract_id ,
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

$attachment = new Swift_Attachment($result, 'document.pdf','application/pdf');

// send message
$transport = new Swift_SmtpTransport(EMAIL_SMTP_HOST, EMAIL_SMTP_PORT /*, 'ssl'*/);

$transport->setUsername(EMAIL_SMTP_ACCOUNT_USERNAME)
          ->setPassword(EMAIL_SMTP_ACCOUNT_PASSWORD);

$message = new Swift_Message();
$message->setTo($params['recipient_email'])
        ->setSubject($params['title'])
        ->setContentType("text/html")
        ->setBody($params['message'])
        ->setFrom([$params['sender_email'] => EMAIL_SMTP_ACCOUNT_DISPLAYNAME])
        ->attach($attachment);

$mailer = new Swift_Mailer($transport);
$result = $mailer->send($message);


Contract::id($contract_id)->update(['status' => 'sent']);

$context->httpResponse()
        ->body([])
        ->send();