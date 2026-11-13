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
    'description' => "Fetch emails from Outlook using Microsoft Graph API.",
    'params' => [
        'id' => [
            'type'            => 'many2one',
            'foreign_object'  => 'communication\\email\\Mailbox',
            'description'     => 'Identifier of the mailbox to fetch.',
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
    'providers' => ['context', 'auth']
]);


/**
 * @var equal\php\Context                $context
 * @var equal\auth\AuthenticationManager $auth
 */
['context' => $context, 'auth' => $auth] = $providers;


$getSafeTextHtml = function($body) {
    $content_type = strtolower(trim((string) ($body['contentType'] ?? '')));
    $content = (string) ($body['content'] ?? '');

    if($content_type !== 'text' || trim($content) === '') {
        return '<pre>[no content]</pre>';
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


$allowed_mime_types = [
        'text/xml',
        'application/xml',
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
    ];

$max_messages_per_fetch = 25;
$graph_page_size = 50;


$mailbox = Mailbox::id($params['id'])
    ->read(['status', 'auth_type', 'access_token', 'access_token_expiry', 'email', 'created', 'date_last_sync'])
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

/* Refresh token if needed */
try {
    if($mailbox['access_token_expiry'] < time()) {
        eQual::run('do', 'communication_email_Mailbox_refresh-token-outlook', ['id' => $params['id']]);

        $mailbox = Mailbox::id($params['id'])
            ->read(['access_token', 'email', 'date_last_sync'])
            ->first();
    }
}
catch(Exception $e) {
    // refresh token failed : force the need for OAuth renewal
    Mailbox::id($params['id'])->update(['status' => 'pending']);
    throw $e;
}


// GRAPH API REQUEST : FETCH NEW EMAILS

$since = str_replace('+00:00', 'Z', gmdate('c', $mailbox['date_last_sync'] ?? $mailbox['created']));
$query = http_build_query([
    '$filter'   => "receivedDateTime ge $since",
    '$orderby'  => 'receivedDateTime asc',
    '$top'      => $graph_page_size
]);

$url = "https://graph.microsoft.com/v1.0/me/mailFolders/inbox/messages?$query";
$new_date_last_sync = time();
$imported_messages_count = 0;
$fetch_limit_reached = false;
$last_processed_message_date = null;

do {
    $http = new HttpRequest("GET $url");

    $response = $http
        ->header("Authorization", "Bearer " . $mailbox['access_token'])
        ->header("Prefer", 'outlook.body-content-type="text"')
        ->send();

    $data = $response->body();
    $status = $response->getStatusCode();

    if($status < 200 || $status > 299) {
        trigger_error("APP::Graph API error: " . json_encode($data), EQ_REPORT_ERROR);
        throw new Exception("graph_api_error", EQ_ERROR_INVALID_PARAM);
    }

    $messages = $data['value'] ?? [];

    foreach($messages as $msg) {

        $message_id = $msg['id'];
        $internet_id = $msg['internetMessageId'] ?? ('graph-' . $message_id);

        $email = Email::search(['message_id', '=', $internet_id])
            ->read(['status'])
            ->first();

        // Resume interrupted imports, but skip messages that were fully processed.
        if($email && $email['status'] === 'processed') {
            continue;
        }

        $message_date = strtotime($msg['receivedDateTime'] ?? '');

        if(!$email) {
            $email = Email::create([
                    'mailbox_id'               => $mailbox['id'],
                    'message_id'               => $internet_id,
                    'subject'                  => substr($msg['subject'] ?: '(no subject)', 0, 255),
                    'from'                     => $msg['from']['emailAddress']['address'] ?? '',
                    'to'                       => $msg['toRecipients'][0]['emailAddress']['address'] ?? '',
                    'direction'                => 'incoming',
                    'date'                     => $message_date,
                    'body'                     => $getSafeTextHtml($msg['body'] ?? []),
                    'incoming_attachment_import_status' => 'pending'
                ])
                ->read(['thread_hash'])
                ->first();
        }

        /*
         * Index documents that might have been created by an interrupted import.
         * This prevents duplicates when the message is resumed.
         */
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

        // handle attachments
        $encoded_message_id = rawurlencode($message_id);
        $attachments_url = "https://graph.microsoft.com/v1.0/me/messages/{$encoded_message_id}/attachments";
        $attachment_count = 0;
        $has_unsupported_attachment = false;
        $ignored_attachment_names = [];

        // #todo - en cas d'absence de document, reponse automatique pour dire donnant le cadre dans lequel ce mail sera traite (pas lu, uniq. piece jointe) -> si info importante : envoyer sur autre adresse

        do {
            $attReq = new HttpRequest("GET $attachments_url");
            $attRes = $attReq
                ->header("Authorization", "Bearer " . $mailbox['access_token'])
                ->send();

            $attData = $attRes->body();
            $attStatus = $attRes->getStatusCode();

            if($attStatus < 200 || $attStatus > 299) {
                trigger_error("APP::Graph API attachments error: " . json_encode($attData), EQ_REPORT_ERROR);
                throw new Exception("graph_api_attachments_error", EQ_ERROR_INVALID_PARAM);
            }

            $attachments = $attData['value'] ?? [];

            foreach($attachments as $att) {
                $attachment_name = trim($att['name'] ?? '') ?: 'attachment';
                $attachment_type = $att['@odata.type'] ?? null;
                $mime = strtolower(trim(explode(';', $att['contentType'] ?? '')[0]));

                if($mime === 'application/octet-stream' && strtolower(pathinfo($attachment_name, PATHINFO_EXTENSION)) === 'pdf') {
                    $mime = 'application/pdf';
                }

                if(!in_array($mime, $allowed_mime_types, true)) {
                    if(($att['isInline'] ?? false) !== true) {
                        ++$attachment_count;
                        $has_unsupported_attachment = true;
                        $ignored_attachment_names[] = $attachment_name;
                    }
                    continue;
                }

                ++$attachment_count;

                if(
                    ($attachment_type && $attachment_type !== '#microsoft.graph.fileAttachment')
                    || !isset($att['contentBytes'])
                ) {
                    $has_unsupported_attachment = true;
                    $ignored_attachment_names[] = $attachment_name;
                    continue;
                }

                $attachment_data = base64_decode($att['contentBytes'], true);

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

            $attachments_url = $attData['@odata.nextLink'] ?? null;
        }
        while($attachments_url);

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
        if($message_date !== false) {
            $last_processed_message_date = $message_date;
        }

        if($imported_messages_count >= $max_messages_per_fetch) {
            $fetch_limit_reached = true;
            break;
        }
    }

    $url = $data['@odata.nextLink'] ?? null;
}
while($url && !$fetch_limit_reached);

if($fetch_limit_reached && $last_processed_message_date !== null) {
    Mailbox::id($mailbox['id'])->update(['date_last_sync' => max(0, $last_processed_message_date - 1)]);
}
else {
    Mailbox::id($mailbox['id'])->update(['date_last_sync' => $new_date_last_sync]);
}

$context->httpResponse()
    ->status(204)
    ->send();
