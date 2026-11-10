<?php
/*
    Developed by Yesbabylon - https://yesbabylon.com
    (c) 2025-2026 Yesbabylon SA
    Licensed under the GNU AGPL v3 License - https://www.gnu.org/licenses/agpl-3.0.html
*/

use communication\email\Mailbox;

[$params, $providers] = eQual::announce([
    'description'	=>	"Fetch the new emails of a given Mailbox.",
    'params' 		=>	[
        'id' =>  [
            'type'             => 'many2one',
            'foreign_object'   => 'communication\email\Mailbox',
            'description'      => "Identifier of the Mailbox.",
            'required'         => true
        ]
    ],
    'access'        => [
        'visibility' => 'public'
    ],
    'response'      => [
        'content-type'      => 'application/json',
        'charset'           => 'utf-8',
        'accept-origin'     => '*'
    ],
    'providers'     => ['context']
]);

/**
 * @var equal\php\Context   $context
 */
['context' => $context] = $providers;


$mailbox = Mailbox::id($params['id'])
    ->read(['auth_type', 'auth_provider', 'imap_server'])
    ->first();

if(!$mailbox) {
    throw new Exception('unknown_mailbox', EQ_ERROR_INVALID_PARAM);
}

if($mailbox['auth_type'] === 'basic') {
    eQual::run('do', 'communication_email_Mailbox_fetch-imap', ['id' => $mailbox['id']], true);
}
elseif($mailbox['auth_type'] === 'oauth') {
    switch($mailbox['auth_provider']) {
        case 'microsoft':
            eQual::run('do', 'communication_email_Mailbox_fetch-outlook', ['id' => $mailbox['id']], true);
            break;
        case 'google':
            eQual::run('do', 'communication_email_Mailbox_fetch-gmail', ['id' => $mailbox['id']], true);
            break;
        default:
            switch($mailbox['imap_server']) {
                case 'imap.outlook.com':
                    eQual::run('do', 'communication_email_Mailbox_fetch-outlook', ['id' => $mailbox['id']], true);
                    break;
                case 'imap.gmail.com':
                    eQual::run('do', 'communication_email_Mailbox_fetch-gmail', ['id' => $mailbox['id']], true);
                    break;
            }
            break;
    }
}
else {
    throw new Exception('unknown_auth_type', EQ_ERROR_INVALID_CONFIG);
}

$context->httpResponse()
        ->status(204)
        ->send();
