<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2026
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

namespace infra\cloud;

use equal\orm\Model;

class Region extends Model {

    public static function getDescription(): string {
        return 'Geographically distinct region operated by a cloud provider.';
    }

    public static function getColumns(): array {
        return [

            'code' => [
                'type'              => 'string',
                'usage'             => 'text/plain:64',
                'description'       => 'Technical code unique within the cloud provider.',
                'required'          => true
            ],

            'name' => [
                'type'              => 'string',
                'description'       => 'Human-readable name of the region.',
                'required'          => true
            ],

            'cloud_provider_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'infra\cloud\CloudProvider',
                'description'       => 'Cloud provider operating the region.',
                'required'          => true
            ],

            'zones_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'infra\cloud\AvailabilityZone',
                'foreign_field'     => 'region_id',
                'description'       => 'Availability zones located in the region.'
            ]

        ];
    }

    public function getUniques(): array {
        return [
            ['cloud_provider_id', 'code']
        ];
    }
}
