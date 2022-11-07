<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

use lodging\identity\CenterOffice;
use lodging\identity\User;

list($params, $providers) = announce([
    'description'   => 'Returns descriptor of current User, based on received access_token',
    'response'      => [
        'content-type'      => 'application/json',
        'charset'           => 'UTF-8',
        'accept-origin'     => '*'
    ],
    'access' => [
        'visibility'        => 'protected'
    ],
    'providers'     => ['context', 'orm', 'auth']
]);

/**
 * @var \equal\php\Context                  $context
 * @var \equal\orm\ObjectManager            $orm
 * @var \equal\auth\AuthenticationManager   $auth
 */
list($context, $om, $auth) = [$providers['context'], $providers['orm'], $providers['auth']];

// retrieve current User identifier (HTTP headers lookup through Authentication Manager)
$user_id = $auth->userId();
// make sure user is authenticated
if($user_id <= 0) {
    throw new Exception('user_unknown', QN_ERROR_NOT_ALLOWED);
}
// request directly the mapper to bypass permission check on User class
$ids = $om->search('identity\User', ['id', '=', $user_id]);
// make sure the User object is available
if(!count($ids)) {
    throw new Exception('unexpected_error', QN_ERROR_INVALID_USER);
}
// user has allways READ right on its own object
$user = User::ids($ids)
            ->read([
                'id',
                'login',
                'name',
                'identity_id' => ['firstname', 'lastname'],
                'language',
                'organisation_id',
                'centers_ids',
                'center_offices_ids',
                'groups_ids' => ['name']
            ])
            ->adapt('txt')
            ->first(true);

if(!$user) {
    throw new Exception('unexpected_error', QN_ERROR_INVALID_USER);
}

// append info about user's Center Office
$preferred_cente_office_id = reset($user['center_offices_ids']);
$user['center_office'] = CenterOffice::id($preferred_cente_office_id)->read(['id', 'name', 'docs_default_mode'])->first();

// append list of user' groups
$user['groups'] = array_values(array_map(function ($a) {return $a['name'];}, $user['groups_ids']));
unset($user['groups_ids']);

// send back basic info of the User object
$context->httpResponse()
        ->body($user)
        ->send();