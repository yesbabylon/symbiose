<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2026
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

namespace infra\cloud;

use equal\orm\Model;

class DeploymentZone extends Model {

    public static function getDescription(): string {
        return 'Provider-specific deployment target located within a geographic cloud region.';
    }

    public static function getColumns(): array {
        return [

            'code' => [
                'type'              => 'string',
                'usage'             => 'text/plain:64',
                'description'       => 'Provider-specific technical code of the deployment target.',
                'required'          => true
            ],

            'name' => [
                'type'              => 'string',
                'description'       => 'Human-readable name of the deployment target.',
                'required'          => true
            ],

            'type' => [
                'type'              => 'string',
                'description'       => 'Provider interpretation of the deployment target.',
                'selection'         => ['availability_zone', 'provider_region'],
                'default'           => 'availability_zone',
                'required'          => true
            ],

            'cloud_provider_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'infra\cloud\CloudProvider',
                'description'       => 'Cloud provider exposing the deployment target.',
                'required'          => true
            ],

            'region_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'infra\cloud\Region',
                'description'       => 'Geographic region containing the deployment target.',
                'required'          => true,
                'domain'            => ['cloud_provider_id', '=', 'object.cloud_provider_id']
            ]

        ];
    }

    public function getUniques(): array {
        return [
            ['region_id', 'code']
        ];
    }
}
