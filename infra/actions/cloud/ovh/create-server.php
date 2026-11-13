<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2026
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

use equal\http\HttpRequest;
use infra\Access;
use infra\cloud\CloudProject;
use infra\cloud\DeploymentZone;
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
            'description'       => 'The material configuration of the server.',
            'selection'         => [
                'd2-2',
                'd2-4',
                'd2-8',
                'b3-8'
            ],
            'default'           => 'd2-2'
        ],
        'image' => [
            'type'              => 'string',
            'description'       => 'The os image to use.',
            'selection'         => [
                'Ubuntu 26.04 - UEFI',
                'Ubuntu 24.04 - UEFI'
            ],
            'default'           => 'Ubuntu 26.04 - UEFI'
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

$randomStr = function(int $length = 16): string {
    $keyspace = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ!@#$%^&*';
    $str = '';
    $max = mb_strlen($keyspace, '8bit') - 1;
    for ($i = 0; $i < $length; ++$i) {
        $str .= $keyspace[random_int(0, $max)];
    }
    return $str;
};

$getUserData = function(string $name, string $password): string {
    $user_data = <<<'YAML'
#cloud-config
package_update: true
packages:
  - git
  - ca-certificates

write_files:
  - path: /root/b2-bootstrap.env
    permissions: '0600'
    content: |
      HOSTNAME=__HOSTNAME__
      BACKUP_HOST='backup.example.internal'
      PUBLIC_IP=''
      PRIVATE_IP=''
      GPG_PASSPHRASE=__PASSWORD__
      ROOT_PASSWORD=__PASSWORD__

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
    systemctl start b2-listener.service
    BASH
YAML;

    $user_data = str_replace('__HOSTNAME__', escapeshellarg($name), $user_data);

    return str_replace('__PASSWORD__', escapeshellarg($password), $user_data);
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
        'cloud_provider_id' => ['code']
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
    ->read(['code', 'cloud_provider_id'])
    ->first();

if(!$region) {
    throw new Exception('unknown_region', EQ_ERROR_UNKNOWN_OBJECT);
}

$deployment_zone = DeploymentZone::id($params['deployment_zone_id'])
    ->read(['code', 'type', 'region_id', 'cloud_provider_id'])
    ->first();

if(!$deployment_zone) {
    throw new Exception('unknown_deployment_zone', EQ_ERROR_UNKNOWN_OBJECT);
}

if(
    (int) $region['cloud_provider_id'] !== (int) $cloudProject['cloud_provider_id']['id']
    || (int) $deployment_zone['cloud_provider_id'] !== (int) $cloudProject['cloud_provider_id']['id']
    || (int) $deployment_zone['region_id'] !== (int) $region['id']
) {
    throw new Exception('invalid_deployment_zone', EQ_ERROR_INVALID_PARAM);
}

$ovh_region_code = $deployment_zone['type'] === 'provider_region'
    ? $deployment_zone['code']
    : $region['code'];

$ovh_availability_zone = $deployment_zone['type'] === 'availability_zone'
    ? $deployment_zone['code']
    : null;

if(empty($cloudProject['provider_catalog'])) {
    throw new Exception('invalid_provider_catalog', EQ_ERROR_INVALID_PARAM);
}

$provider_catalog = json_decode($cloudProject['provider_catalog'], true);
if(!is_array($provider_catalog)) {
    throw new Exception('invalid_provider_catalog', EQ_ERROR_INVALID_PARAM);
}

$region_catalog = $provider_catalog['regions'][$ovh_region_code] ?? null;
if(!$region_catalog) {
    // #memo - the provider region is not part of the project and cannot be used
    throw new Exception('invalid_region', EQ_ERROR_INVALID_PARAM);
}

if(!in_array($deployment_zone['code'], $region_catalog['deployment_zones'] ?? [], true)) {
    throw new Exception('invalid_deployment_zone', EQ_ERROR_INVALID_PARAM);
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

$ssh_key = $region_catalog['ssh_keys'][0] ?? null;
if(!$ssh_key) {
    throw new Exception('missing_ssh_key', EQ_ERROR_INVALID_PARAM);
}

$private_network = $region_catalog['private_networks'][0] ?? null;
if(
    !$private_network
    || empty($private_network['id'])
    || empty($private_network['subnet_ids'][0])
) {
    throw new Exception('missing_private_network', EQ_ERROR_INVALID_PARAM);
}

$project_id = rawurlencode($cloudProject['extref_project_id']);
$region_code = rawurlencode($ovh_region_code);
$base_url = "https://eu.api.ovh.com/1.0/cloud/project/$project_id";

$password = $randomStr();

$method = 'POST';

$url = "$base_url/region/$region_code/instance";

$network = [
    'private' => [
        'network' => [
            'id'        => $private_network['id'],
            'subnetId'  => $private_network['subnet_ids'][0]]
    ],
    'public' => true
];

$body = [
    'availabilityZone'  => $ovh_availability_zone,
    'bootFrom'          => ['imageId' => $image['id'], 'imageRegionName' => $ovh_region_code],
    'bulk'              => 1,
    'flavor'            => ['id' => $flavor['id']],
    'name'              => $name,
    'network'           => $network,
    'sshKey'            => ['name' => $ssh_key['name']],
    'billingPeriod'     => $params['billing_period'],
    'userData'          => $getUserData($name, $password) // #memo - script that configures, installs and starts the b2-server
];

/*

Example of possible options:

{
  "availabilityZone": null,
  "bootFrom": {
    "imageId": "62b2bb6b-d5bd-46c0-a23f-fd673f595bdc",
    "imageRegionName": "GRA11",
    "volumeId": null
  },
  "bulk": 1,
  "flavor": {
    "id": "c6e9b74f-c419-426c-b24c-85aa0ce73495"
  },
  "name": "d2-2-2026_11_12-10-56",
  "network": {
    "private": {
      "floatingIp": null,
      "floatingIpCreate": null,
      "gatewayCreate": null,
      "network": {
        "id": "0351b90e-b104-4c3a-8709-3148dc2f4fa4",
        "subnetId": "b5e46838-1dc3-42fa-a25a-8fe6413276d4"
      },
      "networkCreate": null
    },
    "public": true
  },
  "sshKey": {
    "name": "lucas-Vivobook-ASUSLaptop"
  },
  "sshKeyCreate": null,
  "billingPeriod": "hourly",
  "autobackup": null,
  "userData": null
}
*/

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
    throw new \Exception('failed_creating_ovh_server', EQ_ERROR_UNKNOWN);
}

$response_data = $response->body();

if(isset($response_data['value']) && is_array($response_data['value'])) {
    $response_data = $response_data['value'];
}

if(empty($response_data['id'])) {
    throw new \Exception('failed_creating_ovh_server', EQ_ERROR_UNKNOWN);
}

$server = Server::create([
        'name'               => $name,
        'server_type'        => 'b2',
        'cloud_provider_id'  => $cloudProject['cloud_provider_id']['id'],
        'cloud_project_id'   => $cloudProject['id'],
        'region_id'          => $region['id'],
        'deployment_zone_id' => $deployment_zone['id']
    ])
    ->first();

Access::create([
    'server_id'     => $server['id'],
    'description'   => 'Created by action create-ovh-server.',
    'access_type'   => 'http',
    'host'          => $name,
    'port'          => '8000',
    'username'      => 'root',
    'password'      => $password,
]);

$cron->schedule(
    "infra.ovh-server.{$server['id']}.sync-provisioning",
    strtotime('+5 minutes'),
    'infra_cloud_ovh_sync-server',
    [
        'server_id'        => $server['id'],
        'operation_id'     => $response_data['id'],
        'attempt'          => 1,
        'retry_on_failure' => true
    ]
);

$context
    ->httpResponse()
    ->body($response_data)
    ->send();
