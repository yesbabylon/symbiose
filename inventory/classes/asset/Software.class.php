<?php
namespace inventory\asset;
use equal\orm\Model;

class Software extends Model {
    public static function getColumns() {
        /**
        *
        */
        return [
            'name' => [
                'type'              => 'string',
                'description'       => "Name of tje software",
                'required'          => true
            ],
            'edition' => [
                'type'              => 'string',
                'description'       => "Type of edition (CE/EE/Pro/...), if any"
            ],
            'version' => [
                'type'              => 'string',
                'description'       => "installed version of the software"
            ],
            'instance_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'inventory\asset\Instance'
            ]            
        ];
    }
}