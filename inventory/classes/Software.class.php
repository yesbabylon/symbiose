<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2024
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

namespace inventory;

use equal\orm\Model;

class Software extends Model {

    public static function getColumns()
    {
        return [

            'name' => [
                'type'              => 'string',
                'description'       => "Name of the software.",
                'required'          => true
            ],

            'description' => [
                'type'              => 'string',
                'usage'             => 'text/plain',
                'description'       => 'Information about a software.'
            ],

            'edition' => [
                'type'              => 'string',
                'description'       => "Type of edition (CE/EE/Pro/...)."
            ],

            'version' => [
                'type'              => 'string',
                'description'       => "Installed version of the software."
            ],

            'software_model_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'inventory\SoftwareModel',
                'description'       => 'Model of the software.',
                'onupdate'          => 'onupdateSoftwareModelId'
            ],

            'instance_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'inventory\server\Instance',
                'description'       => 'Instance of the software.'
            ],

            'server_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'inventory\server\Server',
                'ondelete'          => 'cascade',
                'description'       => 'Server hosting the software.'
            ],

            'service_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'inventory\service\Service',
                'description'       => 'Service of the software.',
                'dependencies'      => ['product_id'],
            ],

            'customer_id'=> [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\customer\Customer',
                'description'       => 'Owner of the software.',
            ],

            'product_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'inventory\Product',
                'description'       => 'The product to which the software.'
            ],

            'accesses_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'inventory\Access',
                'foreign_field'     => 'software_id',
                'description'       => 'Access information to the server.'
            ]

        ];
    }

    public static function onupdateSoftwareModelId($self) {
        $self->read(['software_model_id' => ['name', 'description', 'edition', 'version']]);
        foreach($self as $id => $software) {
            self::id($id)->update([
                    'name'          => $software['software_model_id']['name'],
                    'description'   => $software['software_model_id']['description'],
                    'edition'       => $software['software_model_id']['edition'],
                    'version'       => $software['software_model_id']['version'],
                ]);
        }
    }


    public static function onchange($self, $event) {
        $result = [];
        if(isset($event['software_model_id'])) {
            $softwareModel = SoftwareModel::id($event['software_model_id'])->read(['name', 'description', 'edition', 'version'])->first();
            $result = [
                    'name'          => $softwareModel['name'],
                    'description'   => $softwareModel['description'],
                    'edition'       => $softwareModel['edition'],
                    'version'       => $softwareModel['version']
                ];
        }
        return $result;
    }

}
