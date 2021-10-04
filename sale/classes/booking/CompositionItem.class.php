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
                'result_type'       => 'string',
                'function'          => 'sale\booking\CompositionItem::getDisplayName',
                'store'             => true,
                'description'       => 'The display name of the person (concatenation of first and last names).'
            ],

            'firstname' => [
                'type'              => 'string',
                'description'       => "Firstname of the contact."
            ],

            'lastname' => [
                'type'              => 'string',
                'description'       => 'Lastname of the contact.'
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

            'place_of_birth' => [
                'type'          => 'string',
                'description'   => 'Place of birth of the person (city, country).'
            ],

            /* some legal constraints might apply, in which case we need extra contact details */
            'email' => [
                'type'              => 'string',
                'usage'             => 'email',
                'description'       => "Email address of the contact."
            ],

            'phone' => [
                'type'              => 'string',
                'usage'             => 'phone',
                'description'       => "Phone number of the contact."
            ],

            'address' => [
                'type'              => 'string',
                'description'       => 'Full postal address (street, number, zip, city, country).'
            ],

            'country' => [
                'type'              => 'string',
                'usage'             => 'country/iso-3166:2',
                'description'       => "Nationality of the contact.",
                'default'           => 'BE'
            ],

            'rental_unit_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'realestate\RentalUnit',
                'description'       => "The rental unit the person is assigned to.",
                'required'          => true,
                'domain'            => ['id', 'in', 'object.rental_units_ids']
            ],

            'composition_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\booking\Composition',
                'description'       => "The composition the item refers to.",
                'ondelete'          => 'cascade',        // delete item when parent composition is deleted
                'required'          => true
            ],


            // for filtering rental_unit_id field in forms
            'rental_units_ids' => [
                'type'              => 'computed',
                'function'          => 'sale\booking\CompositionItem::getRentalUnitsIds',
                'result_type'       => 'one2many',
                'foreign_object'    => 'realestate\RentalUnit',
                'description'       => "The rental units attached to the current booking."
            ]


        ];
    }

    public static function getRentalUnitsIds($om, $oids, $lang) {
        $result = [];
        $items = $om->read(__CLASS__, $oids, ['composition_id.booking_id.booking_lines_ids']);

        foreach($items as $oid => $odata) {
            $rental_units_ids = [];
            $booking_lines_ids = $odata['composition_id.booking_id.booking_lines_ids'];
            $lines = $om->read('lodging\sale\booking\BookingLine', $booking_lines_ids, ['qty_accounting_method', 'rental_unit_id']);
            foreach($lines as $lid => $line) {
                if($line['qty_accounting_method'] == 'accomodation') {
                    $rental_units_ids[$line['rental_unit_id']] = true;
                }
            }
            $result[$oid] = array_keys($rental_units_ids);
        }
        return $result;
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