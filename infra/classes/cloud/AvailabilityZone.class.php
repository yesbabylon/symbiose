<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2026
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

namespace infra\cloud;

use equal\orm\Model;

class AvailabilityZone extends Model {

    public static function getDescription(): string {
        return 'Failure-isolated availability zone located within a cloud region.';
    }

    public static function getColumns(): array {
        return [

            'code' => [
                'type'              => 'string',
                'usage'             => 'text/plain:64',
                'description'       => 'Technical code unique within the region.',
                'required'          => true
            ],

            'name' => [
                'type'              => 'string',
                'description'       => 'Human-readable name of the availability zone.',
                'required'          => true
            ],

            'cloud_provider_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'infra\cloud\CloudProvider',
                'description'       => 'Cloud provider operating the region.',
                'required'          => true
            ],

            'region_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'infra\cloud\Region',
                'description'       => 'Region containing the availability zone.',
                'required'          => true,
                'domain'            => ['cloud_provider_id', '=', 'object.cloud_provider_id'],
            ]

        ];
    }

    public function getUniques(): array {
        return [
            ['region_id', 'code']
        ];
    }
}
