<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2026
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

use infra\cloud\AvailabilityZone;
use infra\cloud\CloudProvider;
use infra\cloud\Region;

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

$region_definitions = [
    ['code' => 'GRA1',        'name' => 'Gravelines (GRA1)'],
    ['code' => 'GRA3',        'name' => 'Gravelines (GRA3)'],
    ['code' => 'GRA5',        'name' => 'Gravelines (GRA5)'],
    ['code' => 'GRA7',        'name' => 'Gravelines (GRA7)'],
    ['code' => 'GRA9',        'name' => 'Gravelines (GRA9)'],
    ['code' => 'GRA11',       'name' => 'Gravelines (GRA11)'],
    ['code' => 'DE1',         'name' => 'Frankfurt (DE1)'],
    ['code' => 'SBG5',        'name' => 'Strasbourg (SBG5)'],
    ['code' => 'SBG7',        'name' => 'Strasbourg (SBG7)'],
    ['code' => 'RBX-A',       'name' => 'Roubaix (RBX-A)'],
    ['code' => 'EU-WEST-RBX', 'name' => 'Roubaix (EU-WEST-RBX)'],
    ['code' => 'UK1',         'name' => 'London (UK1)'],
    ['code' => 'WAW1',        'name' => 'Warsaw (WAW1)'],
    [
        'code'               => 'EU-WEST-PAR',
        'name'               => 'Paris (EU-WEST-PAR)',
        'availability_zones' => [
            'EU-WEST-PAR-A',
            'EU-WEST-PAR-B',
            'EU-WEST-PAR-C',
        ],
    ],
    [
        'code'               => 'EU-SOUTH-MIL',
        'name'               => 'Milan (EU-SOUTH-MIL)',
        'availability_zones' => [
            'EU-SOUTH-MIL-A',
            'EU-SOUTH-MIL-B',
            'EU-SOUTH-MIL-C',
        ],
    ],
];

foreach($region_definitions as $region_definition) {
    $availability_zone_codes = $region_definition['availability_zones'] ?? [];
    unset($region_definition['availability_zones']);

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

    foreach($availability_zone_codes as $availability_zone_code) {
        $availability_zone_data = [
            'code'              => $availability_zone_code,
            'name'              => $availability_zone_code,
            'cloud_provider_id' => $cloud_provider_id,
            'region_id'         => $region_id
        ];

        $availability_zone = AvailabilityZone::search([
            ['region_id', '=', $region_id],
            ['code', '=', $availability_zone_code]
        ]);
        if(count($availability_zone->ids())) {
            $availability_zone->update($availability_zone_data);
        }
        else {
            AvailabilityZone::create($availability_zone_data);
        }
    }
}
