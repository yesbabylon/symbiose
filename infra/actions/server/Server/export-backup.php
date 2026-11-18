<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2026
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

use equal\http\HttpRequest;
use infra\Access;
use infra\server\Backup;
use infra\server\Instance;
use infra\server\Server;

[$params, $providers] = eQual::announce([
    'description'   => 'Export an existing B2 instance backup to the configured K2 backup host.',
    'params'        => [
        'id' => [
            'type'              => 'many2one',
            'foreign_object'    => 'infra\server\Server',
            'description'       => 'Identifier of the B2 server exporting the backup.',
            'default'           => 0
        ],
        'backup_id' => [
            'type'              => 'many2one',
            'foreign_object'    => 'infra\server\Backup',
            'description'       => 'Existing backup to export to the configured K2 backup host.',
            'domain'            => ['server_id', '=', 'object.id'],
            'required'          => true
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
    throw new Exception('missing_server', EQ_ERROR_MISSING_PARAM);
}

$self->read(['server_type', 'cloud_project_id']);
if($self->count() === 0) {
    throw new Exception('unknown_server', EQ_ERROR_UNKNOWN_OBJECT);
}
if($self->count() !== 1) {
    throw new Exception('multiple_servers_not_allowed', EQ_ERROR_INVALID_PARAM);
}

$server = $self->first();
if($server['server_type'] !== 'b2') {
    throw new Exception('invalid_server_type', EQ_ERROR_NOT_ALLOWED);
}
if(empty($server['cloud_project_id'])) {
    throw new Exception('missing_cloud_project', EQ_ERROR_INVALID_CONFIG);
}

$backup_server = Server::search([
        ['cloud_project_id', '=', $server['cloud_project_id']],
        ['server_type', '=', 'k2']
    ])
    ->read(['id'])
    ->first();

if(!$backup_server) {
    throw new Exception('missing_project_backup_server', EQ_ERROR_INVALID_CONFIG);
}

if($params['backup_id'] <= 0) {
    throw new Exception('missing_backup', EQ_ERROR_MISSING_PARAM);
}

$backup = Backup::id($params['backup_id'])
    ->read(['name', 'server_id'])
    ->first();

if(!$backup) {
    throw new Exception('unknown_backup', EQ_ERROR_UNKNOWN_OBJECT);
}
if((int) $backup['server_id'] !== (int) $server['id']) {
    throw new Exception('backup_not_on_server', EQ_ERROR_NOT_ALLOWED);
}

$pattern = '/^([a-zA-Z0-9.-]+)_(\d{13}_\d+)\.tar(?:\.gpg)?$/';
if(preg_match($pattern, $backup['name'], $matches) !== 1) {
    throw new Exception('invalid_backup_filename', EQ_ERROR_INVALID_PARAM);
}

$instance = Instance::search([
        ['server_id', '=', $server['id']],
        ['name', '=', $matches[1]]
    ])
    ->read(['name'])
    ->first();

if(!$instance) {
    throw new Exception('unknown_instance', EQ_ERROR_UNKNOWN_OBJECT);
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

$request = new HttpRequest(
    'POST '.rtrim($management_access['url'], '/').'/instance/export-backup',
    [],
    json_encode([
        'instance'  => $instance['name'],
        'backup_id' => $matches[2]
    ], JSON_THROW_ON_ERROR)
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
        "APP::Unable to export backup {$backup['id']} from server {$server['id']}: {$e->getMessage()}",
        EQ_REPORT_ERROR
    );
    throw new Exception('backup_export_failed', EQ_ERROR_UNKNOWN);
}

$status = $response->getStatusCode();
if($status < 200 || $status >= 300) {
    trigger_error(
        "APP::Unable to export backup {$backup['id']} from server {$server['id']}: HTTP status {$status}.",
        EQ_REPORT_ERROR
    );
    throw new Exception('backup_export_failed', EQ_ERROR_UNKNOWN);
}

$result = $response->body();

if(
    !is_array($result)
    || empty($result['result'])
    || !is_string($result['result'])
) {
    trigger_error(
        "APP::B2 export-backup returned an invalid response for backup {$backup['id']}.",
        EQ_REPORT_ERROR
    );
    throw new Exception('backup_export_failed', EQ_ERROR_UNKNOWN);
}

$context
    ->httpResponse()
    ->status(200)
    ->body($result)
    ->send();
