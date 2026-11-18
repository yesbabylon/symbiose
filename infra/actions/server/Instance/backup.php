<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2026
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

use equal\http\HttpRequest;
use infra\Access;

[$params, $providers] = eQual::announce([
    'description'   => 'Create a backup of an instance and send it to the configured backup host by default.',
    'params'        => [
        'id' => [
            'type'              => 'many2one',
            'foreign_object'    => 'infra\server\Instance',
            'description'       => 'Identifier of the instance to back up.',
            'default'           => 0
        ],
        'ids' => [
            'type'              => 'one2many',
            'foreign_object'    => 'infra\server\Instance',
            'description'       => 'Identifiers of the instances to back up.',
            'default'           => []
        ],
        'encrypt' => [
            'type'              => 'boolean',
            'description'       => 'Encrypt the backup with the B2 host GPG key.',
            'default'           => true
        ],
        'send_to_backup_host' => [
            'type'              => 'boolean',
            'description'       => 'Send the created backup to the configured backup host.',
            'default'           => true
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
    'providers'     => ['context', 'self']
]);

/**
 * @var \equal\php\Context      $context
 * @var \equal\orm\Collection   $self
 */
['context' => $context, 'self' => $self] = $providers;

if(empty($params['ids']) && $params['id'] <= 0) {
    throw new Exception('missing_instance', EQ_ERROR_MISSING_PARAM);
}

$self->read([
    'name',
    'server_id' => ['server_type']
]);

if($self->count() === 0) {
    throw new Exception('unknown_instance', EQ_ERROR_UNKNOWN_OBJECT);
}

$map_management_accesses = [];
foreach($self as $id => $instance) {
    if(!$instance['server_id']) {
        throw new Exception('missing_server', EQ_ERROR_INVALID_CONFIG);
    }

    if($instance['server_id']['server_type'] !== 'b2') {
        throw new Exception('invalid_server_type', EQ_ERROR_NOT_ALLOWED);
    }

    $management_access = Access::search([
            ['server_id', '=', $instance['server_id']['id']],
            ['access_type', 'in', ['http', 'https']],
            ['port', '=', '8000']
        ])
        ->read(['url', 'username', 'password'])
        ->first();

    if(!$management_access || empty($management_access['url'])) {
        throw new Exception('missing_b2_host_access', EQ_ERROR_INVALID_CONFIG);
    }

    $map_management_accesses[$id] = $management_access;
}

$sendB2Request = function(
    $management_access,
    string $route,
    array $payload,
    int $instance_id,
    string $error_key
): array {
    $request = new HttpRequest(
        "POST {$management_access['url']}$route",
        [],
        json_encode($payload, JSON_THROW_ON_ERROR)
    );

    $credentials = base64_encode(($management_access['username'] ?: 'root').":{$management_access['password']}");
    $request
        ->header('Content-Type', 'application/json')
        ->header('Authorization', "Basic $credentials");

    try {
        $response = $request->send();
    }
    catch(Throwable $e) {
        trigger_error("APP::B2 request to $route failed for instance $instance_id: {$e->getMessage()}", EQ_REPORT_ERROR);
        throw new Exception($error_key, EQ_ERROR_UNKNOWN);
    }

    $status = $response->getStatusCode();
    if($status < 200 || $status >= 300) {
        trigger_error("APP::B2 request to $route for instance $instance_id failed with HTTP status $status.", EQ_REPORT_ERROR);
        throw new Exception($error_key, EQ_ERROR_UNKNOWN);
    }

    $result = $response->body();
    if(!is_array($result) || empty($result['result']) || !is_string($result['result'])) {
        trigger_error("APP::B2 request to $route for instance $instance_id returned an invalid response.", EQ_REPORT_ERROR);
        throw new Exception($error_key, EQ_ERROR_UNKNOWN);
    }

    return $result;
};

$result = [];
foreach($self as $id => $instance) {
    $management_access = $map_management_accesses[$id];

    $instance_result = $sendB2Request(
        $management_access,
        '/instance/backup',
        [
            'instance'  => $instance['name'],
            'encrypt'   => $params['encrypt']
        ],
        $instance['id'],
        'b2_instance_backup_failed'
    );
    $instance_result['sent_to_backup_host'] = false;


    if($params['send_to_backup_host']) {
        $pattern = '/^'.preg_quote($instance['name'], '/').'_(\d{13}_\d+)\.tar(?:\.gpg)?$/';
        if(preg_match($pattern, $instance_result['result'], $matches) !== 1) {
            trigger_error("APP::Unable to derive backup id for instance {$instance['id']} from B2 response.", EQ_REPORT_ERROR);
            throw new Exception('b2_instance_backup_export_failed', EQ_ERROR_UNKNOWN);
        }

        $sendB2Request(
            $management_access,
            '/instance/export-backup',
            [
                'instance'  => $instance['name'],
                'backup_id' => $matches[1]
            ],
            $instance['id'],
            'b2_instance_backup_export_failed'
        );
        $instance_result['sent_to_backup_host'] = true;
    }

    $result[$id] = $instance_result;
}

$context
    ->httpResponse()
    ->status(201)
    ->body($result)
    ->send();
