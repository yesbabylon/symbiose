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
                'onchange'          => 'inventory\product\server\IpAdress::getIP',
                'description'       => 'IPV4 adress of the server (32 bits).'
            ],

            'IPV6' => [
                'type'              => 'string',
                'onchange'          => 'inventory\product\server\IpAdress::getIP',
                'description'       => 'IPV6 adress of the server (128 bits).'
            ],
            'name' => [
                'type'              => 'computed',
                'description'       => 'name to access the product element.',
                'function'          => 'inventory\product\server\IpAdress::getIP',
                'result_type'       => 'string',
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
                $om->write(__CLASS__, $oid, ['name' => $oIP['IPV4']]);
            }
            else if($oIP['IPV6']){
                $result[$oid] = $oIP['IPV6'];
                $om->write(__CLASS__, $oid, ['name' => $oIP['IPV6']]);
            }  
        }
        
        return $result;
    }

    // public static function getIPV4($om, $oids, $lang) {    
    //     $res = $om->read(__CLASS__, $oids, ['IPV4', 'IPV6','description'], $lang);
    //     $result = [];
    //     foreach($res as $oid=>$oIP) {       
    //         if($oIP['IPV4']){  
    //             $om->write(__CLASS__, $oid, ['name' => $oIP['IPV4']]);
    //         }
    //         else if($oIP['IPV6']){
    //             $om->write(__CLASS__, $oid, ['name' => $oIP['IPV6']]);
    //         }  
    //         $result[$oid] = $oIP['IPV4'];
    //     }
       
    //     return $result;
    // }

    // public static function getIPV6($om, $oids, $lang) {    
    //     $res = $om->read(__CLASS__, $oids, ['IPV4', 'IPV6','description'], $lang);
    //     $result = [];
    //     foreach($res as $oid=>$oIP) {
    //         if($oIP['IPV4']){  
    //             $om->write(__CLASS__, $oid, ['name' => $oIP['IPV4']]);
    //         }
    //         else if($oIP['IPV6']){
    //             $om->write(__CLASS__, $oid, ['name' => $oIP['IPV6']]);
    //         }  
    //         $result[$oid] = $oIP['IPV6'];
    //     }
        
    //     return $result;
    // }

}
