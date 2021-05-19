<?php
namespace symbiose\identity;
use qinoa\orm\Model;

class Contact extends Model {
    public static function getColumns() {
        /**
         * A Contact is a natural person that is related to an Organisation.
         */
        return [
            'name' => [
                'type'             => 'alias',
                'alias'            => 'display_name'
            ],
            'display_name' => [
                'type'              => 'computed',
                'function'          => 'symbiose\identity\Contact::getDisplayName',
                'result_type'       => 'string',
                'store'             => true,
                'description'       => 'The display name of the contact (concatenation of first and last names).'
            ],
            'type' => [
                'type'          => 'string',
                'selection'     => ['contact', 'invoice', 'delivery', 'other'],
                'description'   => 'Role of the contact.',
                'default'       => 'contact'
            ],

            /*
                Description of the Contact address
            */
            'address_street' => [
                'type'          => 'string',
                'description'   => 'Street and number.'
            ],
            'address_dispatch' => [
                'type'          => 'string',
                'description'   => 'Optional info for mail dispatch (appartment, box, floor, ...).'
            ],
            'address_city' => [
                'type'          => 'string',
                'description'   => 'City.'
            ],
            'address_zip' => [
                'type'          => 'string',
                'description'   => 'Postal code.'
            ],
            'address_country' => [
                'type'          => 'string',
                'usage'         => 'country/iso-3166:2',
                'description'   => 'Country.' 
            ],

            'firstname' => [
                'type'              => 'string',
                'description'       => "Full name of the contact (must be a person, not a role).",
                'required'          => true                
            ],
            'lastname' => [
                'type'              => 'string',
                'description'       => 'Reference contact surname.'
            ],
            'gender' => [
                'type'              => 'string',
                'selection'         => ['M' => 'Male', 'F' => 'Female'],
                'description'       => 'Reference contact gender.'
            ],
            'title' => [
                'type'              => 'string',
                'selection'         => ['Dr' => 'Doctor', 'Ms' => 'Miss', 'Mrs' => 'Misses', 'Mr' => 'Mister', 'Pr' => 'Professor'],
                'description'       => 'Reference contact gender.'
            ],
            'email' => [
                'type'              => 'string',
                'usage'             => 'email',                
                'description'       => "Email address of the contact."
            ],
            'phone' => [
                'type'              => 'string',
                'usage'             => 'phone',
                'description'       => "Phone number of the contact, if any."
            ],            
             'organisation_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'symbiose\identity\Organisation',
                'description'       => "The organisation the contact belongs to.",
                'required'          => true
            ]
        ];
    }


    public static function getDisplayName($om, $oids, $lang) {
        $result = [];
        $res = $om->read(__CLASS__, $oids, ['firstname', 'lastname']);
        foreach($res as $oid => $odata) {
            $display_name = "{$odata['firstname']} {$odata['lastname']}";
            $result[$oid] = $display_name;
        }
        return $result;              
    }
}