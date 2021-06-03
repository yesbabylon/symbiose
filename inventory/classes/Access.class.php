<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace inventory;
use equal\orm\Model;

class Access extends Model {
    public static function getColumns() {
        /**
        *
        */
        return [
            'name' => [
                'type'              => 'string',
                'description'       => "Short description of the access to serve as memo",
                'required'          => true
            ],
            'protocol' => [
                'type'              => 'string',
                'description'       => "protocol to use",
                'selection'         => ['smtp', 'ftp', 'ssh', 'pop', 'git', 'admin'],
                'required'          => true
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
                'foreign_object'    => 'inventory\asset\Server'
            ],
            'instance_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'inventory\asset\Instance'
            ],
            
        ];
    }
}