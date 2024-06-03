<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2024
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

namespace inventory\service;

use equal\orm\Model;

class Detail extends Model {

    public static function getDescription() {
        return 'Detail manages specific elements related to services or products, providing properties for identification, description, value, and links to service and category.';
    }

    public static function getColumns()
    {
        return [
            'name' => [
                'type'              => 'string',
                'unique'            => true,
                'required'          => true,
                'description'       => 'Unique identifier of the detail (ex: code, key, nic, ...).'
            ],

            'description' => [
                'type'              => 'string',
                'description'       => 'Short presentation of the detail element.'
            ],

            'value' => [
                'type'              => 'string',
                'description'       => 'Server attached to the product.'
            ],

            'service_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'inventory\service\Service',
                'description'       => 'Service attached to the detail.'
            ],

            'detail_category_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'inventory\service\DetailCategory',
                'description'       => 'Detail Category attached to the detail.'
            ]
        ];
    }
}
