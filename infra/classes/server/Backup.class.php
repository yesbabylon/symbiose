<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2025
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

namespace infra\server;

use equal\orm\Model;

class Backup extends Model {

    public static function getColumns() {
        return [

            'name' => [
                'type'              => 'string',
                'description'       => 'The name of the backup',
                'required'          => true
            ],

            'backup_time' => [
                'type'              => 'datetime',
                'description'       => 'The moment the backup was created on.',
                'required'          => true
            ],

            'server_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'infra\server\Server',
                'description'       => 'Server attached to the backup.',
                'ondelete'          => 'cascade',
                'dependents'        => ['server_type', 'cloud_project_id'],
                'required'          => true
            ],

            'server_type' => [
                'type'              => 'computed',
                'result_type'       => 'string',
                'relation'          => ['server_id' => 'server_type'],
                'store'             => true,
                'visible'           => false
            ],

            'cloud_project_id' => [
                'type'              => 'computed',
                'result_type'       => 'many2one',
                'foreign_object'    => 'infra\cloud\CloudProject',
                'description'       => 'Cloud project of the server hosting the backup.',
                'relation'          => ['server_id' => 'cloud_project_id'],
                'store'             => true,
                'visible'           => false
            ]

        ];
    }
}
