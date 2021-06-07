<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace identity;
use equal\orm\Model;


class Address extends Model {

    public static function getName() {
        return "Address";
    }

    public static function getDescription() {
        return "An address is a physical location at which an identity can be contacted.";
    }

    public static function getColumns() {
        return [
            'name' => [
                'type'             => 'alias',
                'alias'            => 'display_name'
            ],

            'display_name' => [
                'type'              => 'computed',
                'function'          => 'identity\Address::getDisplayName',
                'result_type'       => 'string',
                'store'             => true,
                'description'       => 'The display name of the adress.'
            ],

            'identity_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'identity\Identity',
                'description'       => 'The identity that the address relates to.' 
            ],

            'role' => [
                'type'              => 'string',
                'selection'         => [ 'legal', 'invoice', 'delivery', 'other' ],
                'description'       => 'The main reason for which the address is meant to be used.' 
            ],

            /*
                Description of the address.
            */
            'address_street' => [
                'type'              => 'string',
                'description'       => 'Street and number.'
            ],
            'address_dispatch' => [
                'type'              => 'string',
                'description'       => 'Optional info for mail dispatch (appartment, box, floor, ...).'
            ],
            'address_city' => [
                'type'              => 'string',
                'description'       => 'City.'
            ],
            'address_zip' => [
                'type'              => 'string',
                'description'       => 'Postal code.'
            ],
            'address_state' => [
                'type'              => 'string',
                'description'       => 'State or region.'
            ],
            'address_country' => [
                'type'              => 'string',
                'usage'             => 'country/iso-3166:2',
                'description'       => 'Country.' 
            ],


        ];
    }

    public static function getDisplayName($om, $oids, $lang) {
        $result = [];
        $res = $om->read(__CLASS__, $oids, ['address_street', 'address_city', 'address_zip', 'address_country' ]);
        foreach($res as $oid => $odata) {
            $result[$oid] = "{$odata['address_street']} {$odata['address_zip']} {$odata['address_city']}";
        }
        return $result;              
    }
 
}