<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2026
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

namespace infra\cloud;

use equal\orm\Model;

class CloudProject extends Model {

    public static function getDescription(): string {
        return 'Project hosted by a cloud provider.';
    }

    public static function getColumns(): array {
        return [

            'name' => [
                'type'              => 'string',
                'description'       => 'Human-readable name of the cloud project.',
                'required'          => true
            ],

            'cloud_provider_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'infra\cloud\CloudProvider',
                'description'       => 'Cloud provider operating the region.',
                'required'          => true
            ],

            'cloud_provider_code' => [
                'type'              => 'computed',
                'result_type'       => 'string',
                'description'       => 'Technical code of the cloud provider hosting the project.',
                'relation'          => ['cloud_provider_id' => 'code'],
                'readonly'          => true,
                'visible'           => false
            ],

            'extref_project_id' => [
                'type'              => 'string',
                'description'       => 'Project identifier assigned by the cloud provider.',
                'required'          => true
            ],

            'api_credentials' => [
                'type'              => 'string',
                'usage'             => 'text/json',
                'default'           => '{}',
                'description'       => 'JSON object with credentials for cloud provider APIs.'
            ],

            'provider_catalog' => [
                'type'              => 'string',
                'usage'             => 'text/json.medium',
                'default'           => '{}',
                'description'       => 'Provider-specific JSON catalog of cloud project resources.'
            ],

            'available_regions' => [
                'type'              => 'computed',
                'result_type'       => 'string',
                'usage'             => 'text/json',
                'description'       => 'Mapping of available region names to deployment zone codes.',
                'store'             => false,
                'function'          => 'calcAvailableRegions'
            ],

            'servers_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'infra\server\Server',
                'foreign_field'     => 'cloud_project_id',
                'description'       => 'Servers hosted in the cloud project.'
            ]

        ];
    }

    public static function onchange($event, $values): array {
        $result = parent::onchange($event, $values);

        if(isset($event['cloud_provider_id'])) {
            $cloud_provider = CloudProvider::id($event['cloud_provider_id'])
                ->read(['code'])
                ->first();

            if($cloud_provider && $cloud_provider['code'] === 'ovh') {
                $result['api_credentials'] = json_encode([
                    'application_key'    => '',
                    'application_secret' => '',
                    'consumer_key'       => ''
                ], JSON_PRETTY_PRINT);
            }
        }

        return $result;
    }

    protected static function calcAvailableRegions($self): array {
        $result = [];
        $self->read(['provider_catalog', 'cloud_provider_id' => ['code']]);
        foreach($self as $id => $project) {
            $available_regions = [];
            if(!empty($project['provider_catalog'])) {
                $provider_catalog = json_decode($project['provider_catalog'], true);
                switch($project['cloud_provider_id']['code']) {
                    case 'ovh':
                        $region_codes = array_keys($provider_catalog['regions'] ?? []);
                        if(empty($region_codes)) {
                            break;
                        }

                        $regions = Region::search([
                            ['cloud_provider_id', '=', $project['cloud_provider_id']['id']],
                            ['code', 'in', $region_codes]
                        ])
                            ->read(['code', 'name']);

                        $region_names_by_code = [];
                        foreach($regions as $region) {
                            $region_names_by_code[$region['code']] = $region['name'];
                        }

                        // #memo - for ovh regions key can be the code of a deployment zone
                        $dep_zones = DeploymentZone::search([
                            ['cloud_provider_id', '=', $project['cloud_provider_id']['id']],
                            ['code', 'in', $region_codes]
                        ])
                            ->read(['code', 'region_id' => ['name']]);

                        $region_names_by_dep_zone_code = [];
                        foreach($dep_zones as $dep_zone) {
                            $region_names_by_dep_zone_code[$dep_zone['code']] = $dep_zone['region_id']['name'];
                        }

                        foreach($provider_catalog['regions'] ?? [] as $region_code => $region_catalog) {
                            $region_name = $region_names_by_code[$region_code]
                                ?? $region_names_by_dep_zone_code[$region_code]
                                ?? null;
                            if($region_name === null) {
                                continue;
                            }

                            if(!isset($available_regions[$region_name])) {
                                $available_regions[$region_name] = [];
                            }

                            foreach($region_catalog['deployment_zones'] ?? [] as $dep_zone_code) {
                                if(!in_array($dep_zone_code, $available_regions[$region_name], true)) {
                                    $available_regions[$region_name][] = $dep_zone_code;
                                }
                            }
                        }

                        break;
                }
            }

            uksort($available_regions, 'strnatcasecmp');

            $json_lines = [];
            foreach($available_regions as $region_name => $dep_zone_codes) {
                natcasesort($dep_zone_codes);

                $encoded_dep_zone_codes = [];
                foreach($dep_zone_codes as $dep_zone_code) {
                    $encoded_dep_zone_codes[] = json_encode($dep_zone_code, JSON_UNESCAPED_UNICODE);
                }

                $json_lines[] = '  '
                    .json_encode($region_name, JSON_UNESCAPED_UNICODE)
                    .': ['
                    .implode(', ', $encoded_dep_zone_codes)
                    .']';
            }

            $result[$id] = empty($json_lines)
                ? '{}'
                : "{\n".implode(",\n", $json_lines)."\n}";
        }

        return $result;
    }
}
