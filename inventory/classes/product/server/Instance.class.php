<?php
/*
    This file is part of the eQual framework <http://www.github.com/cedricfrancoys/equal>
    Some Rights Reserved, Cedric Francoys, 2010-2021
    Licensed under GNU GPL 3 license <http://www.gnu.org/licenses/>
*/

namespace inventory\product\server;

use equal\orm\Model;

class Instance extends Model {

    public static function getColumns()
    {
        return [
            'name'    => [
                'type'              => 'string',
                'unique'            => true,
                'description'       => 'Unique identifier of the instance.'
            ],

            'version' => [
                'type'              => 'string',
                'description'       => 'Version of the instance.',
                'selection'         => ['production', 'preview', 'staging', 'development']
            ],

            'description' => [
                'type'              => 'string',
                'description'       => 'Short presentation of the instance.',
                'multilang'         => true
            ],

            'type' => [
                'type'              => 'string',
                'selection'         => ['dev', 'staging', 'prod', 'replica'],
                'description'       => "type of instance"
            ],

            'branch' => [
                'type'              => 'string',
                'description'       => 'Branch on which the instance resides.'
            ],

            'url' => [
                'type'              => 'string',
                'description'       => "home URL for front-end, if any"
            ],

            'access_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'inventory\product\Access',
                'foreign_field'     => 'instance_id',
                'description'       => 'Access informations to the server.'
            ],

            'softwares_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'inventory\product\server\Software',
                'foreign_field'     => 'instance_id',
                "description"       => 'Softwares installed on the Instance.'
            ],

            'server_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'inventory\product\server\Server',
                'description'       => 'Server to which the instance belongs (Location of the Instance).'
            ],
        ];
    }
}
