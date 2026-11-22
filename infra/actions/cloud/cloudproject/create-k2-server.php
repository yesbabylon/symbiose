<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2026
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

use infra\cloud\CloudProject;

[$params, $providers] = eQual::announce([
    'description'   => 'Create a new k2 server in a specific cloud project.',
    'params'        => [
        'id' => [
            'type'              => 'many2one',
            'foreign_object'    => 'infra\cloud\CloudProject',
            'description'       => 'Identifier of the targeted cloud project.',
            'required'          => true
        ],
        'name' => [
            'type'              => 'string',
            'description'       => 'The name of the new k2 host server.',
            'required'          => true
        ],
        'region_id' => [
            'type'              => 'many2one',
            'foreign_object'    => 'infra\cloud\Region',
            'description'       => 'Identifier of the region where the server should be added.',
            'required'          => true
        ],
        'deployment_zone_id' => [
            'type'              => 'many2one',
            'foreign_object'    => 'infra\cloud\DeploymentZone',
            'description'       => 'Identifier of the deployment target where the server should be added.',
            'required'          => true,
            'domain'            => [
                ['region_id', '=', 'object.region_id']
            ]
        ],
        'flavor' => [
            'type'              => 'string',
            'description'       => 'The material configuration of the server.'
        ],
        'image' => [
            'type'              => 'string',
            'description'       => 'The os image to use.'
        ],
        'billing_period' => [
            'type'              => 'string',
            'description'       => 'The billing period of the server to create.',
            'selection'         => [
                'monthly',
                'hourly'
            ],
            'default'           => 'monthly'
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

$cloud_project = CloudProject::id($params['id'])
    ->read(['cloud_provider_code'])
    ->first();

if(!$cloud_project) {
    throw new Exception('unknown_cloud_project', EQ_ERROR_UNKNOWN_OBJECT);
}

$controllers = [
    'ovh' => 'infra_cloud_cloudproject_ovh_create-k2-server'
];

if(!isset($controllers[$cloud_project['cloud_provider_code']])) {
    throw new Exception('wrong_cloud_provider', EQ_ERROR_INVALID_PARAM);
}

eQual::run('do', $controllers[$cloud_project['cloud_provider_code']], $params, true, true);

$context
    ->httpResponse()
    ->send();

