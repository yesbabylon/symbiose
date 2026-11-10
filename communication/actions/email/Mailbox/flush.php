<?php
/*
    Developed by Yesbabylon - https://yesbabylon.com
    (c) 2025-2026 Yesbabylon SA
    Licensed under the GNU AGPL v3 License - https://www.gnu.org/licenses/agpl-3.0.html
*/

use communication\email\Email;
use communication\email\Mailbox;

[$params, $providers] = eQual::announce([
    'description' => 'Send pending outgoing emails for a given Mailbox.',
    'params' => [
        'id' => [
            'type'            => 'many2one',
            'foreign_object'  => 'communication\\email\\Mailbox',
            'description'     => 'Identifier of the mailbox to flush.',
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

$mailbox = Mailbox::id($params['id'])
    ->read([
        'id',
        'status',
        'can_send',
        'auth_type',
        'auth_provider',
        'access_token_expiry',
        'smtp_server'
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

$controller = null;

if($mailbox['auth_type'] === 'basic') {
    $controller = 'communication_email_Mailbox_send-smtp';
}
elseif($mailbox['auth_type'] === 'oauth') {
    switch($mailbox['auth_provider']) {
        case 'google':
            $controller = 'communication_email_Mailbox_send-gmail';

            /*
             * Keep the refresh token active for send-only mailboxes even when
             * their outgoing queue is empty.
             */
            if($mailbox['access_token_expiry'] < time()) {
                try {
                    eQual::run(
                        'do',
                        'communication_email_Mailbox_refresh-token-gmail',
                        ['id' => $mailbox['id']]
                    );
                }
                catch(Exception $e) {
                    Mailbox::id($mailbox['id'])->update(['status' => 'pending']);
                    throw $e;
                }
            }
            break;

        case 'microsoft':
            $controller = 'communication_email_Mailbox_send-outlook';

            /*
             * Keep send-only mailboxes alive even when their outgoing queue is
             * empty. Receiving mailboxes already exercise this refresh path
             * while fetching messages.
             */
            if($mailbox['access_token_expiry'] < time()) {
                try {
                    eQual::run(
                        'do',
                        'communication_email_Mailbox_refresh-token-outlook',
                        ['id' => $mailbox['id']]
                    );
                }
                catch(Exception $e) {
                    Mailbox::id($mailbox['id'])->update(['status' => 'pending']);
                    throw $e;
                }
            }
            break;

        default:
            throw new Exception('unknown_auth_provider', EQ_ERROR_INVALID_CONFIG);
    }
}
else {
    throw new Exception('unknown_auth_type', EQ_ERROR_INVALID_CONFIG);
}

$emails = Email::search([
        ['mailbox_id', '=', $mailbox['id']],
        ['direction', '=', 'outgoing'],
        ['status', '=', 'pending']
    ])
    ->read(['id']);

foreach($emails as $email) {
    eQual::run(
        'do',
        $controller,
        [
            'id'       => $mailbox['id'],
            'email_id' => $email['id']
        ],
        true
    );
}

$context->httpResponse()
    ->status(204)
    ->send();
