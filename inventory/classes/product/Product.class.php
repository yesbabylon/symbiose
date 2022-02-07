<?php
/*
    This file is part of the eQual framework <http://www.github.com/cedricfrancoys/equal>
    Some Rights Reserved, Cedric Francoys, 2010-2021
    Licensed under GNU GPL 3 license <http://www.gnu.org/licenses/>
*/

namespace inventory\product;

use equal\orm\Model;

class Product extends Model {

    public static function getColumns() 
    {
        return [
            'name' => [
                'type'              => 'string',
                'unique'            => true
            ],

            'description' => [
                'type'              => 'string',
                'description'       => 'Short presentation of the product.',
                'multilang'         => true
            ],

            'servers_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'inventory\product\server\Server',
                'foreign_field'     => 'product_id',
                'description'       => 'Server used by product.'
            ],

            'services_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'inventory\product\service\Service',
                'foreign_field'     => 'product_id',
                'description'       => 'Services used by product.'
            ]
        ];
    }
}
