<?php
/*
    This file is part of the eQual framework <http://www.github.com/cedricfrancoys/equal>
    Some Rights Reserved, Cedric Francoys, 2010-2021
    Licensed under GNU GPL 3 license <http://www.gnu.org/licenses/>
*/

namespace inventory\server;

use equal\orm\Model;

class Instance extends Model {

    public static function getColumns()
    {
        return [
            'name'    => [
                'type'              => 'string',
                'unique'            => true,
                'required'          => true,
                'description'       => 'Unique identifier of the instance.'
            ],

            'version' => [
                'type'              => 'string',
                'description'       => 'Version of the instance.',
                'selection'         => ['production', 'preview', 'staging', 'development'],
                'default'           => 'development'
            ],

            'description' => [
                'type'              => 'string',
                'description'       => 'Short presentation of the instance.'
            ],

            'type' => [
                'type'              => 'string',
                'selection'         => ['dev', 'staging', 'prod', 'replica'],
                'default'           => 'dev',
                'description'       => "Type of instance."
            ],

            'branch' => [
                'type'              => 'string',
                'description'       => 'Branch on which the instance resides.'
            ],

            'url' => [
                'type'              => 'string',
                'description'       => "Home URL for front-end."
            ],
            'accesses_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'inventory\Access',
                'foreign_field'     => 'instance_id',
                'description'       => 'Access information to the server.'
            ],
            'softwares_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'inventory\Software',
                'foreign_field'     => 'instance_id',
                "description"       => 'Softwares installed on the Instance.'
            ],

            'server_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'inventory\server\Server',
                'ondelete'          => 'cascade',
                'description'       => 'Server to which the instance belongs.'
            ],
        ];
    }
}
