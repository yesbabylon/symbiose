<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

namespace inventory;

use equal\orm\Model;

class Access extends Model {

    const MAP_PORTS = [
        'http'      => '80',
        'https'     => '443',
        'ssh'       => '22',
        'ftp'       => '21',
        'sftp'      => '22',
        'pop'       => '110',
        'smtp'      => '25',
        'git'       => '9418',
        'docker'    => '2345',
    ];

    public static function getColumns() {
        return [
            'name' => [
                'type'              => 'string',
                'description'       => 'Name of the access.',
                'required'          => true
            ],

            'description' => [
                'type'              => 'string',
                'description'       => 'Short description of the access.'
            ],

            'type' => [
                'type'              => 'string',
                'selection'         => ['http', 'https', 'ssh', 'ftp', 'sftp', 'pop', 'smtp', 'git', 'docker'],
                'description'       => 'Type of the access.',
                'required'          => true,
                'onupdate'          => 'onupdateType'

            ],

            'url' => [
                'type'              => 'computed',
                'result_type'       => 'string',
                'description'       => 'The URL to access.',
                'function'          => 'calUrl',
                'store'             => true,
                'instant'           => true
            ],

            'host' => [
                'type'              => 'string',
                'description'       => 'IP address or hostname¨of the server.',
                'required'          => true,
                'dependencies'      => ['url']
            ],

            'port' => [
                'type'              => 'string',
                'description'       => 'Port to connect to (default based on protocol).',
                'dependencies'      => ['url']
            ],

            'username' => [
                'type'              => 'string',
                'description'       => 'Username of the account related to this access.',
                'required'          => true,
                'dependencies'      => ['url']
            ],

            'password' => [
                'type'              => 'string',
                'usage'             => 'password',
                'required'          => true,
                'description'       => 'Password of the account related to this access.'

            ],

            'server_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'inventory\server\Server',
                'description'       => 'Server to which the instance belongs.'
            ],

            'software_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'inventory\server\Software',
                'description'       => 'Software to which the access belongs.'
            ],

            'instance_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'inventory\server\Instance',
                'description'       => 'Instance to which the access belongs.'
            ],

            'service_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'inventory\service\Service',
                'description'       => 'Service to which the access belongs.'
            ],

        ];
    }


    public static function calUrl($self) {
        $result = [];
        $self->read(['port', 'host', 'type', 'username', 'password']);
        foreach($self as $id => $access) {
            $result[$id] = $access['type'].'://'.$access['username'].':'.$access['password'].'@'.$access['host'].($access['port']?':'.$access['port']:'');
        }
        return $result;
    }

    public static function onupdateType($self) {
        $self->read(['type', 'port']);
        foreach($self as $id => $access) {
            if(isset(self::MAP_PORTS[$access['type']])) {
                $port = self::MAP_PORTS[$access['type']];
                if($access['port'] != $port) {
                    self::id($id)->update(['port' => $port]);
                }
            }
        }
    }

    public static function onchange($event,$values) {
        $result = [];
        $port = null;
        if(isset($event['type'])) {
            if(isset(self::MAP_PORTS[$event['type']])) {
                $port=self::MAP_PORTS[$event['type']];
                $result['port'] = $port;
            }

        }

        if(isset($event['type']) || isset($event['username']) || isset($event['password']) || isset($event['host']) ) {

            $type = (string) (isset($event['type']))?$event['type']:$values['type'];
            $username = (string) (isset($event['username']))?$event['username']:$values['username'];
            $password = (string) (isset($event['password']))?$event['password']:$values['password'];
            $host = (string) (isset($event['host']))?$event['host']:$values['host'];
            $port = ($port?':'.$port:'');
            $result['url']=$type.'://'.$username.':'.$password.'@'.$host. $port;
        }

        return $result;
    }
}