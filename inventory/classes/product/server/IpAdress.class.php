<?php
/*
    This file is part of the eQual framework <http://www.github.com/cedricfrancoys/equal>
    Some Rights Reserved, Cedric Francoys, 2010-2021
    Licensed under GNU GPL 3 license <http://www.gnu.org/licenses/>
*/

namespace inventory\product\server;

use equal\orm\Model;

class IpAdress extends Model {

    public static function getColumns() {
        return [
            'IPV4' => [
                'type'              => 'string',
                'description'       => 'IPV4 adress of the server (32 bits).'
            ],

            'IPV6' => [
                'type'              => 'string',
                'description'       => 'IPV6 adress of the server (128 bits).'
            ],
            'name' => [
                'type'              => 'computed',
                'description'       => 'URL to access the product element.',
                'function'          => 'inventory\product\server\IpAdress::getIP',
                'onchange'          => 'inventory\product\server\IpAdress::getIP',
                'result_type'       => 'string',
                'usage'             => 'uri/url',
                'store'             => true,
                'readonly'          => true 
            ],

            'description' => [
                'type'              => 'string',
                'description'       => 'Short presentation of the Ipadress element.',
                'multilang'         => true
            ],

            'server_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'inventory\product\server\Server',
                'description'       => 'Ip Adress attached to the server.'
            ]
        ];
    }

    public static function getIP($om, $oids, $lang) {
        
        $res = $om->read(__CLASS__, $oids, ['IPV4', 'IPV6','description'], $lang);
      
        $result = [];
        foreach($res as $oid=>$oIP) {
            $result[$oid] = '';
            if($oIP['IPV4']){
                $result[$oid] = $oIP['IPV4'];
            }
            else if($oIP['IPV6']){
                $result[$oid] = $oIP['IPV6'];
            }
            
        }
        ob_start();
        var_dump($res);
        $buff = ob_get_clean();
        trigger_error("QN_DEBUG_ORM::{$buff}", QN_REPORT_ERROR);

        return $result;
    }

}
