<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2026
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

use equal\http\HttpRequest;
use infra\Access;
use infra\cloud\CloudProject;
use infra\cloud\Region;
use infra\server\IpAddress;
use infra\server\Server;

[$params, $providers] = eQual::announce([
    'description'   => 'Synchronize an OVHcloud server identifier and IP addresses after provisioning.',
    'params'        => [
        'server_id' => [
            'type'              => 'many2one',
            'foreign_object'    => 'infra\server\Server',
            'description'       => 'Identifier of the local server to synchronize.',
            'required'          => true
        ],
        'operation_id' => [
            'type'              => 'string',
            'description'       => 'Identifier of the OVHcloud instance creation operation. Required when the server has no external identifier yet.'
        ],
        'attempt' => [
            'type'              => 'integer',
            'description'       => 'Current synchronization attempt.',
            'min'               => 1,
            'max'               => 6,
            'default'           => 1
        ],
        'retry_on_failure' => [
            'type'              => 'boolean',
            'description'       => 'Schedule another synchronization attempt when the current attempt fails.',
            'default'           => false
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
    'providers'     => ['context', 'cron']
]);

/**
 * @var \equal\php\Context      $context
 * @var \equal\cron\Scheduler   $cron
 */
['context' => $context, 'cron' => $cron] = $providers;

/**
 * Methods
 */

$getOvhTimestamp = function(): int {
    $request = new HttpRequest('GET https://eu.api.ovh.com/1.0/auth/time');
    $request->header('Accept', 'application/json');

    $response = $request->send();
    if($response->getStatusCode() !== 200) {
        throw new Exception('failed_retrieving_ovh_time', EQ_ERROR_UNKNOWN);
    }

    $body = $response->body();
    if(is_array($body) && isset($body['value'])) {
        $body = $body['value'];
    }

    if(!is_numeric($body)) {
        throw new Exception('invalid_ovh_time_response', EQ_ERROR_UNKNOWN);
    }

    return (int) $body;
};

$getOvhResource = function(array $api_credentials, string $url, array $query = []) use($getOvhTimestamp): array {
    $method = 'GET';
    $effective_url = $url.'?'.http_build_query($query);
    $timestamp = $getOvhTimestamp();
    $fingerprint = $api_credentials['application_secret']
        . '+'
        . $api_credentials['consumer_key']
        . '+'
        . $method
        . '+'
        . $effective_url
        . '++'
        . $timestamp;

    $request = (new HttpRequest("$method $url"))
        ->header('Accept', 'application/json')
        ->header('X-Ovh-Application', $api_credentials['application_key'])
        ->header('X-Ovh-Consumer', $api_credentials['consumer_key'])
        ->header('X-Ovh-Timestamp', (string) $timestamp)
        ->header('X-Ovh-Signature', '$1$'.sha1($fingerprint))
        ->body($query);

    $response = $request->send();
    if($response->getStatusCode() !== 200) {
        trigger_error('APP::Unable to retrieve an OVHcloud server provisioning resource.', EQ_REPORT_WARNING);
        throw new Exception('failed_retrieving_ovh_server', EQ_ERROR_UNKNOWN);
    }

    $body = $response->body();
    if(isset($body['value']) && is_array($body['value'])) {
        $body = $body['value'];
    }

    if(!is_array($body)) {
        throw new Exception('invalid_ovh_server_response', EQ_ERROR_UNKNOWN);
    }

    return $body;
};

$scheduleRetry = function() use($cron, $params): void {
    if($params['attempt'] >= 6) {
        throw new Exception('ovh_server_sync_timeout', EQ_ERROR_UNKNOWN);
    }

    $retry_params = [
        'server_id'        => $params['server_id'],
        'attempt'          => $params['attempt'] + 1,
        'retry_on_failure' => $params['retry_on_failure']
    ];
    if(!empty($params['operation_id'])) {
        $retry_params['operation_id'] = $params['operation_id'];
    }

    $cron->schedule(
        "infra.ovh-server.{$params['server_id']}.sync-provisioning",
        strtotime('+5 minutes'),
        'infra_cloud_ovh_sync-server',
        $retry_params
    );
};

$findResourceId = function(array $operation) use(&$findResourceId): ?string {
    if(!empty($operation['resourceId'])) {
        return $operation['resourceId'];
    }

    foreach($operation['subOperations'] ?? [] as $sub_operation) {
        if(!is_array($sub_operation)) {
            continue;
        }

        $resource_id = $findResourceId($sub_operation);
        if($resource_id !== null) {
            return $resource_id;
        }
    }

    return null;
};

/**
 * Action
 */

$server = Server::id($params['server_id'])
    ->read([
        'cloud_project_id',
        'region_id',
        'deployment_zone_id' => [
            'code',
            'type'
        ],
        'extref_server_id',
        'ip_address_ids' => [
            'ip_v4',
            'ip_v6',
            'visibility',
            'exposure'
        ],
        'accesses_ids' => [
            '@domain' => ['port', '=', '8000'],
            'host'
        ]
    ])
    ->first();

if(!$server) {
    throw new Exception('unknown_server', EQ_ERROR_UNKNOWN_OBJECT);
}

$cloud_project = CloudProject::id($server['cloud_project_id'])
    ->read([
        'extref_project_id',
        'api_credentials',
        'cloud_provider_id' => ['code']
    ])
    ->first();

if(!$cloud_project || $cloud_project['cloud_provider_id']['code'] !== 'ovh') {
    throw new Exception('wrong_cloud_provider', EQ_ERROR_INVALID_PARAM);
}

$region = Region::id($server['region_id'])
    ->read(['code'])
    ->first();

if(!$region) {
    throw new Exception('unknown_region', EQ_ERROR_UNKNOWN_OBJECT);
}

$ovh_region_code = $region['code'];
if(
    $server['deployment_zone_id']
    && $server['deployment_zone_id']['type'] === 'provider_region'
) {
    $ovh_region_code = $server['deployment_zone_id']['code'];
}

$api_credentials = is_array($cloud_project['api_credentials'])
    ? $cloud_project['api_credentials']
    : json_decode($cloud_project['api_credentials'], true);

if(
    !is_array($api_credentials)
    || empty($api_credentials['application_key'])
    || empty($api_credentials['application_secret'])
    || empty($api_credentials['consumer_key'])
) {
    throw new Exception('invalid_api_credentials', EQ_ERROR_INVALID_PARAM);
}

$project_id = rawurlencode($cloud_project['extref_project_id']);
$base_url = "https://eu.api.ovh.com/1.0/cloud/project/$project_id";
$instance_id = $server['extref_server_id'];
if(empty($instance_id) && empty($params['operation_id'])) {
    throw new Exception('missing_operation_id', EQ_ERROR_MISSING_PARAM);
}

try {
    if(empty($instance_id)) {
        $operation = $getOvhResource(
            $api_credentials,
            "$base_url/operation/".rawurlencode($params['operation_id'])
        );

        $operation_status = $operation['status'] ?? null;
        if(in_array($operation_status, ['created', 'in-progress', 'unknown'], true)) {
            $scheduleRetry();
            $context
                ->httpResponse()
                ->status(202)
                ->body(['status' => 'rescheduled', 'attempt' => $params['attempt'] + 1])
                ->send();
            return;
        }

        if($operation_status !== 'completed') {
            throw new Exception('ovh_server_creation_failed', EQ_ERROR_UNKNOWN);
        }

        $instance_id = $findResourceId($operation);
        if($instance_id === null) {
            throw new Exception('ovh_server_creation_failed', EQ_ERROR_UNKNOWN);
        }

        Server::id($server['id'])
            ->update([
                'extref_server_id'  => $instance_id,
                'up'                => true
            ]);
    }

    $region_code = rawurlencode($ovh_region_code);
    $instance = $getOvhResource(
        $api_credentials,
        "$base_url/region/$region_code/instance/".rawurlencode($instance_id),
        ['withNetworks' => 'true']
    );

    $existing_ip_addresses = [];
    foreach($server['ip_address_ids'] as $ip_address) {
        foreach(['ip_v4', 'ip_v6'] as $ip_field) {
            if(empty($ip_address[$ip_field])) {
                continue;
            }

            $normalized_ip = explode('/', $ip_address[$ip_field])[0];
            $existing_ip_addresses[$normalized_ip] = [
                'id'         => $ip_address['id'],
                'visibility' => $ip_address['visibility'],
                'exposure'   => $ip_address['exposure']
            ];
        }
    }

    $found_address_types = [];
    $private_ip_v4 = null;
    foreach($instance['addresses'] ?? [] as $address) {
        $address_type = $address['type'] ?? null;
        if(!in_array($address_type, ['public', 'private'], true)) {
            continue;
        }

        $ip = trim($address['ip'] ?? '');
        $normalized_ip = explode('/', $ip)[0];
        if(filter_var($normalized_ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $ip_field = 'ip_v4';
        }
        elseif(filter_var($normalized_ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            $ip_field = 'ip_v6';
        }
        else {
            trigger_error('APP::Ignoring an invalid IP address returned by OVHcloud.', EQ_REPORT_WARNING);
            continue;
        }

        if($address_type === 'private' && $ip_field === 'ip_v4') {
            $private_ip_v4 = $normalized_ip;
        }

        $ip_values = [
            'visibility' => $address_type,
            'exposure'   => $address_type
        ];
        if(isset($existing_ip_addresses[$normalized_ip])) {
            $existing_ip_address = $existing_ip_addresses[$normalized_ip];
            $updates = [];
            foreach($ip_values as $field => $value) {
                if($existing_ip_address[$field] !== $value) {
                    $updates[$field] = $value;
                }
            }
            if($updates) {
                IpAddress::id($existing_ip_address['id'])->update($updates);
            }
        }
        else {
            $created_ip_address = IpAddress::create(array_merge(
                [
                    'server_id' => $server['id'],
                    $ip_field   => $ip
                ],
                $ip_values
            ))
                ->first();

            $existing_ip_addresses[$normalized_ip] = array_merge(
                ['id' => $created_ip_address['id']],
                $ip_values
            );
        }

        $found_address_types[$address_type] = true;
    }

    if($private_ip_v4 !== null) {
        foreach($server['accesses_ids'] as $access) {
            if($access['host'] !== $private_ip_v4) {
                Access::id($access['id'])->update(['host' => $private_ip_v4]);
            }
        }
    }

    if(!isset($found_address_types['public'], $found_address_types['private'])) {
        $scheduleRetry();
        $context
            ->httpResponse()
            ->status(202)
            ->body(['status' => 'rescheduled', 'attempt' => $params['attempt'] + 1])
            ->send();
        return;
    }
}
catch(Throwable $throwable) {
    if(!$params['retry_on_failure']) {
        throw $throwable;
    }

    $scheduleRetry();
    trigger_error('APP::OVHcloud server synchronization failed; another attempt was scheduled.', EQ_REPORT_WARNING);
    $context
        ->httpResponse()
        ->status(202)
        ->body(['status' => 'rescheduled', 'attempt' => $params['attempt'] + 1])
        ->send();
    return;
}

$context
    ->httpResponse()
    ->status(204)
    ->send();
