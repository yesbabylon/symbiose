<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2026
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

use infra\cloud\CloudProvider;
use infra\cloud\DeploymentZone;
use infra\cloud\Region;
use infra\server\Server;

$cloud_provider_data = [
    'code' => 'ovh',
    'name' => 'OVHcloud'
];

$cloud_provider = CloudProvider::search(['code', '=', $cloud_provider_data['code']]);
if(count($cloud_provider->ids())) {
    $cloud_provider->update($cloud_provider_data);
}
else {
    $cloud_provider = CloudProvider::create($cloud_provider_data);
}

$cloud_provider_ids = $cloud_provider->ids();
$cloud_provider_id = reset($cloud_provider_ids);

$legacy_region_ids_by_code = [];
$existing_regions = Region::search([
    ['cloud_provider_id', '=', $cloud_provider_id]
])->read(['code']);
foreach($existing_regions as $existing_region) {
    $legacy_region_ids_by_code[$existing_region['code']] = $existing_region['id'];
}

$region_definitions = [
    [
        'code'             => 'GRA',
        'name'             => 'Gravelines',
        'deployment_zones' => [
            'GRA1'  => 'provider_region',
            'GRA3'  => 'provider_region',
            'GRA5'  => 'provider_region',
            'GRA7'  => 'provider_region',
            'GRA9'  => 'provider_region',
            'GRA11' => 'provider_region'
        ]
    ],
    [
        'code'             => 'SBG',
        'name'             => 'Strasbourg',
        'deployment_zones' => [
            'SBG5' => 'provider_region',
            'SBG7' => 'provider_region'
        ]
    ],
    [
        'code'             => 'RBX',
        'name'             => 'Roubaix',
        'deployment_zones' => [
            'RBX-A'       => 'provider_region',
            'EU-WEST-RBX' => 'provider_region'
        ]
    ],
    [
        'code'             => 'DE',
        'name'             => 'Frankfurt',
        'deployment_zones' => [
            'DE1' => 'provider_region'
        ]
    ],
    [
        'code'             => 'UK',
        'name'             => 'London',
        'deployment_zones' => [
            'UK1' => 'provider_region'
        ]
    ],
    [
        'code'             => 'WAW',
        'name'             => 'Warsaw',
        'deployment_zones' => [
            'WAW1' => 'provider_region'
        ]
    ],
    [
        'code'             => 'EU-WEST-PAR',
        'name'             => 'Paris',
        'deployment_zones' => [
            'EU-WEST-PAR-A' => 'availability_zone',
            'EU-WEST-PAR-B' => 'availability_zone',
            'EU-WEST-PAR-C' => 'availability_zone'
        ]
    ],
    [
        'code'             => 'EU-SOUTH-MIL',
        'name'             => 'Milan',
        'deployment_zones' => [
            'EU-SOUTH-MIL-A' => 'availability_zone',
            'EU-SOUTH-MIL-B' => 'availability_zone',
            'EU-SOUTH-MIL-C' => 'availability_zone'
        ]
    ]
];

foreach($region_definitions as $region_definition) {
    $deployment_zone_definitions = $region_definition['deployment_zones'];
    unset($region_definition['deployment_zones']);

    $region_data = array_merge(
        $region_definition,
        ['cloud_provider_id' => $cloud_provider_id]
    );

    $region = Region::search([
        ['cloud_provider_id', '=', $cloud_provider_id],
        ['code', '=', $region_data['code']]
    ]);
    if(count($region->ids())) {
        $region->update($region_data);
    }
    else {
        $region = Region::create($region_data);
    }

    $region_ids = $region->ids();
    $region_id = reset($region_ids);

    foreach($deployment_zone_definitions as $deployment_zone_code => $deployment_zone_type) {
        $deployment_zone_data = [
            'code'              => $deployment_zone_code,
            'name'              => $deployment_zone_code,
            'type'              => $deployment_zone_type,
            'cloud_provider_id' => $cloud_provider_id,
            'region_id'         => $region_id
        ];

        // Search by provider and code to reuse identifiers from the renamed entity.
        $deployment_zone = DeploymentZone::search([
            ['cloud_provider_id', '=', $cloud_provider_id],
            ['code', '=', $deployment_zone_code]
        ]);
        $deployment_zone_ids = $deployment_zone->ids();
        if(count($deployment_zone_ids)) {
            $deployment_zone_id = reset($deployment_zone_ids);
            DeploymentZone::id($deployment_zone_id)->update($deployment_zone_data);
        }
        else {
            $deployment_zone = DeploymentZone::create($deployment_zone_data);
            $deployment_zone_ids = $deployment_zone->ids();
            $deployment_zone_id = reset($deployment_zone_ids);
        }

        // Historical OVH technical regions become provider-region deployment zones.
        $legacy_region_id = $legacy_region_ids_by_code[$deployment_zone_code] ?? null;
        if(
            $deployment_zone_type === 'provider_region'
            && $legacy_region_id
            && (int) $legacy_region_id !== (int) $region_id
        ) {
            Server::search([
                ['cloud_provider_id', '=', $cloud_provider_id],
                ['region_id', '=', $legacy_region_id]
            ])->update([
                'region_id'          => $region_id,
                'deployment_zone_id' => $deployment_zone_id
            ]);
        }
    }
}

// Copy historical server zone references after every deployment zone has been reconciled.
$servers = Server::search([
    ['cloud_provider_id', '=', $cloud_provider_id]
])->read(['region_id', 'deployment_zone_id', 'zone_id']);
foreach($servers as $server) {
    if(!empty($server['deployment_zone_id']) || empty($server['zone_id'])) {
        continue;
    }

    $deployment_zone = DeploymentZone::id($server['zone_id'])
        ->read(['region_id'])
        ->first();
    if(!$deployment_zone) {
        continue;
    }

    Server::id($server['id'])->update([
        'region_id'          => $deployment_zone['region_id'],
        'deployment_zone_id' => $deployment_zone['id']
    ]);
}
