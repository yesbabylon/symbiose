<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2026
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

use infra\cloud\CloudProject;

[$params, $providers] = eQual::announce([
    'description'   => 'Synchronize the provider resource catalog of a cloud project.',
    'params'        => [
        'id' => [
            'type'              => 'many2one',
            'foreign_object'    => 'infra\cloud\CloudProject',
            'description'       => 'Identifier of the cloud project to synchronize.',
            'required'          => true
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
 * @var \equal\php\Context $context
 */
['context' => $context] = $providers;

$cloudProject = CloudProject::id($params['id'])
    ->read(['cloud_provider_code'])
    ->first();

if(!$cloudProject) {
    throw new Exception('unknown_cloud_project', EQ_ERROR_UNKNOWN_OBJECT);
}

$controllers = [
    'ovh' => 'infra_cloud_cloudproject_ovh_sync-provider-catalog'
];

if(!isset($controllers[$cloudProject['cloud_provider_code']])) {
    throw new Exception('wrong_cloud_provider', EQ_ERROR_INVALID_PARAM);
}

eQual::run('do', $controllers[$cloudProject['cloud_provider_code']], $params, true, true);

$context
    ->httpResponse()
    ->send();

