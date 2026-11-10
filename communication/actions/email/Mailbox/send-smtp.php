<?php
/*
    Developed by Yesbabylon - https://yesbabylon.com
    (c) 2025-2026 Yesbabylon SA
    Licensed under the GNU AGPL v3 License - https://www.gnu.org/licenses/agpl-3.0.html
*/

use communication\email\Email;
use communication\email\Mailbox;
use document\document\Document;
use equal\email\Email as EmailMessage;
use equal\email\EmailAttachment;
use equal\mailer\MailerSmtp;

[$params, $providers] = eQual::announce([
    'description' => 'Send an email through the SMTP configuration of a mailbox.',
    'params' => [
        'id' => [
            'type'            => 'many2one',
            'foreign_object'  => 'communication\\email\\Mailbox',
            'description'     => 'Identifier of the mailbox used to send the email.',
            'required'        => true
        ],
        'email_id' => [
            'type'            => 'many2one',
            'foreign_object'  => 'communication\\email\\Email',
            'description'     => 'Identifier of the email to send.',
            'required'        => true
        ]
    ],
    'access' => [
        'visibility' => 'protected'
    ],
    'response' => [
        'content-type'  => 'application/json',
        'charset'       => 'utf-8',
        'accept-origin' => '*'
    ],
    'providers' => ['context']
]);

/**
 * @var equal\php\Context $context
 */
['context' => $context] = $providers;

$parse_recipients = static function($value): array {
    if(is_array($value)) {
        $addresses = $value;
    }
    else {
        $addresses = preg_split(
            '/[\s,;]+/',
            trim((string) $value),
            -1,
            PREG_SPLIT_NO_EMPTY
        );
    }

    $recipients = [];
    foreach($addresses as $address) {
        $address = trim((string) $address);

        if(!filter_var($address, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('invalid_recipient_email', EQ_ERROR_INVALID_PARAM);
        }

        $recipients[] = $address;
    }

    return $recipients;
};

$mailbox = Mailbox::id($params['id'])
    ->read([
        'id',
        'status',
        'auth_type',
        'can_send',
        'email',
        'login',
        'password',
        'smtp_server',
        'smtp_port'
    ])
    ->first();

if(!$mailbox) {
    throw new Exception('unknown_mailbox', EQ_ERROR_INVALID_PARAM);
}

if($mailbox['status'] !== 'validated') {
    throw new Exception('non_validated_mailbox', EQ_ERROR_INVALID_PARAM);
}

if(!$mailbox['can_send']) {
    throw new Exception('non_sendable_mailbox', EQ_ERROR_INVALID_PARAM);
}

if($mailbox['auth_type'] !== 'basic') {
    throw new Exception('non_basic_mailbox', EQ_ERROR_INVALID_PARAM);
}

if(empty($mailbox['smtp_server']) || empty($mailbox['smtp_port'])) {
    throw new Exception('missing_smtp_configuration', EQ_ERROR_INVALID_CONFIG);
}

$username = !empty($mailbox['login']) ? $mailbox['login'] : $mailbox['email'];

if(empty($username) || empty($mailbox['password'])) {
    throw new Exception('missing_smtp_credentials', EQ_ERROR_INVALID_CONFIG);
}

$email = Email::id($params['email_id'])
    ->read([
        'id',
        'mailbox_id',
        'message_id',
        'subject',
        'to',
        'reply_to',
        'cc',
        'bcc',
        'direction',
        'status',
        'body',
        'outgoing_attachment_documents_ids'
    ])
    ->first();

if(!$email) {
    throw new Exception('unknown_email', EQ_ERROR_INVALID_PARAM);
}

if((int) $email['mailbox_id'] !== (int) $mailbox['id']) {
    throw new Exception('email_mailbox_mismatch', EQ_ERROR_INVALID_PARAM);
}

if($email['direction'] !== 'outgoing') {
    throw new Exception('non_outgoing_email', EQ_ERROR_INVALID_PARAM);
}

if($email['status'] !== 'pending') {
    throw new Exception('non_pending_email', EQ_ERROR_INVALID_PARAM);
}

$to_recipients = $parse_recipients($email['to']);

if(empty($to_recipients)) {
    throw new Exception('missing_email_recipient', EQ_ERROR_INVALID_PARAM);
}

$message = new EmailMessage();
$message
    ->setId($email['id'])
    ->setTo($to_recipients[0])
    ->setSubject((string) ($email['subject'] ?: '(no subject)'))
    ->setContentType('text/html')
    ->setBody((string) ($email['body'] ?? ''));

foreach($parse_recipients($email['cc'] ?? '') as $address) {
    $message->addCc($address);
}

foreach($parse_recipients($email['bcc'] ?? '') as $address) {
    $message->addBcc($address);
}

$reply_to_recipients = $parse_recipients($email['reply_to'] ?? '');
if(!empty($reply_to_recipients)) {
    $message->setReplyTo($reply_to_recipients[0]);
}

$documents = Document::ids($email['outgoing_attachment_documents_ids'])
    ->read([
        'id',
        'name',
        'data',
        'content_type',
        'extension'
    ]);

foreach($documents as $document) {
    $document_data = $document['data'] ?? null;

    if(!is_string($document_data) || strlen($document_data) <= 0) {
        continue;
    }

    $document_name = trim((string) ($document['name'] ?? '')) ?: 'attachment';

    if(pathinfo($document_name, PATHINFO_EXTENSION) === '') {
        $extension = ltrim(trim((string) ($document['extension'] ?? '')), '.');

        if($extension !== '') {
            $document_name = rtrim($document_name, '.') . '.' . $extension;
        }
    }

    $message->addAttachment(new EmailAttachment(
        $document_name,
        $document_data,
        $document['content_type'] ?: 'application/octet-stream'
    ));
}

$encryption = null;
if((int) $mailbox['smtp_port'] === 465) {
    $encryption = 'ssl';
}
elseif((int) $mailbox['smtp_port'] === 587) {
    $encryption = 'tls';
}

$mailer = new MailerSmtp(
    $mailbox['smtp_server'],
    $mailbox['smtp_port'],
    $username,
    $mailbox['password'],
    $encryption,
    $mailbox['email']
);

$sent = $mailer->send($message, [
    'from'     => $mailbox['email'],
    'username' => $username
]);

if($sent <= 0) {
    throw new Exception('failed_sending_email', EQ_ERROR_UNKNOWN);
}

Email::id($email['id'])
    ->update([
        'from'            => $mailbox['email'],
        'date'            => time(),
        'status'          => 'processed',
        'response_status' => 250,
        'response'        => sprintf('SMTP accepted %d recipient(s).', $sent)
    ]);

$context->httpResponse()
    ->status(204)
    ->send();
