<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace realestate;
use equal\orm\Model;

class RentalUnit extends Model {

    public static function getDescription() {
        return "A rental unit is a ressource that can be rented to a customer.";
    }

    public static function getColumns() {
        return [
            'name' => [
                'type'              => 'string',
                'description'       => "Name of the rental unit.",
                'required'          => true
            ],

            'code' => [
                'type'              => 'string',
                'description'       => 'Short code for identification.'
            ],

            'description' => [
                'type'              => 'string',
                'description'       => 'Short description of the unit.'
            ],

            'type' => [
                'type'              => 'string',
                'selection'         => [
                    'building', 
                    'bedroom', 
                    'bed', 
                    'meetingroom', 
                    'diningroom', 
                    'room', 
                    'FFE'               // Furniture, Fixtures, and Equipment
                ],
                'description'       => 'Type of rental unit (that relates to capacity).',
                'required'          => true
            ],

            'category' => [
                'type'              => 'string',
                'selection'         => ['hostel', 'lodge'],         // hostel is GA, lodge is GG
                'description'       => 'Type of rental unit (that usually comes with extra accomodations, ie meals; or rented as is).',
                'default'           => 'hostel'
            ],

            'capacity' => [
                'type'              => 'integer',
                'description'       => 'The number of persons that may stay in the unit.',
                'default'           => 1
            ],

            'has_children' => [
                'type'              => 'boolean',
                'description'       => 'Flag to mark the unit as having sub-units.'
            ],

            'can_rent' => [
                'type'              => 'boolean',
                'description'       => 'Flag to mark the unit as (temporarily) unavailable for renting.'
            ],

            'children_ids' => [ 
                'type'              => 'one2many', 
                'description'       => "The list of rental units the current unit can be divided into, if any (i.e. a dorm might be rent as individual beds).",
                'foreign_object'    => 'realestate\RentalUnit', 
                'foreign_field'     => 'parent_id'
            ],
            
            'parent_id' => [
                'type'              => 'many2one',
                'description'       => "Rental Unit which current unit belongs to, if any.",
                'foreign_object'    => 'realestate\RentalUnit'
            ],

            // Status relates to current status (NOW) of a rental unit. For availability, refer to related Consumptions
            'status' => [
                'type'              => 'string',
                'selection'         => [
                    'ready',                // unit is clean and ready for customers
                    'ooo',                  // unit is out-of-order
                    'cleanup_daily',        // unit requires a daily cleanup
                    'cleanup_full'          // unit requires a full cleanup
                ],       
                'description'       => 'Status of the rental unit.',
                'default'           => 'clean'
            ],

            'consumptions_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'sale\booking\Consumption',
                'foreign_field'     => 'rental_unit_id',
                'description'       => "The consumptions that relate to the rental unit."
            ],

            'composition_items_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'sale\booking\CompositionItem',
                'foreign_field'     => 'rental_unit_id',
                'description'       => "The composition items that relate to the rental unit."
            ],

            'rental_unit_category_id' => [
                'type'              => 'many2one',
                'description'       => "Category which current unit belongs to, if any.",
                'foreign_object'    => 'realestate\RentalUnitCategory'
            ]

        ];
    }

    public static function getConstraints() {
        return [
            'capacity' =>  [
                'lte_zero' => [
                    'message'       => 'Capacity must be a positive value.',
                    'function'      => function ($qty, $values) {
                        return ($qty > 0);
                    }
                ]
            ]

        ];
    }    
}