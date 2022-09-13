<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
use lodging\identity\CenterOffice;
use lodging\identity\User;

list($params, $providers) = announce([
    'name'          => 'Generate Exports',
    'description'   => "Creates export archives with newly available data from invoices and payments.",
    'params'        => [],
    'access' => [
        'groups'            => ['finance.default.user'],
    ],
    'response'      => [
        'charset'             => 'utf-8',
        'accept-origin'       => '*'
    ],
    'providers'     => ['context', 'orm', 'auth']
]);

/**
 * @var \equal\php\Context $context
 * @var \equal\orm\ObjectManager $orm
 * @var \equal\auth\AuthenticationManager $auth
 */
list($context, $orm, $auth) = [$providers['context'], $providers['orm'], $providers['auth']];

// retrieve current user
$user_id = $auth->userId();
$user = User::id($user_id)->read(['center_offices_ids'])->first();

// generate exports for center offices current user belongs to
foreach($user['center_offices_ids'] as $center_offices_id) {
    eQual::run('do', 'lodging_payments_export-invoices', ['center_office_id' => $center_offices_id]);
    eQual::run('do', 'lodging_payments_export-payments', ['center_office_id' => $center_offices_id]);
}


$context->httpResponse()
        ->status(201)
        ->send();