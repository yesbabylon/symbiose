<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2024
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
                'usage'             => 'text/plain',
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
                'usage'             => 'url',
                'description'       => 'The URL to access.',
                'function'          => 'calUrl',
                'store'             => true,
                'instant'           => true
            ],

            'host' => [
                'type'              => 'string',
                'description'       => 'IP address or hostname of the server.',
                'required'          => true,
                'dependents'        => ['url']
            ],

            'port' => [
                'type'              => 'string',
                'description'       => 'Port to connect to (default based on protocol).',
                'dependents'        => ['url']
            ],

            'username' => [
                'type'              => 'string',
                'description'       => 'Username of the account related to this access.',
                'required'          => true,
                'dependents'        => ['url']
            ],

            'password' => [
                'type'              => 'string',
                'required'          => true,
                'description'       => 'Password of the account related to this access.',
                'help'              => 'The password is arbitrary and depends on the application logic of the host (so there are no constraints on it).'
            ],

            'server_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'inventory\server\Server',
                'description'       => 'Server to which the access belongs.',
                'readonly'          => true,
                'visible'           => ['server_id', '<>', null]
            ],

            'software_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'inventory\server\Software',
                'description'       => 'Software to which the access belongs.',
                'readonly'          => true,
                'visible'           => ['software_id', '<>', null]
            ],

            'instance_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'inventory\server\Instance',
                'description'       => 'Instance to which the access belongs.',
                'readonly'          => true,
                'visible'           => ['instance_id', '<>', null]
            ],

            'service_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'inventory\service\Service',
                'description'       => 'Service to which the access belongs.',
                'readonly'          => true,
                'visible'           => ['service_id', '<>', null]
            ]

        ];
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

    public static function calUrl($self) {
        $result = [];
        $self->read(['port', 'host', 'type', 'username', 'password']);
        foreach($self as $id => $access) {
            $result[$id] = self::createUrl($access);
        }
        return $result;
    }

    public static function onchange($event, $values) {
        $result = [];

        if(isset($event['type'], self::MAP_PORTS[$event['type']])) {
            $result['port'] = self::MAP_PORTS[$event['type']];
        }

        if(
            isset($event['type'])
            || isset($event['username'])
            || isset($event['password'])
            || isset($event['host'])
        ) {
            $result['url'] = self::createUrl([
                'type'     => $event['type'] ?? $values['type'],
                'username' => $event['username'] ?? $values['username'],
                'password' => $event['password'] ?? $values['password'],
                'host'     => $event['host'] ?? $values['host'],
                'port'     => $result['port'] ?? ''
            ]);
        }

        return $result;
    }

    private static function createUrl($access) {
        return sprintf(
            '%s://%s:%s@%s',
            $access['type'],
            $access['username'],
            $access['password'],
            self::createAuthority($access)
        );
    }

    private static function createAuthority($access) {
        $authority = $access['host'];
        if(!empty($access['port'])) {
            $authority .= ':'.$access['port'];
        }

        return $authority;
    }
}
