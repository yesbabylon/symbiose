<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
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
                'description'       => 'Server of the software.'
            ],

            'service_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'inventory\service\Service',
                'description'       => 'Service of the software.'
            ],

            'customer_id'=> [
                'type'              => 'many2one',
                'foreign_object'    => 'inventory\sale\customer\Customer',
                'description'       => 'Customer of the software.',
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
