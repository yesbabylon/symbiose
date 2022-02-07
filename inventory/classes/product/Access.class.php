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
                'selection'         => ['www', 'ssh', 'ftp', 'sftp', 'pop', 'smtp', 'git', 'docker'],
                'description'       => 'Type of the access',
                'required'          => true
            ],

            'url' => [
                'type'              => 'computed',
                'description'       => 'URL to access the product element.',
                'function'          => 'inventory\product\Access::getUrl',
                'result_type'       => 'string',
                'usage'             => 'uri/url',
                'onchange'          => 'inventory\product\Access::getUrl',
                'store'             => false,
                'readonly'          => true
            ],

            'host' => [
                'type'              => 'string',
                'description'       => "IP address or hostname¨of the server",
                'required'          => true
            ],

            'port' => [
                'type'              => 'string',
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
        $res = $om->read(__CLASS__, $oids, ['port', 'host', 'type']);
    
        $result = [];
        foreach($res as $oid=>$ourl) {
            $content = $ourl['type'].'.'.$ourl['host'].':'.$ourl['port'];
            $result[$oid] = $content;
        }   
        return $result;  
    }
}
