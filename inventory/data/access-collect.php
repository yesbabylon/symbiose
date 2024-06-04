<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2024
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

use equal\orm\Domain;

list($params, $providers) = eQual::announce([
    'description'   => 'Advanced search for Accesses: returns a collection of Reports according to extra parameters.',
    'extends'       => 'core_model_collect',
    'params'        => [

        'entity' =>  [
            'description'   => 'name',
            'type'          => 'string',
            'default'       => 'inventory\Access'
        ],

        'username' => [
            'type'              => 'string',
            'description'       => 'username of the account related to this access',
        ],

        'access_type' => [
            'type'              => 'string',
            'selection'         => ['all','http', 'https', 'ssh', 'ftp', 'sftp', 'pop', 'smtp', 'git', 'docker'],
            'description'       => 'Type of the access',
        ],

        'host' => [
            'type'              => 'string',
            'description'       => 'IP address or hostname¨of the server',
        ],

        'url' => [
            'type'       => 'string',
            'description'       => 'The URL to access.',

        ],

        'server_id' => [
            'type'              => 'many2one',
            'foreign_object'    => 'inventory\server\Server',
            'description'       => 'Server to which the access belongs .'
        ],

        'software_id' => [
            'type'              => 'many2one',
            'foreign_object'    => 'inventory\Software',
            'description'       => 'Software to which the access belongs.'
        ],

        'instance_id' => [
            'type'              => 'many2one',
            'foreign_object'    => 'inventory\server\Instance',
            'description'       => 'Instance to which the access belongs.'
        ],

        'service_id' => [
            'type'              => 'many2one',
            'foreign_object'    => 'inventory\service\Service',
            'description'       => 'Service to which the access belongs.'
        ]
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
$context = $providers['context'];

//   Add conditions to the domain to consider advanced parameters
$domain = [];

if(isset($params['username']) && strlen($params['username']) > 0 ) {
    $domain = ['username', 'ilike','%'.$params['username'].'%'];
}

if(isset($params['access_type']) && strlen($params['access_type']) > 0 && $params['access_type']!= 'all') {
    $domain = ['access_type', '=', $params['access_type']];
}

if(isset($params['host']) && strlen($params['host']) > 0 ) {
    $domain = ['host', 'ilike','%'.$params['host'].'%'];
}

if(isset($params['url']) && strlen($params['url']) > 0 ) {
    $domain = ['url', 'ilike','%'.$params['url'].'%'];
}

if(isset($params['server_id']) && $params['server_id'] > 0) {
    $domain = ['server_id', '=', $params['server_id']];
}

if(isset($params['software_id']) && $params['software_id'] > 0) {
    $domain = ['software_id', '=', $params['software_id']];
}

if(isset($params['instance_id']) && $params['instance_id'] > 0) {
    $domain = ['instance_id', '=', $params['instance_id']];
}

if(isset($params['service_id']) && $params['service_id'] > 0) {
    $domain = ['service_id', '=', $params['service_id']];
}

$params['domain'] = (new Domain($params['domain']))
    ->merge(new Domain($domain))
    ->toArray();

$result = eQual::run('get', 'model_collect', $params, true);

$context->httpResponse()
        ->body($result)
        ->send();
