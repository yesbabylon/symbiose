<?php
/*
    Developed by Yesbabylon - https://yesbabylon.com
    (c) 2025-2026 Yesbabylon SA
    Licensed under the GNU AGPL v3 License - https://www.gnu.org/licenses/agpl-3.0.html
*/

use communication\email\Email;
use communication\email\Mailbox;
use document\document\Document;
use equal\http\HttpRequest;

[$params, $providers] = eQual::announce([
    'description' => 'Send an email through Gmail API using a Google OAuth mailbox.',
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

$sanitize_header = static function(string $value): string {
    return trim(str_replace(["\r", "\n"], '', $value));
};

$format_address_header = static function(array $addresses): string {
    return implode(', ', array_map(static fn(string $address): string => '<' . $address . '>', $addresses));
};

$encode_header = static function(string $value) use ($sanitize_header): string {
    $value = $sanitize_header($value);

    if(preg_match('/[^\x20-\x7E]/', $value)) {
        return '=?UTF-8?B?' . base64_encode($value) . '?=';
    }

    return $value;
};

$encode_header_parameter = static function(string $value) use ($sanitize_header): string {
    return str_replace(['\\', '"'], ['\\\\', '\\"'], $sanitize_header($value));
};

$base64url_encode = static function(string $data): string {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
};

$base64_mime = static function(string $data): string {
    return rtrim(chunk_split(base64_encode($data), 76, "\r\n"));
};

$mailbox = Mailbox::id($params['id'])
    ->read([
        'id',
        'status',
        'auth_type',
        'auth_provider',
        'access_token',
        'access_token_expiry',
        'can_send',
        'email'
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

if($mailbox['auth_type'] !== 'oauth') {
    throw new Exception('non_oauth_mailbox', EQ_ERROR_INVALID_PARAM);
}

if($mailbox['auth_provider'] !== 'google') {
    throw new Exception('non_google_mailbox', EQ_ERROR_INVALID_PARAM);
}

try {
    if($mailbox['access_token_expiry'] < time()) {
        eQual::run(
            'do',
            'communication_email_Mailbox_refresh-token-gmail',
            [
                'id' => $mailbox['id']
            ]
        );

        $mailbox = Mailbox::id($mailbox['id'])
            ->read([
                'id',
                'access_token',
                'email'
            ])
            ->first();
    }
}
catch(Exception $e) {
    Mailbox::id($params['id'])
        ->update([
            'status' => 'pending'
        ]);

    throw $e;
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
$cc_recipients = $parse_recipients($email['cc'] ?? '');
$bcc_recipients = $parse_recipients($email['bcc'] ?? '');
$reply_to_recipients = $parse_recipients($email['reply_to'] ?? '');

if(empty($to_recipients)) {
    throw new Exception('missing_email_recipient', EQ_ERROR_INVALID_PARAM);
}

$documents = Document::ids($email['outgoing_attachment_documents_ids'])
    ->read([
        'id',
        'name',
        'data',
        'content_type',
        'extension'
    ]);

$domain = substr(strrchr($mailbox['email'], '@') ?: '@localhost', 1);
$message_id = sprintf(
    '<equal-%d-%s@%s>',
    $email['id'],
    bin2hex(random_bytes(8)),
    $domain
);

$headers = [
    'MIME-Version: 1.0',
    'Date: ' . date(DATE_RFC2822),
    'Message-ID: ' . $message_id,
    'From: <' . $mailbox['email'] . '>',
    'To: ' . $format_address_header($to_recipients),
    'Subject: ' . $encode_header((string) ($email['subject'] ?: '(no subject)'))
];

if(!empty($cc_recipients)) {
    $headers[] = 'Cc: ' . $format_address_header($cc_recipients);
}

if(!empty($bcc_recipients)) {
    $headers[] = 'Bcc: ' . $format_address_header($bcc_recipients);
}

if(!empty($reply_to_recipients)) {
    $headers[] = 'Reply-To: ' . $format_address_header($reply_to_recipients);
}

$body = (string) ($email['body'] ?? '');

if(empty($documents)) {
    $raw_message = implode("\r\n", array_merge($headers, [
        'Content-Type: text/html; charset=UTF-8',
        'Content-Transfer-Encoding: base64',
        '',
        $base64_mime($body)
    ]));
}
else {
    $boundary = '=_equal_' . bin2hex(random_bytes(12));
    $parts = [];

    $parts[] = implode("\r\n", [
        '--' . $boundary,
        'Content-Type: text/html; charset=UTF-8',
        'Content-Transfer-Encoding: base64',
        '',
        $base64_mime($body)
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

        $content_type = $document['content_type'] ?: 'application/octet-stream';
        $filename = $encode_header_parameter($document_name);

        $parts[] = implode("\r\n", [
            '--' . $boundary,
            'Content-Type: ' . $content_type . '; name="' . $filename . '"',
            'Content-Transfer-Encoding: base64',
            'Content-Disposition: attachment; filename="' . $filename . '"',
            '',
            $base64_mime($document_data)
        ]);
    }

    $parts[] = '--' . $boundary . '--';

    $raw_message = implode("\r\n", array_merge($headers, [
        'Content-Type: multipart/mixed; boundary="' . $boundary . '"',
        '',
        implode("\r\n", $parts)
    ]));
}

$request = new HttpRequest(
    'POST https://gmail.googleapis.com/gmail/v1/users/me/messages/send',
    [
        'Authorization' => 'Bearer ' . $mailbox['access_token'],
        'Content-Type'  => 'application/json'
    ],
    [
        'raw' => $base64url_encode($raw_message)
    ]
);

$response = $request->send();

if(!$response) {
    throw new Exception('gmail_api_unavailable', EQ_ERROR_INVALID_PARAM);
}

$status = $response->getStatusCode();
$data = $response->body();

if($status < 200 || $status >= 300) {
    trigger_error(
        'APP::Gmail API error: ' . json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
        EQ_REPORT_ERROR
    );

    throw new Exception('gmail_api_error', EQ_ERROR_INVALID_PARAM);
}

Email::id($email['id'])
    ->update([
        'message_id'       => $message_id,
        'from'             => $mailbox['email'],
        'date'             => time(),
        'status'           => 'processed',
        'response_status'  => 250,
        'response'         => sprintf('Gmail API accepted send request (HTTP %d, id: %s).', $status, $data['id'] ?? '')
    ]);

$context->httpResponse()
    ->status(204)
    ->send();
