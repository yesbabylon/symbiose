<?php
/*
    Developed by Yesbabylon - https://yesbabylon.com
    (c) 2025-2026 Yesbabylon SA
    Licensed under the GNU AGPL v3 License - https://www.gnu.org/licenses/agpl-3.0.html
*/

use Webklex\PHPIMAP\ClientManager;

use communication\email\Email;
use communication\email\Mailbox;
use document\document\Document;

[$params, $providers] = eQual::announce([
    'description'	=>	"Fetch emails from a Gmail/Google Mailbox using Imap with OAuth2.",
    'params' 		=>	[
        'id' =>  [
            'type'             => 'many2one',
            'foreign_object'   => 'communication\email\Mailbox',
            'description'      => 'Identifier of the mailbox to fetch.',
            'required'         => true
        ]
    ],
    'access'        => [
        'visibility' => 'protected'
    ],
    'response'      => [
        'content-type'      => 'application/json',
        'charset'           => 'utf-8',
        'accept-origin'     => '*'
    ],
    'providers'     => ['context', 'auth', 'orm'],
    'constants'     => ['BACKEND_URL']
]);

/**
 * @var equal\php\Context                   $context
 * @var equal\orm\ObjectManager             $om
 * @var equal\auth\AuthenticationManager    $auth
 */
['context' => $context, 'orm' => $om, 'auth' => $auth] = $providers;

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

// check consistency
$mailbox = Mailbox::id($params['id'])
    ->read(['status', 'auth_type', 'access_token_expiry'])
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
    }
}
catch(Exception $e) {
    // refresh token failed : force the need for OAuth renewal
    Mailbox::id($params['id'])->update(['status' => 'pending']);
    throw $e;
}

// load info required for fetching emails
$mailbox = Mailbox::id($params['id'])
    ->read(['imap_server', 'imap_port', 'email', 'access_token', 'date_last_sync'])
    ->first();


try {

    $cm = new ClientManager([
        /*
        // #debug
        'options' => [
            'debug' => true,
            'log'   => true,
            'log_channel' => 'imap',
        ]
        */
    ]);

    $client = $cm->make([
        'host'           => $mailbox['imap_server'],
        'port'           => $mailbox['imap_port'],
        'encryption'     => 'ssl',
        'validate_cert'  => false,
        'username'       => $mailbox['email'],
        'password'       => $mailbox['access_token'],
        'authentication' => 'oauth',
        'protocol'       => 'imap'
    ]);

    $client->connect();

    // limit requests on main inbox
    $inbox = $client->getFolder('INBOX');

    // limit query to messages received since last sync
    $messages = $inbox->query()->since($mailbox['date_last_sync'])->get();
    $messages_buffer = [];
    foreach($messages as $message) {
        $messages_buffer[] = $message;
    }

    usort($messages_buffer, function($message_a, $message_b) {
        return strtotime($message_a->getDate()) <=> strtotime($message_b->getDate());
    });

    $new_date_last_sync = time();
    $imported_messages_count = 0;
    $fetch_limit_reached = false;
    $last_processed_message_date = null;

    foreach($messages_buffer as $message) {
        $message_id = $message->getMessageId();

        $email = Email::search(['message_id', '=', $message_id])
            ->read(['status'])
            ->first();

        // Resume interrupted imports, but skip messages that were fully processed.
        if($email && $email['status'] === 'processed') {
            continue;
        }

        $message_date = strtotime($message->getDate());

        if(!$email) {
            $email = Email::create([
                    'mailbox_id'               => $mailbox['id'],
                    'message_id'               => $message_id,
                    'subject'                  => substr($message->getSubject() ?: '(no subject)', 0, 255),
                    'from'                     => $message->getFrom()[0]->mail ?? '',
                    'to'                       => $message->getTo()[0]->mail ?? '',
                    'direction'                => 'incoming',
                    'date'                     => $message_date,
                    'body'                     => $getSafeTextHtml($message->getTextBody()),
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

        // handle attachments
        $attachment_count = 0;
        $has_unsupported_attachment = false;
        $ignored_attachment_names = [];

        // #todo - en cas d'absence de document, réponse automatique pour dire donnant le cadre dans lequel ce mail sera traité (pas lu, uniq. pièce jointe) -> si info importante : envoyer sur autre adresse

        foreach($message->getAttachments() as $attachment) {
            $disposition = strtolower(trim((string) $attachment->getDisposition()));
            if(str_starts_with($disposition, 'inline')) {
                continue;
            }

            ++$attachment_count;

            $attachment_name = trim($attachment->getName() ?? '') ?: 'attachment';
            $mime = strtolower(trim(explode(';', $attachment->getContentType() ?? '')[0]));
            if(!in_array($mime, $allowed_mime_types, true)) {
                $has_unsupported_attachment = true;
                $ignored_attachment_names[] = $attachment_name;
                continue;
            }

            $attachment_data = $attachment->getContent();
            if($attachment_data === null || $attachment_data === '') {
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
        if($message_date !== false) {
            $last_processed_message_date = $message_date;
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

    $client->disconnect();

}
catch(\Exception $e) {
    trigger_error('APP::Unable to connect to IMAP server: ' . $e->getMessage(), EQ_REPORT_ERROR);
    throw new Exception("imap_connect_error", EQ_ERROR_INVALID_PARAM);
}

$context->httpResponse()
        ->status(204)
        ->send();
