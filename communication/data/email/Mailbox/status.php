<?php
/*
    Developed by Yesbabylon - https://yesbabylon.com
    (c) 2025-2026 Yesbabylon SA
    Licensed under the GNU AGPL v3 License - https://www.gnu.org/licenses/agpl-3.0.html
*/

use communication\email\Mailbox;

[$params, $providers] = eQual::announce([
    'description' => 'Return an object with the status of the given mailbox.',
    'params'      => [
        'id' => [
            'type'           => 'many2one',
            'foreign_object' => 'communication\email\Mailbox',
            'description'    => 'Identifier of the mailbox.',
            'required'       => true
        ]
    ],
    'response' => [
        'content-type'  => 'application/json',
        'charset'       => 'utf-8',
        'accept-origin' => '*'
    ],
    'providers' => ['context']
]);

/**
 * @var \equal\php\Context $context
 */
['context' => $context] = $providers;

if(!isset($params['id'])) {
    throw new Exception('missing_id', EQ_ERROR_INVALID_PARAM);
}

$mailbox = Mailbox::id($params['id'])
    ->read(['status'])
    ->adapt('json')
    ->first(true);

if(!$mailbox) {
    throw new Exception('unknown_mailbox', EQ_ERROR_INVALID_PARAM);
}

$context->httpResponse()
    ->body($mailbox)
    ->send();
