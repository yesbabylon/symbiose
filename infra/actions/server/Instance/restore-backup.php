<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2026
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

use equal\http\HttpRequest;
use infra\Access;
use infra\server\Backup;

[$params, $providers] = eQual::announce([
    'description'   => 'Restore an instance from a backup hosted by its server backup host.',
    'help'          => 'Imports the selected backup from the configured backup host and restores the instance from it.',
    'params'        => [
        'id' => [
            'type'              => 'many2one',
            'foreign_object'    => 'infra\server\Instance',
            'description'       => 'Identifier of the instance to restore.',
            'default'           => 0
        ],
        'backup_id' => [
            'type'              => 'many2one',
            'foreign_object'    => 'infra\server\Backup',
            'description'       => 'Backup to use for restoring the instance.',
            'domain'            => [
                ['server_type', '=', 'k2'],
                ['cloud_project_id', '=', 'object.cloud_project_id']
            ],
            'required'          => true
        ],
        'passphrase' => [
            'type'              => 'string',
            'usage'             => 'password',
            'description'       => 'Passphrase of the B2 host GPG key when restoring an encrypted backup.',
            'default'           => ''
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
    'cloud_project_id',
    'server_id' => ['server_type']
]);

if($self->count() === 0) {
    throw new Exception('unknown_instance', EQ_ERROR_UNKNOWN_OBJECT);
}
if($self->count() !== 1) {
    throw new Exception('multiple_instances_not_allowed', EQ_ERROR_INVALID_PARAM);
}

$instance = $self->first();

if(!$instance['server_id']) {
    throw new Exception('missing_server', EQ_ERROR_INVALID_CONFIG);
}

if($instance['server_id']['server_type'] !== 'b2') {
    throw new Exception('invalid_server_type', EQ_ERROR_NOT_ALLOWED);
}
if(empty($instance['cloud_project_id'])) {
    throw new Exception('missing_cloud_project', EQ_ERROR_INVALID_CONFIG);
}

if($params['backup_id'] <= 0) {
    throw new Exception('missing_backup', EQ_ERROR_MISSING_PARAM);
}

$backup = Backup::id($params['backup_id'])
    ->read(['name', 'server_type', 'cloud_project_id'])
    ->first();

if(!$backup) {
    throw new Exception('unknown_backup', EQ_ERROR_UNKNOWN_OBJECT);
}

if($backup['server_type'] !== 'k2') {
    throw new Exception('backup_not_on_k2_server', EQ_ERROR_NOT_ALLOWED);
}
if((int) $backup['cloud_project_id'] !== (int) $instance['cloud_project_id']) {
    throw new Exception('backup_not_in_cloud_project', EQ_ERROR_NOT_ALLOWED);
}

$pattern = '/^'.preg_quote($instance['name'], '/').'_(\d{13}_\d+)\.tar(\.gpg)?$/';
if(preg_match($pattern, $backup['name'], $matches) !== 1) {
    throw new Exception('invalid_backup_filename', EQ_ERROR_INVALID_PARAM);
}

$backup_reference = $matches[1];
$encrypted = !empty($matches[2]);
if($encrypted && $params['passphrase'] === '') {
    throw new Exception('missing_backup_passphrase', EQ_ERROR_MISSING_PARAM);
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

$sendB2Request = function(
    string $route,
    array $payload,
    string $error_key
) use ($management_access, $instance): array {
    $request = new HttpRequest(
        'POST '.rtrim($management_access['url'], '/').$route,
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
        trigger_error(
            "APP::B2 request to {$route} failed for instance {$instance['id']}: {$e->getMessage()}",
            EQ_REPORT_ERROR
        );
        throw new Exception($error_key, EQ_ERROR_UNKNOWN);
    }

    $status = $response->getStatusCode();
    if($status < 200 || $status >= 300) {
        trigger_error(
            "APP::B2 request to {$route} for instance {$instance['id']} failed with HTTP status {$status}.",
            EQ_REPORT_ERROR
        );
        throw new Exception($error_key, EQ_ERROR_UNKNOWN);
    }

    $result = $response->body();
    if(!is_array($result) || empty($result['result']) || !is_string($result['result'])) {
        trigger_error(
            "APP::B2 request to {$route} for instance {$instance['id']} returned an invalid response.",
            EQ_REPORT_ERROR
        );
        throw new Exception($error_key, EQ_ERROR_UNKNOWN);
    }

    return $result;
};

$sendB2Request(
    '/instance/import-backup',
    [
        'instance'  => $instance['name'],
        'backup_id' => $backup_reference
    ],
    'b2_instance_backup_import_failed'
);

$restore_payload = [
    'instance'  => $instance['name'],
    'backup_id' => $backup_reference
];
if($encrypted) {
    $restore_payload['passphrase'] = $params['passphrase'];
}

$result = $sendB2Request(
    '/instance/restore',
    $restore_payload,
    'b2_instance_restore_failed'
);

$context
    ->httpResponse()
    ->status(200)
    ->body($result)
    ->send();
