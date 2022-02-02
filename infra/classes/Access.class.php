<?php
/*
    This file is part of the eQual framework <http://www.github.com/cedricfrancoys/equal>
    Some Rights Reserved, Cedric Francoys, 2010-2021
    Licensed under GNU GPL 3 license <http://www.gnu.org/licenses/>
*/

namespace infra;

use equal\orm\Model;

class Access extends Model
{

    public static function getColumns()
    {
        return [
            'type' => [
                'type'              => 'string',
                'selection'         => ['www', 'ssh', 'ftp', 'sftp', 'pop', 'smtp', 'git', 'docker']
            ],
            'description' => [
                'type'              => 'string',
                'description'       => 'Access informations about',
                'multilang'         => true
            ],
            'password' => [
                'type'              => 'string',
                'usage'             => 'password',
                'onchange'          => 'core\User::onchangePassword',
                'required'          => true
            ],
            'username' => [
                'type'              => 'string',
                'usage'             => 'email',
                'required'          => true,
                'unique'            => true
            ],
            'host' => [
                'type'              => 'string',
                'description'       => 'Host of the access'
            ],
            'port' => [
                'type'              => 'string',
                'description'       => 'Port of the access'
            ],
            'service_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'infra\Service',
                'description'       => 'Service to which the access belongs.'
            ],
            'server_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'infra\Server',
                'description'       => 'Server to which the access belongs.'
            ],
            'instance_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'infra\Instance',
                'description'       => 'Instance to which the access belongs.'
            ],
        ];
    }
}
