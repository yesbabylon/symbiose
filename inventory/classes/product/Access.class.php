<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

namespace inventory\product;

use equal\orm\Model;

class Access extends Model {
    public static function getColumns()
    {
        /**
         *
         */
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
                'onupdate'          => 'getUrl',
                'onupdate'          => 'getType',
                'description'       => 'Type of the access',
                'required'          => true
            ],

            'url' => [
                'type'              => 'computed',
                'description'       => 'URL to access the product element.',
                'function'          => 'getUrl',
                'result_type'       => 'string',
                'usage'             => 'uri/url',
                'store'             => true,
                'readonly'          => true
            ],

            'host' => [
                'type'              => 'string',
                'onupdate'          => 'getUrl',
                'description'       => "IP address or hostname¨of the server",
                'required'          => true
            ],

            'port' => [
                'type'              => 'string',
                'onupdate'          => 'getUrl',
                'description'       => "port to connect to (default based on protocol)"
            ],

            'username' => [
                'type'              => 'string',
                'description'       => "username of the account related to this access"
            ],

            'password' => [
                'type'              => 'string',
                'usage'             => 'password',
                'description'       => "Password of the account related to this access"
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
                'description'       => 'Service to which the access belongs.',
                'description'       => "Service Access"
            ],

        ];    
    }


    public static function getUrl($om, $oids) {
        $res = $om->read(__CLASS__, $oids, ['port', 'host', 'type', 'username', 'password']);
        foreach($res as $oid=>$ourl) {
            $content = $ourl['type'].'://'.$ourl['username'].':'.$ourl['password'].'@'.$ourl['host'].($ourl['port']?':'.$ourl['port']:'');
            $om->write(__CLASS__, $oids, ['url' => $content]);
        }      
    }

    public static function getType($om, $oids) {
        $res = $om->read(__CLASS__, $oids, ['type']);
        foreach($res as $oid=>$otype) {
            $defaultPort = ['http' => '80', 
                            'https' => '443', 
                            'ssh' => '22', 
                            'ftp' => '20', 
                            'sftp' => '22', 
                            'pop' => '110', 
                            'smtp' => '25', 
                            'git' => '9418', 
                            'docker' => '2345',
                            ];
            $content = $defaultPort[$otype['type']];
            $om->write(__CLASS__, $oids, ['port' => $content]);
        }      
    }
}
