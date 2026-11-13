<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2026
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

use equal\http\HttpRequest;
use infra\cloud\CloudProject;
use infra\cloud\DeploymentZone;

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

$signOvhRequest = function(array $api_credentials, string $method, string $url, int $timestamp): string {
    $fingerprint = $api_credentials['application_secret']
        . '+'
        . $api_credentials['consumer_key']
        . '+'
        . strtoupper($method)
        . '+'
        . $url
        . '++'
        . $timestamp;

    return '$1$' . sha1($fingerprint);
};

$getOvhCollection = function(array $api_credentials, string $path, array $query = []) use($getOvhTimestamp, $signOvhRequest): array {
    $method = 'GET';
    $base_url = 'https://eu.api.ovh.com/1.0' . $path;
    $query_string = http_build_query($query);
    // HttpRequest::send() rebuilds every GET URL from its body, including the
    // trailing question mark when the body is empty. Sign that effective URL.
    $url = $base_url . '?' . $query_string;
    $timestamp = $getOvhTimestamp();
    $signature = $signOvhRequest($api_credentials, $method, $url, $timestamp);

    $request = (new HttpRequest("$method $base_url"))
        ->header('Accept', 'application/json')
        ->header('X-Ovh-Application', $api_credentials['application_key'])
        ->header('X-Ovh-Consumer', $api_credentials['consumer_key'])
        ->header('X-Ovh-Timestamp', (string) $timestamp)
        ->header('X-Ovh-Signature', $signature)
        ->body($query);

    $response = $request->send();
    if($response->getStatusCode() !== 200) {
        trigger_error(
            'APP::Unable to retrieve an OVHcloud provider catalog resource.',
            EQ_REPORT_WARNING
        );
        file_put_contents(QN_LOG_STORAGE_DIR.'/tmp.log', json_encode($response->body()).PHP_EOL, FILE_APPEND | LOCK_EX);
        throw new Exception('failed_retrieving_ovh_catalog', EQ_ERROR_UNKNOWN);
    }

    $body = $response->body();
    if(isset($body['value']) && is_array($body['value'])) {
        $body = $body['value'];
    }

    if(!is_array($body)) {
        throw new Exception('invalid_ovh_catalog_response', EQ_ERROR_UNKNOWN);
    }

    return $body;
};

/**
 * Action
 */

$cloud_project = CloudProject::id($params['id'])
    ->read([
        'extref_project_id',
        'api_credentials',
        'cloud_provider_id' => ['code']
    ])
    ->first();

if(!$cloud_project) {
    throw new Exception('unknown_cloud_project', EQ_ERROR_UNKNOWN_OBJECT);
}

if($cloud_project['cloud_provider_id']['code'] !== 'ovh') {
    throw new Exception('unsupported_catalog_provider', EQ_ERROR_INVALID_PARAM);
}

if(empty($cloud_project['extref_project_id'])) {
    throw new Exception('missing_extref_project_id', EQ_ERROR_INVALID_PARAM);
}

if(empty($cloud_project['api_credentials'])) {
    throw new Exception('missing_api_credentials', EQ_ERROR_INVALID_PARAM);
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
$base_path = "/cloud/project/$project_id";

$ovh_region_codes = $getOvhCollection($api_credentials, "$base_path/region");
$ovh_region_codes = array_fill_keys(
    array_filter($ovh_region_codes, fn($code) => is_string($code) && $code !== ''),
    true
);

$configured_region_codes = [];
$configured_deployment_zones = DeploymentZone::search([
    ['cloud_provider_id', '=', $cloud_project['cloud_provider_id']['id']]
])->read([
    'code',
    'type',
    'region_id' => ['code']
]);
foreach($configured_deployment_zones as $deployment_zone) {
    $region_code = $deployment_zone['type'] === 'provider_region'
        ? $deployment_zone['code']
        : $deployment_zone['region_id']['code'];

    if(isset($ovh_region_codes[$region_code])) {
        $configured_region_codes[$region_code][$deployment_zone['code']] = $deployment_zone['type'];
    }
}

if(empty($configured_region_codes)) {
    throw new Exception('missing_provider_catalog_regions', EQ_ERROR_INVALID_PARAM);
}

$flavors = $getOvhCollection($api_credentials, "$base_path/flavor");
$images = $getOvhCollection($api_credentials, "$base_path/image", ['osType' => 'linux']);
$ssh_keys = $getOvhCollection($api_credentials, "$base_path/sshkey");

$catalog = [
    'provider'  => 'ovh',
    'synced_at' => gmdate('c'),
    'regions'   => []
];

foreach(array_keys($configured_region_codes) as $region_code) {
    $region_info = $getOvhCollection(
        $api_credentials,
        "$base_path/region/".rawurlencode($region_code)
    );

    $reported_availability_zones = array_fill_keys(
        array_filter(
            $region_info['availabilityZones'] ?? [],
            fn($code) => is_string($code) && $code !== ''
        ),
        true
    );
    $available_deployment_zones = [];
    foreach($configured_region_codes[$region_code] as $deployment_zone_code => $deployment_zone_type) {
        if(
            ($deployment_zone_type === 'provider_region' && $deployment_zone_code === $region_code)
            || (
                $deployment_zone_type === 'availability_zone'
                && isset($reported_availability_zones[$deployment_zone_code])
            )
        ) {
            $available_deployment_zones[] = $deployment_zone_code;
        }
    }

    if(empty($available_deployment_zones)) {
        unset($configured_region_codes[$region_code]);
        continue;
    }

    $catalog['regions'][$region_code] = [
        'deployment_zones'  => $available_deployment_zones,
        'flavors'          => [],
        'images'           => [],
        'ssh_keys'         => [],
        'public_networks'  => [],
        'private_networks' => []
    ];

    $regional_networks = $getOvhCollection(
        $api_credentials,
        "$base_path/region/".rawurlencode($region_code).'/network'
    );
    foreach($regional_networks as $network) {
        $visibility = $network['visibility'] ?? null;
        if(
            !in_array($visibility, ['public', 'private'], true)
            || empty($network['id'])
            || ($network['status'] ?? null) !== 'ACTIVE'
        ) {
            continue;
        }

        $catalog_key = $visibility.'_networks';
        $network_data = [
            'id'   => $network['id'],
            'name' => $network['name'] ?? $network['id']
        ];
        if($visibility === 'private') {
            $network_data['subnet_ids'] = array_values(array_filter(
                $network['subnetIds'] ?? [],
                fn($subnet_id) => is_string($subnet_id) && $subnet_id !== ''
            ));
        }

        $catalog['regions'][$region_code][$catalog_key][$network['id']] = $network_data;
    }
}

if(empty($catalog['regions'])) {
    throw new Exception('missing_provider_catalog_regions', EQ_ERROR_INVALID_PARAM);
}

$allowed_flavor_names = ['d2-2', 'd2-4', 'b3-8'];
foreach($flavors as $flavor) {
    $region_code = $flavor['region'] ?? null;
    if(
        !isset($configured_region_codes[$region_code])
        || !in_array($flavor['name'] ?? null, $allowed_flavor_names, true)
        || empty($flavor['available'])
        || (isset($flavor['quota']) && $flavor['quota'] <= 0)
        || empty($flavor['id'])
    ) {
        continue;
    }

    $catalog['regions'][$region_code]['flavors'][$flavor['id']] = [
        'id'    => $flavor['id'],
        'name'  => $flavor['name']
    ];
}

$allowed_image_names = ['Ubuntu 24.04 - UEFI', 'Ubuntu 26.04 - UEFI'];
foreach($images as $image) {
    $region_code = $image['region'] ?? null;
    if(
        !isset($configured_region_codes[$region_code])
        || !in_array($image['name'] ?? null, $allowed_image_names, true)
        || ($image['status'] ?? null) !== 'active'
        || empty($image['id'])
    ) {
        continue;
    }

    $catalog['regions'][$region_code]['images'][$image['id']] = [
        'id'    => $image['id'],
        'name'  => $image['name']
    ];
}

foreach($ssh_keys as $ssh_key) {
    if(empty($ssh_key['id'])) {
        continue;
    }

    foreach($ssh_key['regions'] ?? [] as $region_code) {
        if(!isset($configured_region_codes[$region_code])) {
            continue;
        }

        $catalog['regions'][$region_code]['ssh_keys'][$ssh_key['id']] = [
            'id'    => $ssh_key['id'],
            'name'  => $ssh_key['name'] ?? $ssh_key['id']
        ];
    }
}

foreach($catalog['regions'] as &$region_catalog) {
    foreach($region_catalog as $resource_type => &$resources) {
        if($resource_type === 'deployment_zones') {
            sort($resources);
            continue;
        }
        $resources = array_values($resources);
        usort($resources, fn($a, $b) => strcasecmp($a['name'], $b['name']));
    }
    unset($resources);
}
unset($region_catalog);
ksort($catalog['regions']);

$provider_catalog = json_encode($catalog, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
if($provider_catalog === false) {
    throw new Exception('invalid_provider_catalog', EQ_ERROR_UNKNOWN);
}

CloudProject::id($cloud_project['id'])->update(['provider_catalog' => $provider_catalog]);

$context
    ->httpResponse()
    ->status(204)
    ->send();
