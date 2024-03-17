<?php
/*
    This file is part of the eQual framework <http://www.github.com/cedricfrancoys/equal>
    Some Rights Reserved, Cedric Francoys, 2010-2021
    Licensed under GNU GPL 3 license <http://www.gnu.org/licenses/>
*/

namespace inventory\server;

use equal\orm\Model;

class Server extends Model {


    public static function getColumns()
    {
        return [
            'name' => [
                'type'              => 'string',
                'description'       => 'Internal identification ex. trg.be-master.',
                'unique'            => true
            ],

            'description' => [
                'type'              => 'string',
                'description'       => 'Short description of the Server.',
            ],

            'type' => [
                'type'              => 'string',
                'description'       => 'Type of the server.',
                'selection'         => ['front', 'node', 'storage'],
                'default'           => 'front'
            ],

            'accesses_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'inventory\Access',
                'foreign_field'     => 'server_id',
                'description'       => 'Access information to the server.'
            ],

            'instances_ids' => [
                'type'              => 'one2many',
                'foreign_field'     => 'server_id',
                'foreign_object'    => 'inventory\server\Instance',
                'ondetach'          => 'delete',
                'description'       => 'Instances running on the server.'
            ],

            'product_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'inventory\Product',
                'ondelete'          => 'cascade',
                'description'       => 'Product the server belongs to.'
            ],

            'ip_address_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'inventory\server\IpAddress',
                'foreign_field'     => 'server_id',
                'ondetach'          => 'delete',
                'description'       => 'IP Addresses of the server.'
            ],

            'softwares_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'inventory\server\Software',
                'foreign_field'     => 'server_id',
                'ondetach'          => 'delete',
                'description'       => 'Softwares installed on the server.'
            ],
        ];
    }
}
