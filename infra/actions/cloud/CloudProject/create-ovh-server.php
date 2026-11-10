<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2026
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

use equal\http\HttpRequest;
use infra\cloud\AvailabilityZone;
use infra\cloud\CloudProject;
use infra\cloud\Region;
use infra\server\Server;

[$params, $providers] = eQual::announce([
    'description'   => "Create a new server in a specific cloud project.",
    'params'        => [
        'id' => [
            'type'              => 'many2one',
            'foreign_object'    => 'infra\cloud\CloudProject',
            'description'       => 'Identifier of the targeted cloud project.',
            'required'          => true
        ],
        'name' => [
            'type'              => 'string',
            'description'       => 'The name of the new b2 host server.',
            'required'          => true
        ],
        'region_id' => [
            'type'              => 'many2one',
            'foreign_object'    => 'infra\cloud\Region',
            'description'       => 'Identifier of the region where the server should be added.',
            'required'          => true
        ],
        'availability_zone_id' => [
            'type'              => 'many2one',
            'foreign_object'    => 'infra\cloud\AvailabilityZone',
            'description'       => 'Identifier of the availability zone where the server should be added.',
            'domain'            => [
                ['region_id', '=', 'object.region_id']
            ]
        ],
        'flavor' => [
            'type'              => 'string',
            'description'       => 'The material configuration of the server.',
            'selection'         => [
                'd2-2',
                'd2-4',
                'b3-8'
            ]
        ],
        'image' => [
            'type'              => 'string',
            'description'       => 'The os image to use.',
            'selection'         => [
                'Ubuntu 26.04 - UEFI',
                'Ubuntu 24.04 - UEFI'
            ]
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

$getUserData = function(string $name) {
    $userData = <<<'YAML'
#cloud-config
package_update: true
packages:
  - git
  - ca-certificates

write_files:
  - path: /root/b2-bootstrap.env
    permissions: '0600'
    content: |
      HOSTNAME='__HOSTNAME__'
      BACKUP_HOST='backup.example.internal'
      PUBLIC_IP=''
      PRIVATE_IP=''
      GPG_PASSPHRASE='test123456'
      ROOT_PASSWORD='test123456'

runcmd:
  - |
    bash -euo pipefail <<'BASH'
    PUBLIC_IP=''
    PRIVATE_IP=''

    for attempt in $(seq 1 30); do
        PUBLIC_IP="$(ip -4 -o addr show dev ens3 scope global 2>/dev/null \
            | awk 'NR == 1 { sub(/\/.*/, "", $4); print $4 }' || true)"

        PRIVATE_IP="$(ip -4 -o addr show dev ens4 scope global 2>/dev/null \
            | awk 'NR == 1 { sub(/\/.*/, "", $4); print $4 }' || true)"

        if [[ -n "$PUBLIC_IP" && -n "$PRIVATE_IP" ]]; then
            break
        fi

        sleep 1
    done

    if [[ -z "$PUBLIC_IP" || -z "$PRIVATE_IP" ]]; then
        echo 'Unable to resolve ens3 or ens4 IPv4 address.' >&2
        exit 1
    fi

    sed -i \
        -e "s/^PUBLIC_IP='.*'$/PUBLIC_IP='${PUBLIC_IP}'/" \
        -e "s/^PRIVATE_IP='.*'$/PRIVATE_IP='${PRIVATE_IP}'/" \
        /root/b2-bootstrap.env

    git clone https://github.com/yesbabylon/b2.git /root/b2
    cd /root/b2
    install -m 600 /root/b2-bootstrap.env /root/b2/.env
    rm /root/b2-bootstrap.env
    ./install.sh
    BASH
YAML;

    $userData = str_replace('__HOSTNAME__', escapeshellarg($name), $userData);

    return $userData;
};

$encodeBody = function($body): ?string {
    if($body === null || $body === []) {
        return null;
    }

    if(is_string($body)) {
        return $body;
    }

    return json_encode($body, JSON_PRETTY_PRINT);
};

$getOvhTimestamp = function(): int {
    $request = new HttpRequest('GET https://eu.api.ovh.com/1.0/auth/time');
    $request->header('Accept', 'application/json');

    $response = $request->send();
    if($response->getStatusCode() !== 200) {
        throw new \Exception('failed_retrieving_ovh_time', EQ_ERROR_UNKNOWN);
    }

    $body = $response->body();
    if(is_array($body) && isset($body['value'])) {
        $body = $body['value'];
    }

    if(!is_numeric($body)) {
        throw new \Exception('invalid_ovh_time_response', EQ_ERROR_UNKNOWN);
    }

    return (int) $body;
};

$signRequest = function(array $api_credentials, string $method, string $url, string $raw_body, int $timestamp): string {
    $fingerprint = $api_credentials['application_secret']
        . '+'
        . $api_credentials['consumer_key']
        . '+'
        . strtoupper($method)
        . '+'
        . $url
        . '+'
        . $raw_body
        . '+'
        . $timestamp;

    return '$1$' . sha1($fingerprint);
};

/**
 * Action
 */

$cloudProject = CloudProject::id($params['id'])
    ->read([
        'extref_project_id',
        'api_credentials',
        'api_access_token',
        'api_access_token_expiry',
        'provider_catalog',
        'cloud_provider_id' => [
            'code'
        ]
    ])
    ->first();

if(!$cloudProject) {
    throw new Exception('unknown_cloud_project', EQ_ERROR_UNKNOWN_OBJECT);
}

if($cloudProject['cloud_provider_id']['code'] !== 'ovh') {
    throw new Exception('wrong_cloud_provider', EQ_ERROR_INVALID_PARAM);
}

if(empty($cloudProject['extref_project_id'])) {
    throw new Exception('missing_extref_project_id', EQ_ERROR_INVALID_PARAM);
}

if(empty($cloudProject['api_credentials'])) {
    throw new Exception('missing_api_credentials', EQ_ERROR_INVALID_PARAM);
}

$api_credentials = json_decode($cloudProject['api_credentials'], true);

if(empty($api_credentials['application_key'])) {
    throw new Exception('invalid_application_key', EQ_ERROR_INVALID_PARAM);
}

if(empty($api_credentials['application_secret'])) {
    throw new Exception('invalid_application_secret', EQ_ERROR_INVALID_PARAM);
}

if(empty($api_credentials['consumer_key'])) {
    throw new Exception('invalid_consumer_key', EQ_ERROR_INVALID_PARAM);
}

$name = $params['name'];

if(empty($name)) {
    throw new Exception('invalid_name', EQ_ERROR_INVALID_PARAM);
}

$region = Region::id($params['region_id'])
    ->read(['code'])
    ->first(true);

if(!$region) {
    throw new Exception('unknown_region', EQ_ERROR_UNKNOWN_OBJECT);
}

$zone = null;
if(!empty($params['availability_zone_id'])) {
    $zone = AvailabilityZone::id($params['availability_zone_id'])
        ->read(['code', 'region_id'])
        ->first();

    if(!$zone) {
        throw new Exception('unknown_zone', EQ_ERROR_UNKNOWN_OBJECT);
    }

    if($zone['region_id'] !== $region['id']) {
        throw new Exception('invalid_zone', EQ_ERROR_INVALID_PARAM);
    }
}

if(empty($cloudProject['provider_catalog'])) {
    throw new Exception('invalid_provider_catalog', EQ_ERROR_INVALID_PARAM);
}

$provider_catalog = json_decode($cloudProject['provider_catalog'], true);

$region_catalog = $provider_catalog['regions'][$region['code']] ?? null;
if(!$region_catalog) {
    // #memo - the region is not part of the project and cannot be used
    throw new Exception('invalid_region', EQ_ERROR_INVALID_PARAM);
}

$flavor = null;
foreach($region_catalog['flavors'] as $fla) {
    if($fla['name'] === $params['flavor']) {
        $flavor = $fla;
        break;
    }
}
if(!$flavor) {
    throw new Exception('invalid_flavor', EQ_ERROR_INVALID_PARAM);
}

$image = null;
foreach($region_catalog['images'] as $im) {
    if($im['name'] === $params['image']) {
        $image = $im;
        break;
    }
}
if(!$image) {
    throw new Exception('invalid_image', EQ_ERROR_INVALID_PARAM);
}

$public_network = null;
foreach($region_catalog['public_networks'] as $pub_net) {
    if($pub_net['name'] === 'Ext-Net') {
        $public_network = $pub_net;
        break;
    }
}
if(!$public_network) {
    throw new Exception('invalid_image', EQ_ERROR_INVALID_PARAM);
}

$ssh_key = $region_catalog['ssh_keys'][0] ?? null;
if(!$ssh_key) {
    throw new Exception('missing_ssh_key', EQ_ERROR_INVALID_PARAM);
}

$private_network = $region_catalog['private_networks'][0] ?? null;
if(!$private_network) {
    throw new Exception('missing_private_network', EQ_ERROR_INVALID_PARAM);
}

$method = 'POST';
$url = "https://eu.api.ovh.com/1.0/cloud/project/{$cloudProject['extref_project_id']}/instance";
$body = [
    'flavorId' => $flavor['id'],
    'imageId' => $image['id'],
    'monthlyBilling' => false,
    'name' => $name,
    'region' => $region['code'],
    'sshKeyId' => $ssh_key['id'],
    'networks' => [
        ['networkId' => $public_network['id']],
        ['networkId' => $private_network['id']]
    ],
    'userData' => $getUserData($name)
];

if($zone) {
    $body['availabilityZone'] = $zone['code'];
}

file_put_contents(QN_LOG_STORAGE_DIR.'/tmp.log', json_encode($body).PHP_EOL, FILE_APPEND | LOCK_EX);

$raw_body = $encodeBody($body);

$timestamp = $getOvhTimestamp();
$signature = $signRequest($api_credentials, $method, $url, $raw_body, $timestamp);

$request = (new HttpRequest("$method $url"))
    ->header('Accept', 'application/json')
    ->header('Content-Type', 'application/json')
    ->header('X-Ovh-Application', $api_credentials['application_key'])
    ->header('X-Ovh-Consumer', $api_credentials['consumer_key'])
    ->header('X-Ovh-Timestamp', (string) $timestamp)
    ->header('X-Ovh-Signature', $signature)
    ->body($body);

$response = $request->send();

if($response->getStatusCode() !== 200) {
    file_put_contents(QN_LOG_STORAGE_DIR.'/tmp.log', json_encode($response->body()).PHP_EOL, FILE_APPEND | LOCK_EX);
    throw new \Exception('failed_creating_ovh_server', EQ_ERROR_UNKNOWN);
}

$response_data = $response->body();

file_put_contents(QN_LOG_STORAGE_DIR.'/tmp.log', $response->status().PHP_EOL, FILE_APPEND | LOCK_EX);
file_put_contents(QN_LOG_STORAGE_DIR.'/tmp.log', json_encode($response_data).PHP_EOL, FILE_APPEND | LOCK_EX);

Server::create([
    'name'              => $name,
    'server_type'       => 'b2',
    'cloud_provider_id' => $cloudProject['cloud_provider_id']['id'],
    'cloud_project_id'  => $cloudProject['id'],
    'region_id'         => $region['id'],
    'extref_server_id'  => $response_data['id']
]);

// TODO: create access and save root password


$context
    ->httpResponse()
    ->send();
