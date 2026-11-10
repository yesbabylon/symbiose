<?php
/*
    Developed by Yesbabylon - https://yesbabylon.com
    (c) 2025-2026 Yesbabylon SA
    Licensed under the GNU AGPL v3 License - https://www.gnu.org/licenses/agpl-3.0.html
*/

use communication\email\Email;
use communication\email\Mailbox;
use document\document\Document;

[$params, $providers] = eQual::announce([
    'description'	=>	"Fetch emails from a Gmail/Google Mailbox using Gmail API with OAuth2.",
    'params' 		=>	[
        'id' =>  [
            'type'             => 'many2one',
            'foreign_object'   => 'communication\email\Mailbox',
            'description'      => "Identifier of the mailbox to fetch.",
            'required'         => true
        ]
    ],
    'access'        => [
        'visibility'    => 'protected'
    ],
    'response'      => [
        'content-type'  => 'application/json',
        'charset'       => 'utf-8',
        'accept-origin' => '*'
    ],
    'providers'     => ['context']
]);

/**
 * @var equal\php\Context   $context
 */
['context' => $context] = $providers;

/*************
 * Functions *
 *************/

$getSafeTextHtml = function($text) {
    $content = (string) ($text ?? '');
    if(trim($content) === '') {
        return '[no content]';
    }

    $max_body_length = 65000;
    if(strlen($content) > $max_body_length) {
        $suffix = '[...]';
        $content = mb_strcut($content, 0, $max_body_length - strlen($suffix), 'UTF-8') . $suffix;
    }

    return nl2br(
        htmlspecialchars($content, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8'),
        false
    );
};

/**
 * Returns a page of emails that were received after the given timestamp
 *
 * @param string $access_token      Gmail Google API token
 * @param int $after                Unix timestamp
 * @param string|null $page_token   Gmail pagination token
 * @param int $max_results          Maximum number of messages for the page
 * @return array                    Gmail list response subset
 * @throws Exception
 */
$getMessagesPage = function($access_token, $after, $page_token = null, $max_results = 50) {
    $params = ['maxResults' => $max_results, 'labelIds' => 'INBOX'];

    if($after > 0) {
        $params['q'] = "after:$after";
    }

    if($page_token) {
        $params['pageToken'] = $page_token;
    }

    $url = "https://gmail.googleapis.com/gmail/v1/users/me/messages?".http_build_query($params);

    $http = new \equal\http\HttpRequest("GET $url");

    $response = $http
        ->header("Authorization", "Bearer $access_token")
        ->send();

    $data = $response->body();
    $status = $response->getStatusCode();

    if($status < 200 || $status > 299) {
        trigger_error("APP::Gmail API error: " . json_encode($data), EQ_REPORT_ERROR);
        throw new Exception("gmail_api_error", EQ_ERROR_INVALID_PARAM);
    }

    return [
        'messages'       => $data['messages'] ?? [],
        'nextPageToken'  => $data['nextPageToken'] ?? null
    ];
};

/**
 * Returns a specific email fetched from Gmail API
 *
 * @param string $access_token  Gmail Google API token
 * @param int $id               Gmail API id the email to fetch
 * @return array                HTML body
 * @throws Exception
 */
$getMessage = function($access_token, $id) {
    $url = "https://gmail.googleapis.com/gmail/v1/users/me/messages/$id?format=full";

    $http = new \equal\http\HttpRequest("GET $url");

    $response = $http
        ->header("Authorization", "Bearer $access_token")
        ->send();

    $data = $response->body();
    $status = $response->getStatusCode();

    if($status < 200 || $status > 299) {
        trigger_error("APP::Gmail API error: " . json_encode($data), EQ_REPORT_ERROR);
        throw new Exception("gmail_api_error", EQ_ERROR_INVALID_PARAM);
    }

    return $data;
};

/**
 * Extracts a plain-text body from the given Gmail message payload.
 *
 * @param array $payload    Gmail API message payload
 * @return string|null      Plain-text body, or null if none is available
 */
$extractTextBody = function($payload) use(&$extractTextBody) {
    if(
        ($payload['mimeType'] ?? '') === 'text/plain'
        && empty($payload['filename'])
        && !empty($payload['body']['data'])
    ) {
        $decoded = base64_decode(strtr($payload['body']['data'], '-_', '+/'), true);
        return $decoded === false ? null : $decoded;
    }

    foreach($payload['parts'] ?? [] as $part) {
        $text = $extractTextBody($part);
        if($text !== null) {
            return $text;
        }
    }

    return null;
};

/**
 * Extracts first email address found in given string (based on rfc822)
 *
 * @param string $address_header raw imap email address (e.g.: "Google <no-reply@accounts.google.com>", "fmtsolutions.yb@gmail.com")
 * @return mixed|string
 */
$extractEmailAddress = function($address_header) {
    if (preg_match('/<([^>]+)>/', $address_header, $matches)) {
        return $matches[1];
    }
    return filter_var($address_header, FILTER_VALIDATE_EMAIL) ? $address_header : '';
};

/**
 * Extracts attachments from the given Gmail message payload
 *
 * @param array $payload    Gmail API message payload
 * @return array            List of attachements
 */
$extractMessageAttachments = function($payload) use (&$extractMessageAttachments) {
    $attachments = [];

    $filename = $payload['filename'] ?? '';
    $size = $payload['body']['size'] ?? 0;
    $disposition = '';
    foreach($payload['headers'] ?? [] as $header) {
        if(strtolower($header['name']) === 'content-disposition') {
            $disposition = strtolower($header['value']);
            break;
        }
    }

    $is_attachment = str_contains($disposition, 'attachment') && $filename;
    if($is_attachment && $size > 0) {
        $attachment_id = $payload['body']['attachmentId'] ?? null;
        $inline_data = $payload['body']['data'] ?? null;

        $attachments[] = [
            'filename'      => $filename,
            'mimeType'      => $payload['mimeType'] ?? 'application/octet-stream',
            'size'          => $size,
            'attachmentId'  => $attachment_id,  // null if inline
            'data'          => $inline_data,    // null if external
        ];
    }

    foreach($payload['parts'] ?? [] as $part) {
        $sub_attachments = $extractMessageAttachments($part);
        foreach($sub_attachments as $sub_attachment) {
            $attachments[] = $sub_attachment;
        }
    }

    return $attachments;
};

/**
 * Returns a specific attachment fetched from Gmail API
 *
 * @param string $access_token  Gmail Google API token
 * @param int $message_id       Gmail API email's id of the attachment to fetch
 * @param int $id               Gmail API id of the attachment to fetch
 * @return mixed|string
 * @throws Exception
 */
$getAttachment = function($access_token, $message_id, $id) {
    $url = "https://gmail.googleapis.com/gmail/v1/users/me/messages/$message_id/attachments/$id";

    $http = new \equal\http\HttpRequest("GET $url");

    $response = $http
        ->header("Authorization", "Bearer $access_token")
        ->send();

    $data = $response->body();
    $status = $response->getStatusCode();

    if($status < 200 || $status > 299) {
        trigger_error("APP::Graph API error: " . json_encode($data), EQ_REPORT_ERROR);
        throw new Exception("graph_api_error", EQ_ERROR_INVALID_PARAM);
    }

    return $data['data'] ?? '';
};

/**********
 * Action *
 **********/

$allowed_mime_types = [
        'text/xml',
        'application/xml',
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    ];

$max_messages_per_fetch = 25;
$gmail_page_size = 50;

// check consistency
$mailbox = Mailbox::id($params['id'])
    ->read(['status', 'auth_type', 'access_token', 'access_token_expiry', 'created', 'date_last_sync'])
    ->first();

if(!$mailbox) {
    throw new Exception("unknown_mailbox", EQ_ERROR_INVALID_PARAM);
}

if($mailbox['status'] !== 'validated') {
    throw new Exception("non_validated_mailbox", EQ_ERROR_INVALID_PARAM);
}

if($mailbox['auth_type'] !== 'oauth') {
    throw new Exception("non_oauth_mailbox", EQ_ERROR_INVALID_PARAM);
}

try {
    if($mailbox['access_token_expiry'] < time()) {
        eQual::run('do', 'communication_email_Mailbox_refresh-token-gmail', ['id' => $params['id']]);

        $mailbox = Mailbox::id($params['id'])
            ->read(['status', 'auth_type', 'access_token', 'access_token_expiry', 'created', 'date_last_sync'])
            ->first();
    }
}
catch(Exception $e) {
    // refresh token failed : force the need for OAuth renewal
    Mailbox::id($params['id'])->update(['status' => 'pending']);
    trigger_error("Gmail OAuth token expired: " . $e->getMessage(), EQ_REPORT_ERROR);
    throw new \Exception('expired_oauth_token', EQ_ERROR_UNKNOWN);
}

$new_date_last_sync = time();
$imported_messages_count = 0;
$fetch_limit_reached = false;
$next_page_token = null;
$message_refs = [];
$last_processed_message_date = null;

do {
    $page = $getMessagesPage($mailbox['access_token'], $mailbox['date_last_sync'] ?? $mailbox['created'], $next_page_token, $gmail_page_size);

    foreach($page['messages'] as $message_ref) {
        $message_refs[] = $message_ref;
    }

    $next_page_token = $page['nextPageToken'];
}
while($next_page_token);

$message_refs = array_reverse($message_refs);

foreach($message_refs as $message_ref) {
    $message_id = $message_ref['id'];

    $email = Email::search(['message_id', '=', $message_id])
        ->read(['status'])
        ->first();

    // Resume interrupted imports, but skip messages that were fully processed.
    if($email && $email['status'] === 'processed') {
        continue;
    }

    $message = $getMessage($mailbox['access_token'], $message_id);

    $headers = [
        'Subject'   => '(no subject)',
        'From'      => '',
        'To'        => '',
        'Date'      => '',
    ];
    foreach($message['payload']['headers'] as $header) {
        if(in_array($header['name'], ['Subject', 'From', 'To', 'Date'])) {
            $headers[$header['name']] = $header['value'];
        }
    }

    $body = $getSafeTextHtml($extractTextBody($message['payload']));
    $message_date = strtotime($headers['Date']);
    $message_internal_date = !empty($message['internalDate']) ? intval($message['internalDate'] / 1000) : null;

    if(!$email) {
        $email = Email::create([
                'mailbox_id'               => $mailbox['id'],
                'message_id'               => $message_id,
                'subject'                  => substr($headers['Subject'], 0, 255),
                'from'                     => $extractEmailAddress($headers['From']),
                'to'                       => $extractEmailAddress($headers['To']),
                'direction'                => 'incoming',
                'date'                     => $message_date,
                'body'                     => $body,
                'incoming_attachment_import_status' => 'pending'
            ])
            ->read(['thread_hash'])
            ->first();
    }

    $existing_documents_by_signature = [];
    $existing_documents = Document::search([
            ['source_object_class', '=', Email::class],
            ['source_object_id', '=', $email['id']],
            ['source', '=', 'email']
        ])
        ->read(['name', 'hash_sha256']);

    foreach($existing_documents as $document_id => $document) {
        $signature = $document['name'] . "\0" . $document['hash_sha256'];
        $existing_documents_by_signature[$signature][] = $document_id;
    }

    $attachments = $extractMessageAttachments($message['payload']);
    $attachment_count = count($attachments);
    $has_unsupported_attachment = false;
    $ignored_attachment_names = [];

    foreach($attachments as $attachment) {
        $attachment_name = trim($attachment['filename'] ?? '') ?: 'attachment';
        $mime = strtolower(trim(explode(';', $attachment['mimeType'] ?? '')[0]));

        if(!in_array($mime, $allowed_mime_types, true)) {
            $has_unsupported_attachment = true;
            $ignored_attachment_names[] = $attachment_name;
            continue;
        }

        if(!$attachment['data'] && !$attachment['attachmentId']) {
            $has_unsupported_attachment = true;
            $ignored_attachment_names[] = $attachment_name;
            continue;
        }

        $data = $attachment['data'];
        if(!$data) {
            $data = $getAttachment($mailbox['access_token'], $message_id, $attachment['attachmentId']);
        }

        $attachment_data = base64_decode(strtr($data, '-_', '+/'), true);

        if($attachment_data === false || $attachment_data === '') {
            $has_unsupported_attachment = true;
            $ignored_attachment_names[] = $attachment_name;
            continue;
        }

        $signature = $attachment_name . "\0" . hash('sha256', $attachment_data);

        if(!empty($existing_documents_by_signature[$signature])) {
            array_shift($existing_documents_by_signature[$signature]);

            continue;
        }

        Document::create([
                'name'                => $attachment_name,
                'data'                => $attachment_data,
                'source'              => 'email',
                'source_object_class' => Email::class,
                'source_object_id'    => $email['id']
            ]);
    }

    $incoming_attachment_import_status = 'complete';
    if($attachment_count === 0) {
        $incoming_attachment_import_status = 'missing';
    }
    elseif($has_unsupported_attachment) {
        $incoming_attachment_import_status = 'unsupported';
    }

    Email::id($email['id'])->update([
        'incoming_attachment_import_status' => $incoming_attachment_import_status,
        'incoming_attachment_import_log'  => implode("\n", $ignored_attachment_names),
        'status'                   => 'processed'
    ]);

    ++$imported_messages_count;
    if($message_internal_date !== null) {
        $last_processed_message_date = $message_internal_date;
    }

    if($imported_messages_count >= $max_messages_per_fetch) {
        $fetch_limit_reached = true;
        break;
    }
}

if($fetch_limit_reached && $last_processed_message_date !== null) {
    Mailbox::id($mailbox['id'])->update(['date_last_sync' => max(0, $last_processed_message_date - 1)]);
}
else {
    Mailbox::id($mailbox['id'])->update(['date_last_sync' => $new_date_last_sync]);
}

$context
    ->httpResponse()
    ->status(204)
    ->send();
