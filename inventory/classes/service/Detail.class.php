<?php
/*
    This file is part of the eQual framework <http://www.github.com/cedricfrancoys/equal>
    Some Rights Reserved, Cedric Francoys, 2010-2021
    Licensed under GNU GPL 3 license <http://www.gnu.org/licenses/>
*/

namespace inventory\service;

use equal\orm\Model;

class Detail extends Model {

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
