<?php
/*
    This file is part of the eQual framework <http://www.github.com/cedricfrancoys/equal>
    Some Rights Reserved, Cedric Francoys, 2010-2021
    Licensed under GNU GPL 3 license <http://www.gnu.org/licenses/>
*/

namespace inventory\product\server;

use equal\orm\Model;

class Server extends Model {

    public static function getColumns()
    {
        return [
            'name' => [
                'type'              => 'string',
                'description'       => 'internal identification ex. trg.be-master',
                'unique'            => true
            ],

            'description' => [
                'type'              => 'string',
                'description'       => 'Short description of the Server Instance (it could be considered as an Instance).',
                'multilang'         => true
            ],

            'type' => [
                'type'              => 'string',
                'description'       => 'Type of the server.',
                'selection'         => ['front', 'node', 'storage']
            ],

            'access_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'inventory\product\Access',
                'foreign_field'     => 'server_id',
                'description'       => 'Access informations to the server.'
            ],

            'instances_ids' => [
                'type'              => 'one2many',
                'foreign_field'     => 'server_id',
                'foreign_object'    => 'inventory\product\server\Instance',
                'description'       => 'Product attached to the server.'
            ],

            'product_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'inventory\product\Product',
                'description'       => 'Product attached to the server.'
            ],

            'ip_adress_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'inventory\product\server\IpAdress',
                'foreign_field'     => 'server_id',
                'description'       => 'IP Adresses of the server.'
            ],

            'softwares_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'inventory\product\server\Software',
                'foreign_field'     => 'server_id',
                'description'       => 'Softwares installed on the server instance.'
            ],
        ];
    }
}
