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
                'type'        => 'string',
                'usage'       => 'text/json',
                'default'     => '{}',
                'description' => 'JSON object with credentials for cloud provider APIs.'
            ],

            'provider_catalog' => [
                'type'        => 'string',
                'usage'       => 'text/json.medium',
                'default'     => '{}',
                'description' => 'Provider-specific JSON catalog of cloud project resources.'
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
}
