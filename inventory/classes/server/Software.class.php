<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

namespace inventory\server;

use equal\orm\Model;
use inventory\service\Service;

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

}
