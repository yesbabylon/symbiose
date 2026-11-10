<?php
/*
    Developed by Yesbabylon - https://yesbabylon.com
    (c) 2025-2026 Yesbabylon SA
    Licensed under the GNU AGPL v3 License - https://www.gnu.org/licenses/agpl-3.0.html
*/

use communication\email\Mailbox;
use equal\http\HttpRequest;

[$params, $providers] = eQual::announce([
    'description'	=>	"Refresh the access token of a given Mailbox.",
    'params' 		=>	[
        'id' =>  [
            'type'             => 'many2one',
            'foreign_object'   => 'communication\email\Mailbox',
            'description'      => 'Identifier of the Assembly item (resolution).',
        ]
    ],
    'access'        => [
        'visibility'    => 'public'
    ],
    'response'      => [
        'content-type'  => 'application/json',
        'charset'       => 'utf-8',
        'accept-origin' => '*'
    ],
    'constants'     => ['GOOGLE_OAUTH_CLIENT_ID', 'GOOGLE_OAUTH_CLIENT_SECRET'],
    'providers'     => ['context', 'auth', 'orm']
]);

/**
 * @var equal\php\Context                   $context
 * @var equal\orm\ObjectManager             $om
 * @var equal\auth\AuthenticationManager    $auth
 */
['context' => $context, 'orm' => $om, 'auth' => $auth] = $providers;


if(!isset($params['id'])) {
    throw new Exception("missing_id", EQ_ERROR_INVALID_PARAM);
}

$mailbox = Mailbox::id($params['id'])
    ->read(['status', 'auth_type', 'refresh_token'])
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

if(empty($mailbox['refresh_token'])) {
    throw new Exception("missing_refresh_token", EQ_ERROR_INVALID_PARAM);
}


$body = [
    'grant_type'    => 'refresh_token',
    'client_id'     => constant('GOOGLE_OAUTH_CLIENT_ID'),
    'client_secret' => constant('GOOGLE_OAUTH_CLIENT_SECRET'),
    'refresh_token' => $mailbox['refresh_token']
];

$oauthRequest = new HttpRequest('POST https://oauth2.googleapis.com/token');
$response = $oauthRequest
            ->header('Content-Type', 'application/x-www-form-urlencoded')
            ->setBody($body)
            ->send();

$data = $response->body();
$status = $response->getStatusCode();

if($status < 200 || $status >= 300) {
    trigger_error("APP::Gmail OAuth refresh failed: " . json_encode($data), EQ_REPORT_ERROR);
    throw new Exception("refresh_token_failed", EQ_ERROR_INVALID_PARAM);
}

if(empty($data['access_token']) || empty($data['expires_in'])) {
    trigger_error("APP::Gmail OAuth refresh returned an incomplete response: " . json_encode($data), EQ_REPORT_ERROR);
    throw new Exception("invalid_oauth_response", EQ_ERROR_INVALID_PARAM);
}

$updates = [
    'access_token'        => $data['access_token'],
    'access_token_expiry' => time() + $data['expires_in']
];

/* Google does not normally rotate refresh tokens during this exchange. */
if(!empty($data['refresh_token'])) {
    $updates['refresh_token'] = $data['refresh_token'];

    if(!empty($data['refresh_token_expires_in'])) {
        $updates['refresh_token_expiry'] = time() + $data['refresh_token_expires_in'];
    }
}

Mailbox::id($mailbox['id'])->update($updates);


$context->httpResponse()
        ->status(204)
        ->send();
