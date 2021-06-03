<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace inventory\asset;
use equal\orm\Model;

class Instance extends Model {
    public static function getColumns() {
        /**
        *
        */
        return [
            'type' => [
                'type'              => 'string',
                'selection'         => ['dev', 'staging','prod','replica'],
                'description'       => "type of instance"
            ],
            'url' => [
                'type'              => 'string',
                'description'       => "home URL for front-end, if any"
            ],
            'description' => [
                'type'              => 'string',
                'description'       => "memo to identify server (tech specs, hosting plan, ...)"
            ],
            'product_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'inventory\asset\Product'
            ],
            'server_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'inventory\asset\Server'
            ],
            'software' => [
                'type'              => 'one2many',            
                'foreign_object'    => 'inventory\asset\Software', 
                'foreign_field'     => 'instance_id'
            ],
            'access' => [
                'type'              => 'one2many',            
                'foreign_object'    => 'inventory\Access', 
                'foreign_field'     => 'instance_id'
            ]
            
        ];
    }
}