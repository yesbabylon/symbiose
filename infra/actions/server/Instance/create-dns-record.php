<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2026
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

use equal\dns\Dns;
use equal\dns\DnsRecordHelper;
use infra\server\Instance;
use infra\server\IpAddress;

[$params, $providers] = eQual::announce([
    'description'   => 'Ensure that a managed OVH A record exists for an instance.',
    'params'        => [
        'id' => [
            'type'              => 'many2one',
            'foreign_object'    => 'infra\server\Instance',
            'description'       => 'Identifier of the targeted instance.',
            'required'          => true
        ],
        'zone' => [
            'type'              => 'string',
            'description'       => 'Managed OVH DNS zone containing the instance FQDN.',
            'required'          => true
        ],
        'ttl' => [
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
    'constants'     => [
        'DNS_API_APPLICATION_KEY',
        'DNS_API_APPLICATION_SECRET',
        'DNS_API_CONSUMER_KEY'
    ],
    'providers'     => ['context']
]);

/**
 * @var \equal\php\Context $context
 */
['context' => $context] = $providers;

$instance = Instance::id($params['id'])
    ->read([
        'name',
        'has_dns_record',
        'server_id' => [
            'server_type',
            'ip_address_ids' => ['ip_v4', 'visibility', 'exposure', 'dns_status']
        ]
    ])
    ->first();

if(!$instance) {
    throw new Exception('unknown_instance', EQ_ERROR_UNKNOWN_OBJECT);
}

if(!$instance['server_id']) {
    throw new Exception('missing_server', EQ_ERROR_INVALID_PARAM);
}

if($instance['server_id']['server_type'] !== 'b2') {
    throw new Exception('invalid_server_type', EQ_ERROR_NOT_ALLOWED);
}

$zone = DnsRecordHelper::normalizeZone($params['zone']);
$fqdn = strtolower(rtrim(trim($instance['name']), '.'));
if($zone === '' || $fqdn === $zone || !str_ends_with($fqdn, ".$zone")) {
    throw new Exception('invalid_dns_zone', EQ_ERROR_INVALID_PARAM);
}

$name = DnsRecordHelper::normalizeSubdomain($fqdn, $zone);
$protected_names = [
    '',
    '@',
    'www',
    'mail',
    'smtp',
    'imap',
    'pop',
    'ftp',
    'webmail',
    '_dmarc',
    '_acme-challenge',
    'platform'
];
if(in_array($name, $protected_names, true)) {
    throw new Exception('protected_dns_record_name', EQ_ERROR_NOT_ALLOWED);
}

$public_ip_address = null;
$fallback_ip_address = null;
foreach($instance['server_id']['ip_address_ids'] as $ip_address) {
    if(empty($ip_address['ip_v4']) || $ip_address['exposure'] !== 'public') {
        continue;
    }

    $value = explode('/', $ip_address['ip_v4'])[0];
    if(filter_var($value, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) === false) {
        continue;
    }

    if($fallback_ip_address === null) {
        $fallback_ip_address = $ip_address;
    }
    if($ip_address['visibility'] === 'public') {
        $public_ip_address = $ip_address;
        break;
    }
}

$public_ip_address ??= $fallback_ip_address;
if($public_ip_address === null) {
    throw new Exception('missing_public_ip_address', EQ_ERROR_INVALID_CONFIG);
}

$value = explode('/', $public_ip_address['ip_v4'])[0];
$dns_config = [
    'provider' => 'ovh',
    'zones'    => [
        $zone => [
            'provider'    => 'ovh',
            'credentials' => [
                'application_key'    => constant('DNS_API_APPLICATION_KEY'),
                'application_secret' => constant('DNS_API_APPLICATION_SECRET'),
                'consumer_key'       => constant('DNS_API_CONSUMER_KEY')
            ],
            'supported_types' => ['A'],
            'min_ttl'         => 60,
            'default_ttl'     => 3600
        ]
    ]
];

try {
    $dns = Dns::create($dns_config);
    $existing_records = $dns->getRecords($zone, $name, 'A');
    if(count($existing_records) > 1) {
        throw new Exception('multiple_matching_dns_records', EQ_ERROR_CONFLICT_OBJECT);
    }

    $result = $dns->ensureRecord([
        'zone'  => $zone,
        'name'  => $name,
        'type'  => 'A',
        'value' => $value,
        'ttl'   => $params['ttl']
    ]);
}
catch(Throwable $e) {
    trigger_error("APP::Unable to ensure OVH DNS record for instance {$instance['id']}: {$e->getMessage()}", EQ_REPORT_ERROR);
    throw new Exception('dns_record_creation_failed', EQ_ERROR_UNKNOWN);
}

Instance::id($instance['id'])->update(['has_dns_record' => true]);

$dns_status = $public_ip_address['dns_status'];
if($dns_status === 'none') {
    $dns_status = 'forward';
}
elseif($dns_status === 'reverse') {
    $dns_status = 'forward_reverse';
}
if($dns_status !== $public_ip_address['dns_status']) {
    IpAddress::id($public_ip_address['id'])->update(['dns_status' => $dns_status]);
}

trigger_error(
    'APP::dns_operation '.json_encode([
        'zone'      => $zone,
        'name'      => $name,
        'type'      => 'A',
        'value'     => $value,
        'provider'  => 'ovh',
        'operation' => 'ensure_record',
        'result'    => $result['status'] ?? 'success'
    ], JSON_UNESCAPED_SLASHES),
    EQ_REPORT_INFO
);

$context
    ->httpResponse()
    ->status(204)
    ->send();
