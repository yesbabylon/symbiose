<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace inventory\asset;
use equal\orm\Model;

class Server extends Model {
    public static function getColumns() {
        /**
        *
        */
        return [
            'hostname' => [
                'type'              => 'string',
                'description'       => "hostname of the server, if any"
            ],
            'IPv4' => [
                'type'              => 'string',
                'description'       => "Email address of the contact",
                'required'          => true
            ],
            'IPv6' => [
                'type'              => 'string',
                'description'       => "Phone number of the contact, if any",
                'required'          => true                
            ],
            'description' => [
                'type'              => 'string',
                'description'       => "memo to identify server (tech specs, hosting plan, ...)"
            ],
            'access' => [
                'type'              => 'one2many',            
                'foreign_object'    => 'inventory\Access', 
                'foreign_field'     => 'server_id'
            ]
        ];
    }
}