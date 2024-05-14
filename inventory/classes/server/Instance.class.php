<?php
/*
    This file is part of the eQual framework <http://www.github.com/cedricfrancoys/equal>
    Some Rights Reserved, Cedric Francoys, 2010-2024
    Licensed under GNU GPL 3 license <http://www.gnu.org/licenses/>
*/

namespace inventory\server;

use equal\orm\Model;

class Instance extends Model {

    public static function getDescription() {
        return 'Instance running on a server, for example a Docker instance.';
    }

    public static function getColumns()
    {
        return [

            'name'    => [
                'type'              => 'string',
                'unique'            => true,
                'required'          => true,
                'description'       => 'Unique identifier of the instance.'
            ],

            'description' => [
                'type'              => 'string',
                'usage'             => 'text/plain',
                'description'       => 'Short description of the instance.'
            ],

            'type' => [
                'type'              => 'string',
                'selection'         => ['dev', 'staging', 'prod', 'replica'],
                'description'       => 'Type of instance.',
                'default'           => 'dev'
            ],

            'version' => [
                'type'              => 'string',
                'selection'         => ['development', 'staging', 'preview', 'production'],
                'description'       => 'Branch version.',
                'default'           => 'development'
            ],

            'branch' => [
                'type'              => 'string',
                'description'       => 'Branch used by the instance.'
            ],

            'url' => [
                'type'              => 'string',
                'description'       => 'Front-end home URL.'
            ],

            'product_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'inventory\Product',
                'description'       => 'Product the instance belongs to.',
                'required'          => true
            ],

            'server_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'inventory\server\Server',
                'description'       => 'Server on which the instance runs.',
                'ondelete'          => 'cascade',
                'required'          => true
            ],

            'accesses_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'inventory\Access',
                'foreign_field'     => 'instance_id',
                'description'       => 'Information about how to access the instance.'
            ],

            'softwares_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'inventory\Software',
                'foreign_field'     => 'instance_id',
                'description'       => 'Information about softwares running on the instance.'
            ]

        ];
    }
}
