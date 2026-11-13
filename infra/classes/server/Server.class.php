<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2024
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

namespace infra\server;

use equal\orm\Model;
use infra\Access;
use infra\cloud\DeploymentZone;
use infra\cloud\Region;

class Server extends Model {


    public static function getDescription() {
        return 'The Server includes its name, description, type, access, instances, associated products, IP addresses, and installed software.';
    }

    public static function getColumns() {

        return [

            'name' => [
                'type'              => 'string',
                'description'       => 'Internal identification ex. trg.be-master.',
                'unique'            => true
            ],

            'description' => [
                'type'              => 'string',
                'usage'             => 'text/plain',
                'description'       => 'Short description of the Server.',
            ],

            'cloud_provider_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'infra\cloud\CloudProvider',
                'description'       => 'Cloud provider hosting the server.',
                // 'required'          => true
            ],

            'cloud_project_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'infra\cloud\CloudProject',
                'description'       => 'Cloud project hosting the Server.',
                // 'required'          => true
            ],

            'extref_server_id' => [
                'type'              => 'string',
                'usage'             => 'text/plain:128',
                'description'       => 'Server identifier assigned by the external cloud provider.'
            ],

            'region_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'infra\cloud\Region',
                'description'       => 'Geographic region hosting the server.',
                'domain'            => ['cloud_provider_id', '=', 'object.cloud_provider_id'],
                // 'required'          => true
            ],

            'deployment_zone_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'infra\cloud\DeploymentZone',
                'description'       => 'Provider deployment target hosting the server.',
                'domain'            => ['region_id', '=', 'object.region_id']
            ],

            'zone_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'infra\cloud\DeploymentZone',
                'description'       => 'Legacy deployment target reference retained for data migration.',
                'visible'           => false,
                'deprecated'        => true
            ],

            'server_type' => [
                'type'              => 'string',
                'description'       => 'Type of the server.',
                'selection'         => ['front', 'node', 'storage', 'b2', 'k2', 's2', 'admin'],
                'default'           => 'front',
                'onupdate'          => 'onupdateServerType',
                'dependents'        => ['instances_ids' => ['server_type']]
            ],

            'synced' => [
                'type'              => 'datetime',
                'description'       => 'Date of last status update.',
                'help'              => 'The "up" field can be auto updated by the action "infra_server_Server_sync-status".'
            ],

            'up' => [
                'type'              => 'boolean',
                'description'       => 'Whether the latest status check found the server reachable.',
                'default'           => false,
                'onupdate'          => 'onupdateUp'
            ],

            'accesses_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'infra\Access',
                'foreign_field'     => 'server_id',
                'description'       => 'Access information to the server.'
            ],

            'instances_ids' => [
                'type'              => 'one2many',
                'foreign_field'     => 'server_id',
                'foreign_object'    => 'infra\server\Instance',
                'ondetach'          => 'delete',
                'description'       => 'Instances running on the server.'
            ],

            'instances_count' => [
                'type'              => 'computed',
                'result_type'       => 'integer',
                'function'          => 'calcInstancesCount'
            ],

            'products_ids' => [
                'type'              => 'many2many',
                'foreign_object'    => 'inventory\Product',
                'foreign_field'     => 'servers_ids',
                'rel_table'         => 'inventory_rel_product_server',
                'rel_foreign_key'   => 'product_id',
                'rel_local_key'     => 'server_id',
                'description'       => 'List of products that are using the server.'
            ],

            'products_count' => [
                'type'              => 'computed',
                'result_type'       => 'integer',
                'function'          => 'calcProductsCount'
            ],

            'ip_address_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'infra\server\IpAddress',
                'foreign_field'     => 'server_id',
                'ondetach'          => 'delete',
                'description'       => 'IP Addresses of the server.'
            ],

            'softwares_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'inventory\Software',
                'foreign_field'     => 'server_id',
                'ondetach'          => 'delete',
                'description'       => 'List of Software installed on the server.'
            ],

            'statuses_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'infra\server\Status',
                'foreign_field'     => 'object_id',
                'domain'            => ['object_class', '=', 'infra\server\Server'],
                'description'       => 'Statuses of the server.'
            ],

            'checks_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'infra\server\Check',
                'foreign_field'     => 'object_id',
                'domain'            => ['object_class', '=', 'infra\server\Server'],
                'description'       => 'Checks of the server.'
            ],

            'alert_triggers_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'infra\server\AlertTrigger',
                'foreign_field'     => 'object_id',
                'domain'            => ['object_class', '=', 'infra\server\Server'],
                'description'       => 'Alert triggers of the server.'
            ]

        ];
    }

    public static function getConstraints(): array {
        return [
            'region_id' => [
                'invalid_cloud_provider' => [
                    'message'       => 'Region must belong to the selected cloud provider.',
                    'function'      => function($region_id, $values) {
                        if(empty($region_id)) {
                            return false;
                        }
                        if(!array_key_exists('cloud_provider_id', $values)) {
                            return true;
                        }
                        if(empty($values['cloud_provider_id'])) {
                            return false;
                        }

                        $region = Region::id($region_id)
                            ->read(['cloud_provider_id'])
                            ->first();

                        return $region
                            && (int) $region['cloud_provider_id'] === (int) $values['cloud_provider_id'];
                    }
                ]
            ],
            'deployment_zone_id' => [
                'invalid_region' => [
                    'message'       => 'Deployment zone must belong to the selected region.',
                    'function'      => function($deployment_zone_id, $values) {
                        if(empty($deployment_zone_id)) {
                            return true;
                        }
                        if(!array_key_exists('region_id', $values)) {
                            return true;
                        }
                        if(empty($values['region_id'])) {
                            return false;
                        }

                        $zone = DeploymentZone::id($deployment_zone_id)
                            ->read(['region_id'])
                            ->first();

                        return $zone
                            && (int) $zone['region_id'] === (int) $values['region_id'];
                    }
                ]
            ]
        ];
    }

    public static function getPolicies(): array {
        return array_merge(parent::getPolicies(), [
            'valid_cloud_location' => [
                'description'       => 'Ensure the cloud region and deployment zone belong to their selected parents.',
                'function'          => 'policyValidCloudLocation'
            ]
        ]);
    }

    public static function getOperationPolicies(): array {
        return array_replace_recursive(parent::getOperationPolicies(), [
            EQ_R_UPDATE => [
                '*'                  => true,
                'cloud_provider_id'  => ['valid_cloud_location'],
                'region_id'          => ['valid_cloud_location'],
                'deployment_zone_id' => ['valid_cloud_location']
            ]
        ]);
    }

    protected static function policyValidCloudLocation($self, $values): array {
        $self->read(['state', 'cloud_provider_id', 'region_id', 'deployment_zone_id']);

        $result = [];
        foreach($self as $id => $server) {
            $cloud_provider_id = array_key_exists('cloud_provider_id', $values)
                ? $values['cloud_provider_id']
                : $server['cloud_provider_id'];
            $region_id = array_key_exists('region_id', $values)
                ? $values['region_id']
                : $server['region_id'];
            $deployment_zone_id = array_key_exists('deployment_zone_id', $values)
                ? $values['deployment_zone_id']
                : $server['deployment_zone_id'];

            if(empty($cloud_provider_id) || empty($region_id)) {
                if($server['state'] === 'draft') {
                    continue;
                }
                $result[$id]['invalid_cloud_provider'] = 'A cloud provider and one of its regions are required.';
                continue;
            }

            $region = Region::id($region_id)
                ->read(['cloud_provider_id'])
                ->first();
            if(!$region || (int) $region['cloud_provider_id'] !== (int) $cloud_provider_id) {
                $result[$id]['invalid_cloud_provider'] = 'Region must belong to the selected cloud provider.';
                continue;
            }

            if(empty($deployment_zone_id)) {
                continue;
            }

            $zone = DeploymentZone::id($deployment_zone_id)
                ->read(['region_id'])
                ->first();
            if(!$zone || (int) $zone['region_id'] !== (int) $region_id) {
                $result[$id]['invalid_region'] = 'Deployment zone must belong to the selected region.';
            }
        }

        return $result;
    }

    protected static function calcProductsCount($self): array {
        $result = [];
        $self->read(['products_ids']);
        foreach($self as $id => $product) {
            $result[$id] = count($product['products_ids']);
        }

        return $result;
    }

    protected static function calcInstancesCount($self): array {
        $result = [];
        $self->read(['instances_ids']);
        foreach($self as $id => $server) {
            $result[$id] = count($server['instances_ids']);
        }

        return $result;
    }

    public static function getActions(): array {
        return [
            'create_management_api_access' => [
                'description'   => "Creates the management api access if needed.",
                'policies'      => [],
                'function'      => 'doCreateManagementApiAccess'
            ]
        ];
    }

    public static function onupdateUp($self) {
        $self->read(['up', 'server_type', 'instances_ids']);
        foreach($self as $server) {
            if($server['server_type'] === 'b2' && !$server['up']) {
                Instance::ids($server['instances_ids'])->update(['up' => false]);
            }
        }
    }

    public static function onupdateServerType($self) {
        $self->do('create_management_api_access');
    }

    public static function doCreateManagementApiAccess($self) {
        $self->read([
            'server_type',
            'accesses_ids'      => ['access_type', 'protocol', 'port'],
            'ip_address_ids'    => ['ip_v4', 'visibility']
        ]);

        foreach($self as $server) {
            if(!in_array($server['server_type'], ['b2', 'k2', 's2', 'admin'])) {
                continue;
            }

            foreach($server['accesses_ids'] as $access) {
                if(in_array($access['access_type'], ['http', 'https']) && $access['port'] === '8000') {
                    continue 2;
                }
            }

            $private_ip_address = null;
            foreach($server['ip_address_ids'] as $ip_address) {
                if($ip_address['visibility'] === 'private') {
                    $private_ip_address = $ip_address;
                    break;
                }
            }
            if(is_null($private_ip_address) || is_null($private_ip_address['ip_v4'])) {
                continue;
            }

            Access::create([
                'name'          => 'Management API',
                'server_id'     => $server['id'],
                'access_type'   => 'https',
                'port'          => '8000',
                'host'          => explode('/', $private_ip_address['ip_v4'])[0],
                'username'      => '',
                'password'      => ''
            ]);
        }
    }
}
