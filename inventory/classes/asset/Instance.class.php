<?php
namespace symbiose\inventory\asset;


class Instance extends \qinoa\orm\Model {
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
                'foreign_object'    => 'symbiose\inventory\asset\Product'
            ],
            'server_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'symbiose\inventory\asset\Server'
            ],
            'software' => [
                'type'              => 'one2many',            
                'foreign_object'    => 'symbiose\inventory\asset\Software', 
                'foreign_field'     => 'instance_id'
            ],
            'access' => [
                'type'              => 'one2many',            
                'foreign_object'    => 'symbiose\inventory\Access', 
                'foreign_field'     => 'instance_id'
            ]
            
        ];
    }
}