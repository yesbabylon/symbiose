<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2026
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

namespace infra\cloud;

use equal\orm\Model;

class CloudProvider extends Model {

    public static function getDescription(): string {
        return 'Cloud provider that operates geographic regions.';
    }

    public static function getColumns(): array {
        return [

            'code' => [
                'type'              => 'string',
                'usage'             => 'text/plain:64',
                'description'       => 'Unique technical code of the cloud provider.',
                'required'          => true,
                'unique'            => true
            ],

            'name' => [
                'type'              => 'string',
                'description'       => 'Human-readable name of the cloud provider.',
                'required'          => true
            ],

            'regions_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'infra\cloud\Region',
                'foreign_field'     => 'cloud_provider_id',
                'description'       => 'Geographic regions operated by the cloud provider.'
            ]

        ];
    }
}
