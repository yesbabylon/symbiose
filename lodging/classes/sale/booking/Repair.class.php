<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace lodging\sale\booking;

class Repair extends Consumption {

    public static function getName() {
        return 'Repair';
    }

    public static function getDescription() {
        return "A repair is an event that relates to a scheduled repairing. Repairs and Consumptions are handled the same way in the Planning.";
    }

    public static function getColumns() {
        return [

            'repairing_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'lodging\sale\booking\Repairing',
                'description'       => 'The booking the comsumption relates to.',
                'ondelete'          => 'cascade'        // delete repair when parent repairing is deleted
            ],

            'type' => [
                'type'              => 'string',
                'description'       => 'The reason the unit is reserved.',
                'selection'         => [
                    'ooo'                              // out-of-order (repair & maintenance)
                ],
                'readonly'          => true,               
                'default'           => 'ooo'
            ],

            'is_rental_unit' => [
                'type'              => 'boolean',
                'description'       => 'Does the consumption relate to a rental unit?',
                'default'           => true
            ],

            'rental_unit_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'lodging\realestate\RentalUnit',
                'description'       => "The rental unit the consumption is assigned to."
            ],

            'qty' => [
                'type'              => 'integer',
                'description'       => "How many times the consumption is booked for.",
                'default'           => 1,
                'readonly'          => true                
            ]

        ];
    }

}