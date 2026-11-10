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
    'description' => 'Send an email through Outlook using Microsoft Graph API.',
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
    'providers' => ['context', 'auth']
]);


/**
 * @var equal\php\Context                 $context
 * @var equal\auth\AuthenticationManager  $auth
 */
[
    'context' => $context,
    'auth'    => $auth
] = $providers;


/*
 * Microsoft Graph limits.
 *
 * Files smaller than 3 MB can be attached directly.
 * Files between 3 MB and 150 MB require an upload session.
 *
 * The upload chunk size must be a multiple of 320 KiB.
 */
$small_attachment_limit = 3 * 1024 * 1024;
$maximum_attachment_size = 150 * 1024 * 1024;
$upload_chunk_size = 10 * 320 * 1024;


/*
 * Convert a string containing one or several email addresses
 * to the Microsoft Graph recipient format.
 */
$build_recipients = static function($value): array {

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
            throw new Exception(
                "invalid_recipient_email",
                EQ_ERROR_INVALID_PARAM
            );
        }

        $recipients[] = [
            'emailAddress' => [
                'address' => $address
            ]
        ];
    }

    return $recipients;
};


/*
 * Detect the MIME type of a document from its binary data.
 */
$detect_mime_type = static function(string $data): string {

    static $finfo = null;

    if($finfo === null) {
        $finfo = new finfo(FILEINFO_MIME_TYPE);
    }

    $mime_type = $finfo->buffer($data);

    if(!$mime_type) {
        return 'application/octet-stream';
    }

    return $mime_type;
};


// LOAD AND VALIDATE MAILBOX

$mailbox = Mailbox::id($params['id'])
    ->read([
        'id',
        'status',
        'auth_type',
        'access_token',
        'access_token_expiry',
        'can_send',
        'email'
    ])
    ->first();

if(!$mailbox) {
    throw new Exception("unknown_mailbox", EQ_ERROR_INVALID_PARAM);
}

if($mailbox['status'] !== 'validated') {
    throw new Exception("non_validated_mailbox", EQ_ERROR_INVALID_PARAM);
}

if(!$mailbox['can_send']) {
    throw new Exception("non_sendable_mailbox", EQ_ERROR_INVALID_PARAM);
}

if($mailbox['auth_type'] !== 'oauth') {
    throw new Exception("non_oauth_mailbox", EQ_ERROR_INVALID_PARAM);
}

// REFRESH ACCESS TOKEN IF NEEDED

try {
    if($mailbox['access_token_expiry'] < time()) {

        eQual::run(
            'do',
            'communication_email_Mailbox_refresh-token-outlook',
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

    // Force a new OAuth authorization.
    Mailbox::id($params['id'])
        ->update([
            'status' => 'pending'
        ]);

    throw $e;
}


// LOAD EMAIL

$email = Email::id($params['email_id'])
    ->read([
        'id',
        'mailbox_id',
        'message_id',
        'subject',
        'from',
        'to',
        'reply_to',
        'cc',
        'bcc',
        'direction',
        'date',
        'body',
        'outgoing_attachment_documents_ids'
    ])
    ->first();

if(!$email) {
    throw new Exception("unknown_email", EQ_ERROR_INVALID_PARAM);
}

if((int) $email['mailbox_id'] !== (int) $mailbox['id']) {
    throw new Exception("email_mailbox_mismatch", EQ_ERROR_INVALID_PARAM);
}

$to_recipients = $build_recipients($email['to']);
$cc_recipients = $build_recipients($email['cc'] ?? '');
$bcc_recipients = $build_recipients($email['bcc'] ?? '');
$reply_to_recipients = $build_recipients($email['reply_to'] ?? '');

if(empty($to_recipients)) {
    throw new Exception("missing_email_recipient", EQ_ERROR_INVALID_PARAM);
}


// LOAD ATTACHMENTS

$documents = Document::ids($email['outgoing_attachment_documents_ids'])
    ->read(['id', 'name', 'data', 'content_type', 'extension']);


// GRAPH REQUEST HELPER

$graph_request = static function(
    string $method,
    string $url,
    $body,
    array $headers,
    array $expected_statuses,
    bool $authorize = true
) use ($mailbox) {

    if($authorize) {
        $headers['Authorization'] = 'Bearer ' . $mailbox['access_token'];
    }

    $request = new HttpRequest(
        "{$method} {$url}",
        $headers,
        $body ?? ''
    );

    $response = $request->send();

    if(!$response) {
        throw new Exception(
            "graph_api_unavailable",
            EQ_ERROR_INVALID_PARAM
        );
    }

    $status = $response->getStatusCode();

    if(!in_array($status, $expected_statuses, true)) {

        $response_body = $response->body();

        trigger_error(
            sprintf(
                "APP::Graph API error [%d] %s %s: %s",
                $status,
                $method,
                $url,
                json_encode(
                    $response_body,
                    JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
                )
            ),
            EQ_REPORT_ERROR
        );

        throw new Exception(
            "graph_api_error",
            EQ_ERROR_INVALID_PARAM
        );
    }

    return $response;
};


// CREATE DRAFT

$draft_payload = [
    'subject' => (string) ($email['subject'] ?: '(no subject)'),

    'body' => [
        'contentType' => 'HTML',
        'content'     => (string) ($email['body'] ?? '')
    ],

    'toRecipients' => $to_recipients,

    /*
     * This custom header can help trace the originating eQual email.
     * Custom Graph headers must start with "x-".
     */
    'internetMessageHeaders' => [
        [
            'name'  => 'X-eQual-Email-Id',
            'value' => (string) $email['id']
        ]
    ]
];


if(!empty($cc_recipients)) {
    $draft_payload['ccRecipients'] = $cc_recipients;
}

if(!empty($bcc_recipients)) {
    $draft_payload['bccRecipients'] = $bcc_recipients;
}

if(!empty($reply_to_recipients)) {
    $draft_payload['replyTo'] = $reply_to_recipients;
}

$draft_id = null;
$message_sent = false;

try {

    $create_response = $graph_request(
        'POST',
        'https://graph.microsoft.com/v1.0/me/messages',
        $draft_payload,
        [
            'Content-Type' => 'application/json'
        ],
        [200, 201]
    );

    $draft = $create_response->body();

    $draft_id = $draft['id'] ?? null;

    if(!$draft_id) {
        throw new Exception(
            "missing_graph_message_id",
            EQ_ERROR_INVALID_PARAM
        );
    }

    $encoded_draft_id = rawurlencode($draft_id);


    // ADD ATTACHMENTS

    foreach($documents as $document) {

        $document_name = trim(
            (string) ($document['name'] ?? '')
        );

        if($document_name === '') {
            $document_name = 'attachment';
        }

        if(pathinfo($document_name, PATHINFO_EXTENSION) === '') {
            $extension = ltrim(trim((string) ($document['extension'] ?? '')), '.');

            if($extension !== '') {
                $document_name = rtrim($document_name, '.') . '.' . $extension;
            }
        }

        $document_data = $document['data'] ?? null;

        if(!is_string($document_data)) {
            throw new Exception(
                "invalid_attachment_data",
                EQ_ERROR_INVALID_PARAM
            );
        }

        $document_size = strlen($document_data);

        if($document_size <= 0) {
            continue;
        }

        if($document_size > $maximum_attachment_size) {
            throw new Exception(
                "attachment_too_large",
                EQ_ERROR_INVALID_PARAM
            );
        }

        $mime_type = $detect_mime_type($document_data);


        /*
         * Small attachment: attach it directly to the draft.
         */
        if($document_size < $small_attachment_limit) {

            $attachment_payload = [
                '@odata.type' => '#microsoft.graph.fileAttachment',
                'name'        => $document_name,
                'contentType' => $mime_type,
                'contentBytes' => base64_encode($document_data)
            ];

            $graph_request(
                'POST',
                "https://graph.microsoft.com/v1.0/me/messages/{$encoded_draft_id}/attachments",
                $attachment_payload,
                [
                    'Content-Type' => 'application/json'
                ],
                [200, 201]
            );

            continue;
        }


        /*
         * Large attachment: create an upload session.
         */
        $session_payload = [
            'AttachmentItem' => [
                'attachmentType' => 'file',
                'name'           => $document_name,
                'size'           => $document_size
            ]
        ];

        $session_response = $graph_request(
            'POST',
            "https://graph.microsoft.com/v1.0/me/messages/{$encoded_draft_id}/attachments/createUploadSession",
            $session_payload,
            [
                'Content-Type' => 'application/json'
            ],
            [200, 201]
        );

        $session = $session_response->body();
        $upload_url = $session['uploadUrl'] ?? null;

        if(!$upload_url) {
            throw new Exception(
                "missing_graph_upload_url",
                EQ_ERROR_INVALID_PARAM
            );
        }


        /*
         * Upload the document sequentially.
         *
         * The upload URL already contains a temporary authorization token.
         * The OAuth bearer token must not be added to these requests.
         */
        $offset = 0;

        while($offset < $document_size) {

            $length = min(
                $upload_chunk_size,
                $document_size - $offset
            );

            $chunk = substr(
                $document_data,
                $offset,
                $length
            );

            $end = $offset + $length - 1;

            $graph_request(
                'PUT',
                $upload_url,
                $chunk,
                [
                    'Content-Type'  => 'application/octet-stream',
                    'Content-Range' => sprintf(
                        'bytes %d-%d/%d',
                        $offset,
                        $end,
                        $document_size
                    )
                ],
                [
                    // Intermediate chunk accepted.
                    202,
                    // Final chunk accepted.
                    200,
                    201
                ],
                false
            );

            $offset += $length;
        }
    }


    // SEND DRAFT

    $send_response = $graph_request(
        'POST',
        "https://graph.microsoft.com/v1.0/me/messages/{$encoded_draft_id}/send",
        '',
        [],
        [202]
    );

    $send_status = $send_response->getStatusCode();

    /*
     * A 202 response means Microsoft accepted the send request.
     * Delivery itself is processed asynchronously by Exchange.
     */
    $message_sent = true;


    // UPDATE LOCAL EMAIL

    $update_values = [
        'from'            => $mailbox['email'],
        'date'            => time(),
        'status'          => 'processed',
        'response_status' => 250,
        'response'        => sprintf('Microsoft Graph accepted send request (HTTP %d).', $send_status)
    ];

    /*
     * internetMessageId is distinct from the Graph resource ID.
     *
     * It corresponds to the RFC Message-ID and is therefore compatible
     * with the identifier stored by the incoming-email controller.
     */
    if(!empty($draft['internetMessageId'])) {
        $update_values['message_id'] = $draft['internetMessageId'];
    }

    Email::id($email['id'])
        ->update($update_values);
}
catch(Exception $e) {

    /*
     * Remove the unfinished draft when the error occurred before sending.
     *
     * Cleanup failure must not hide the initial exception.
     */
    if($draft_id && !$message_sent) {

        try {
            $graph_request(
                'DELETE',
                'https://graph.microsoft.com/v1.0/me/messages/' . rawurlencode($draft_id),
                '',
                [],
                [204]
            );
        }
        catch(Exception $cleanup_exception) {
            trigger_error(
                "APP::Unable to remove failed Outlook draft {$draft_id}.",
                EQ_REPORT_WARNING
            );
        }
    }

    throw $e;
}


$context->httpResponse()
    ->status(204)
    ->send();
