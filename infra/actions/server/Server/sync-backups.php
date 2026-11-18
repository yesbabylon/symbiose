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
    'description'   => 'Synchronizes the backups hosted by B2 and K2 servers.',
    'help'          => 'Fetches the backups hosted by each selected server, creates missing backup records, updates existing ones and removes records that no longer exist on the server.',
    'params'        => [
        'id' => [
            'type'              => 'many2one',
            'foreign_object'    => 'infra\server\Server',
            'description'       => 'Identifier of the targeted server.',
            'default'           => 0
        ],
        'ids' => [
            'type'              => 'one2many',
            'foreign_object'    => 'infra\server\Server',
            'description'       => 'Identifiers of the targeted servers.',
            'default'           => []
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

$self->read([
    'server_type',
    'backups_ids' => [
        'name',
        'backup_time'
    ]
]);

if($self->count() === 0) {
    throw new Exception('unknown_server', EQ_ERROR_UNKNOWN_OBJECT);
}

$map_management_accesses = [];
foreach($self as $id => $server) {
    if(!in_array($server['server_type'], ['b2', 'k2'], true)) {
        throw new Exception('invalid_backup_server_type', EQ_ERROR_NOT_ALLOWED);
    }

    $management_access = Access::search([
            ['server_id', '=', $id],
            ['access_type', 'in', ['http', 'https']],
            ['port', '=', '8000']
        ])
        ->read(['url', 'username', 'password'])
        ->first();

    if(!$management_access || empty($management_access['url'])) {
        throw new Exception('missing_backup_server_access', EQ_ERROR_INVALID_CONFIG);
    }

    $map_management_accesses[$id] = $management_access;
}

// Fetch and validate every response before changing local backup records.
$map_remote_backups = [];
foreach($self as $id => $server) {
    $management_access = $map_management_accesses[$id];
    $request = new HttpRequest('GET '.rtrim($management_access['url'], '/').'/backups');
    $credentials = base64_encode(($management_access['username'] ?: 'root').":{$management_access['password']}");
    $request->header('Authorization', "Basic $credentials");

    try {
        $response = $request->send();
    }
    catch(Throwable $e) {
        trigger_error(
            "APP::Unable to fetch backups from server {$id}: {$e->getMessage()}",
            EQ_REPORT_ERROR
        );
        throw new Exception('backup_sync_failed', EQ_ERROR_UNKNOWN);
    }

    $status = $response->getStatusCode();
    if($status < 200 || $status >= 300) {
        trigger_error(
            "APP::Unable to fetch backups from server {$id}: HTTP status {$status}.",
            EQ_REPORT_ERROR
        );
        throw new Exception('backup_sync_failed', EQ_ERROR_UNKNOWN);
    }

    $hosted_backups = $response->body();
    if(!is_array($hosted_backups)) {
        trigger_error("APP::Server {$id} returned an invalid backups response.", EQ_REPORT_ERROR);
        throw new Exception('invalid_backups_response', EQ_ERROR_UNKNOWN);
    }

    $remote_backups = [];
    foreach($hosted_backups as $instance_name => $instance_backups) {
        if(!is_string($instance_name) || $instance_name === '' || !is_array($instance_backups)) {
            trigger_error("APP::Server {$id} returned an invalid backups response.", EQ_REPORT_ERROR);
            throw new Exception('invalid_backups_response', EQ_ERROR_UNKNOWN);
        }

        $backup_entries = $instance_backups;
        if($server['server_type'] === 'b2') {
            if(!array_key_exists('export', $instance_backups) || !array_key_exists('import', $instance_backups)) {
                trigger_error("APP::B2 server {$id} returned an invalid backups response.", EQ_REPORT_ERROR);
                throw new Exception('invalid_backups_response', EQ_ERROR_UNKNOWN);
            }
            if(!is_array($instance_backups['export']) || !is_array($instance_backups['import'])) {
                trigger_error("APP::B2 server {$id} returned invalid import or export backup lists.", EQ_REPORT_ERROR);
                throw new Exception('invalid_backups_response', EQ_ERROR_UNKNOWN);
            }

            // Only imported archives can be restored by the B2 host.
            $backup_entries = $instance_backups['import'];
        }

        $backup_pattern = '/^'.preg_quote($instance_name, '/').'_(\d{8})(\d{5})_(?:\d+\.tar(?:\.gpg)?|stack\.tar\.gz)$/';
        $legacy_stack_pattern = '/^stack-'.preg_quote($instance_name, '/').'-(\d{8})-(\d{2})(\d{2})(\d{2})(?:-\d+)?\.tar\.gz$/';
        foreach($backup_entries as $backup_entry) {
            $backup_name = $backup_entry;
            if($server['server_type'] === 'b2' && is_array($backup_entry)) {
                $backup_name = $backup_entry['filename'] ?? null;
            }

            if(!is_string($backup_name)) {
                trigger_error("APP::Server {$id} returned an invalid backup filename.", EQ_REPORT_ERROR);
                throw new Exception('invalid_backups_response', EQ_ERROR_UNKNOWN);
            }

            $matches = [];
            if(preg_match($backup_pattern, $backup_name, $matches) === 1) {
                $date_value = $matches[1];
                $seconds = (int) $matches[2];
            }
            elseif(preg_match($legacy_stack_pattern, $backup_name, $matches) === 1) {
                $date_value = $matches[1];
                $hours = (int) $matches[2];
                $minutes = (int) $matches[3];
                $seconds_value = (int) $matches[4];
                if($hours >= 24 || $minutes >= 60 || $seconds_value >= 60) {
                    trigger_error("APP::Server {$id} returned an invalid backup timestamp.", EQ_REPORT_ERROR);
                    throw new Exception('invalid_backups_response', EQ_ERROR_UNKNOWN);
                }

                $seconds = ($hours * 3600) + ($minutes * 60) + $seconds_value;
            }
            else {
                trigger_error("APP::Server {$id} returned an invalid backup filename.", EQ_REPORT_ERROR);
                throw new Exception('invalid_backups_response', EQ_ERROR_UNKNOWN);
            }

            $date = DateTimeImmutable::createFromFormat(
                '!Ymd',
                $date_value,
                new DateTimeZone('UTC')
            );
            if(!$date || $date->format('Ymd') !== $date_value || $seconds >= 86400) {
                trigger_error("APP::Server {$id} returned an invalid backup timestamp.", EQ_REPORT_ERROR);
                throw new Exception('invalid_backups_response', EQ_ERROR_UNKNOWN);
            }

            $remote_backups[$backup_name] = $date->getTimestamp() + $seconds;
        }
    }

    $map_remote_backups[$id] = $remote_backups;
}

foreach($self as $id => $server) {
    $existing_backups = [];
    $stale_backup_ids = [];
    foreach($server['backups_ids'] as $backup) {
        if(isset($existing_backups[$backup['name']])) {
            $stale_backup_ids[] = $backup['id'];
            continue;
        }

        $existing_backups[$backup['name']] = $backup;
    }

    foreach($map_remote_backups[$id] as $backup_name => $backup_time) {
        if(isset($existing_backups[$backup_name])) {
            $backup = $existing_backups[$backup_name];
            if((int) $backup['backup_time'] !== $backup_time) {
                Backup::id($backup['id'])->update(['backup_time' => $backup_time]);
            }

            unset($existing_backups[$backup_name]);
            continue;
        }

        Backup::create([
            'name'          => $backup_name,
            'backup_time'   => $backup_time,
            'server_id'     => $id
        ]);
    }

    foreach($existing_backups as $backup) {
        $stale_backup_ids[] = $backup['id'];
    }

    if(!empty($stale_backup_ids)) {
        Backup::ids($stale_backup_ids)->delete(true);
    }
}

$context
    ->httpResponse()
    ->status(204)
    ->send();
