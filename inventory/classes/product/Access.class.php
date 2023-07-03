<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

namespace inventory\product;

use equal\orm\Model;

class Access extends Model {

    public static function getColumns() {
        return [
            'name' => [
                'type'              => 'string',
                'description'       => "Short description of the access to serve as memo",
                'required'          => true
            ],

            'description' => [
                'type'              => 'string',
                'description'       => 'Subscribed service - ovh pro2014, VPS2018',
                'multilang'         => true
            ],

            'type' => [
                'type'              => 'string',
                'selection'         => ['http', 'https', 'ssh', 'ftp', 'sftp', 'pop', 'smtp', 'git', 'docker'],
                'onupdate'          => 'onupdateType',
                'description'       => 'Type of the access',
                'required'          => true
            ],

            'url' => [
                'type'              => 'computed',
                'description'       => 'URL to access the product element.',
                'function'          => 'calcUrl',
                'result_type'       => 'string',
                'usage'             => 'uri/url',
                'store'             => true,
                'readonly'          => true
            ],

            'host' => [
                'type'              => 'string',
                'description'       => "IP address or hostname¨of the server",
                'required'          => true,
                'dependencies'      => ['url']
            ],

            'port' => [
                'type'              => 'string',
                'description'       => "port to connect to (default based on protocol)",
                'dependencies'      => ['url']
            ],

            'username' => [
                'type'              => 'string',
                'description'       => "username of the account related to this access",
                'dependencies'      => ['url']
            ],

            'password' => [
                'type'              => 'string',
                'usage'             => 'password',
                'description'       => "Password of the account related to this access",
                'dependencies'      => ['url']
            ],

            'server_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'inventory\product\server\Server',
                'description'       => "Server Access"
            ],

            'instance_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'inventory\product\server\Instance',
                'description'       => "Instance Access"
            ],

            'service_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'inventory\product\service\Service',
                'description'       => 'Service to which the access belongs.'
            ]

        ];
    }


    public static function calcUrl($self) {
        $self->read(['port', 'host', 'type', 'username', 'password']);
        foreach($self as $id => $access) {
            $url = $access['type'].'://'.$access['username'].':'.$access['password'].'@'.$access['host'].($access['port']?':'.$access['port']:'');
            self::id($id)->update(['url' => $url]);
        }
    }

    public static function onupdateType($self) {
        $self->read(['type']);
        foreach($self as $id => $access) {
            $map_ports = [
                    'http'      => '80',
                    'https'     => '443',
                    'ssh'       => '22',
                    'ftp'       => '20',
                    'sftp'      => '22',
                    'pop'       => '110',
                    'smtp'      => '25',
                    'git'       => '9418',
                    'docker'    => '2345',
                ];
            if(isset($map_ports[$access['type']])) {
                $default_port = $map_ports[$access['type']];
                $self::id($id)->update(['port' => $default_port]);
            }
        }
    }
}
