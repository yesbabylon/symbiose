<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace sale\booking;
use equal\orm\Model;

class CompositionItem extends Model {
    public static function getColumns() {

        /**
         * Composition items are details about a person that is part of a booking and stays at least one night.
         */
        return [
            'name' => [
                'type'              => 'computed',
                'function'          => 'sale\booking\CompositionItem::getDisplayName',
                'result_type'       => 'string',
                'store'             => true,
                'description'       => 'The display name of the person (concatenation of first and last names).'
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
                'selection'         => ['M' => 'Male', 'F' => 'Female', 'X' => 'Non-binary'],
                'description'       => 'Reference contact gender.'
            ],

            'date_of_birth' => [
                'type'          => 'date',
                'description'   => 'Date of birth of the person.'
            ],

            /* in some cases we need additional contact details */
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

             'rental_unit_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'realestate\RentalUnit',
                'description'       => "The rental unit the person is assigned to.",
                'required'          => true
            ]
        ];
    }


    public static function getDisplayName($om, $oids, $lang) {
        $result = [];
        $res = $om->read(__CLASS__, $oids, ['firstname', 'lastname']);
        foreach($res as $oid => $odata) {
            $result[$oid] = "{$odata['firstname']} {$odata['lastname']}";
        }
        return $result;              
    }
}