<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2026
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

use equal\data\DataGenerator;
use equal\http\HttpRequest;
use infra\Access;
use infra\server\Instance;
use infra\server\Server;
use inventory\Product;

[$params, $providers] = eQual::announce([
    'description'   => 'Create a new instance on a B2 host.',
    'params'        => [
        'id' => [
            'type'              => 'many2one',
            'foreign_object'    => 'infra\server\Server',
            'description'       => 'Identifier of the targeted B2 host.',
            'required'          => true
        ],
        'product_id' => [
            'type'              => 'many2one',
            'foreign_object'    => 'inventory\Product',
            'description'       => 'Product to which the new instance belongs.',
            'required'          => true
        ],
        'instance_name' => [
            'type'              => 'string',
            'description'       => 'FQDN of the new instance. The B2 API limits it to 32 characters.',
            'required'          => true
        ],
        'password' => [
            'type'              => 'string',
            'description'       => 'Password used for the instance accesses. Must contain between 8 and 70 characters.',
            'required'          => true
        ],
        'instance_type' => [
            'type'              => 'string',
            'description'       => 'Business environment of the local instance record.',
            'selection'         => ['dev', 'staging', 'prod', 'replica'],
            'default'           => 'dev'
        ],
        'b2_instance_type' => [
            'type'              => 'string',
            'description'       => 'B2 stack template to install.',
            'selection'         => ['equal', 'wordpress', 'equalpress', 'symbiose', 'fmt', 'mkdocs'],
            'default'           => 'equal'
        ],
        'b2_instance_subtype' => [
            'type'              => 'string',
            'description'       => 'Used only when required by the B2 instance type.',
            'help'              => '{ "fmt": ["global", "agency"] }'
        ],
        'cipher_key' => [
            'type'              => 'string',
            'description'       => 'Optional 32-character cipher key. B2 generates one when omitted.',
            'default'           => ''
        ],
        'https_redirect' => [
            'type'              => 'string',
            'description'       => 'Whether HTTP requests must be redirected to HTTPS.',
            'selection'         => ['redirect', 'noredirect'],
            'default'           => 'noredirect'
        ],
        'mem_limit' => [
            'type'              => 'string',
            'description'       => 'Memory limit of the application container.',
            'default'           => '1000M'
        ],
        'cpu_limit' => [
            'type'              => 'string',
            'description'       => 'CPU limit of the application container.',
            'default'           => '1'
        ],
        'eq_mem_free_limit' => [
            'type'              => 'string',
            'description'       => 'Memory reserve passed to eQual-compatible templates.',
            'default'           => '256M'
        ],
        'init' => [
            'type'              => 'boolean',
            'description'       => 'Run the selected template initialization script after creation.',
            'default'           => false
        ],
        'create_dns_record' => [
            'type'              => 'boolean',
            'description'       => 'Create an OVH A record for the new instance.',
            'default'           => false
        ],
        'dns_zone' => [
            'type'              => 'string',
            'description'       => 'Managed OVH DNS zone. Required when DNS record creation is requested.',
            'default'           => ''
        ],
        'dns_ttl' => [
            'type'              => 'integer',
            'description'       => 'TTL of the DNS record in seconds.',
            'min'               => 60,
            'default'           => 3600
        ]
    ],
    'access'        => [
        'visibility' => 'protected'
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

$instance_name = strtolower(rtrim(trim($params['instance_name']), '.'));
if(
    strlen($instance_name) > 32
    || preg_match('/^(?!-)(?:[a-z0-9-]{1,63}\.)+[a-z]{2,}$/', $instance_name) !== 1
) {
    throw new Exception('invalid_instance_name', EQ_ERROR_INVALID_PARAM);
}

if(strlen($params['password']) < 8 || strlen($params['password']) > 70) {
    throw new Exception('invalid_instance_password', EQ_ERROR_INVALID_PARAM);
}

$allowed_subtypes = [
    'fmt' => ['global', 'agency']
];
if(!empty($allowed_subtypes[$params['b2_instance_type']]) && !in_array($params['b2_instance_subtype'], $allowed_subtypes[$params['b2_instance_type']])) {
    throw new Exception('invalid_b2_instance_subtype', EQ_ERROR_INVALID_PARAM);
}

if($params['cipher_key'] !== '' && strlen($params['cipher_key']) !== 32) {
    throw new Exception('invalid_cipher_key', EQ_ERROR_INVALID_PARAM);
}

if(preg_match('/^\d+[MG]$/i', $params['mem_limit']) !== 1) {
    throw new Exception('invalid_mem_limit', EQ_ERROR_INVALID_PARAM);
}

if(!is_numeric($params['cpu_limit']) || (float) $params['cpu_limit'] <= 0) {
    throw new Exception('invalid_cpu_limit', EQ_ERROR_INVALID_PARAM);
}

if(preg_match('/^\d+[MG]$/i', $params['eq_mem_free_limit']) !== 1) {
    throw new Exception('invalid_eq_mem_free_limit', EQ_ERROR_INVALID_PARAM);
}

if($params['create_dns_record'] && trim($params['dns_zone']) === '') {
    throw new Exception('missing_dns_zone', EQ_ERROR_MISSING_PARAM);
}

$server = Server::id($params['id'])
    ->read(['server_type'])
    ->first();

if(!$server) {
    throw new Exception('unknown_server', EQ_ERROR_UNKNOWN_OBJECT);
}

if($server['server_type'] !== 'b2') {
    throw new Exception('invalid_server_type', EQ_ERROR_NOT_ALLOWED);
}

$product = Product::id($params['product_id'])
    ->read(['id'])
    ->first();

if(!$product) {
    throw new Exception('unknown_product', EQ_ERROR_UNKNOWN_OBJECT);
}

if(Instance::search(['name', '=', $instance_name])->count() > 0) {
    throw new Exception('instance_already_exists', EQ_ERROR_CONFLICT_OBJECT);
}

$management_access = Access::search([
        ['server_id', '=', $server['id']],
        ['access_type', 'in', ['http', 'https']],
        ['port', '=', '8000']
    ])
    ->read(['url', 'username', 'password'])
    ->first();

if(!$management_access || empty($management_access['url'])) {
    throw new Exception('missing_b2_host_access', EQ_ERROR_INVALID_CONFIG);
}

$instance_uuid = DataGenerator::uuid();
$create_params = [
    'USERNAME'          => $instance_name,
    'PASSWORD'          => $params['password'],
    'INSTANCE_TYPE'     => $params['b2_instance_type'],
    'HTTPS_REDIRECT'    => $params['https_redirect'],
    'MEM_LIMIT'         => strtoupper($params['mem_limit']),
    'CPU_LIMIT'         => $params['cpu_limit'],
    'EQ_MEM_FREE_LIMIT' => strtoupper($params['eq_mem_free_limit']),
    'INIT'              => $params['init']
];

if(!empty($params['b2_instance_subtype'])) {
    $create_params['INSTANCE_SUBTYPE'] = $params['b2_instance_subtype'];
}

if(!empty($params['cipher_key'])) {
    $create_params['CIPHER_KEY'] = $params['cipher_key'];
}

$request = new HttpRequest(
    "POST {$management_access['url']}/instance/create",
    [],
    json_encode($create_params, JSON_THROW_ON_ERROR)
);

$credentials = base64_encode(($management_access['username'] ?: 'root').":{$management_access['password']}");
$request
    ->header('Content-Type', 'application/json')
    ->header('Authorization', "Basic $credentials");

try {
    $response = $request->send();
}
catch(Throwable $e) {
    trigger_error("APP::B2 instance creation request failed: {$e->getMessage()}", EQ_REPORT_ERROR);
    throw new Exception('b2_instance_creation_failed', EQ_ERROR_UNKNOWN);
}

$status = $response->getStatusCode();
if($status < 200 || $status >= 300) {
    trigger_error("APP::B2 instance creation failed with HTTP status $status.", EQ_REPORT_ERROR);
    throw new Exception('b2_instance_creation_failed', EQ_ERROR_INVALID_PARAM);
}

$scheme = $params['https_redirect'] === 'redirect' ? 'https' : 'http';
$instance = Instance::create([
    'name'              => $instance_name,
    'uuid'              => $instance_uuid,
    'instance_type'     => $params['instance_type'],
    'b2_instance_type'  => $params['b2_instance_type'],
    'url'               => "$scheme://$instance_name",
    'product_id'        => $product['id'],
    'server_id'         => $server['id']
])
    ->first();

Access::create([
    'description'   => 'Created with the B2 instance.',
    'access_type'   => 'sftp',
    'host'          => $instance_name,
    'port'          => '22',
    'username'      => $instance_name,
    'password'      => $params['password'],
    'instance_id'   => $instance['id']
]);

$dns_record_created = false;
$dns_record_error = null;
if($params['create_dns_record']) {
    try {
        eQual::run('do', 'infra_server_Instance_create-dns-record', [
            'id'    => $instance['id'],
            'zone'  => $params['dns_zone'],
            'ttl'   => $params['dns_ttl']
        ]);
        $dns_record_created = true;
    }
    catch(Throwable $e) {
        trigger_error("APP::Unable to create DNS record for instance {$instance['id']}: {$e->getMessage()}", EQ_REPORT_WARNING);
        $dns_record_error = 'dns_record_creation_failed';
    }
}

$result = [
    'id'                 => $instance['id'],
    'dns_record_created' => $dns_record_created
];
if($dns_record_error !== null) {
    $result['dns_record_error'] = $dns_record_error;
}

$context
    ->httpResponse()
    ->status(201)
    ->body($result)
    ->send();
