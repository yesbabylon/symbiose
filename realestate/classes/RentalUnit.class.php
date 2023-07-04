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
        return "A rental unit is a resource that can be rented to a customer.";
    }

    public static function getColumns() {
        return [
            'name' => [
                'type'              => 'string',
                'description'       => "Name of the rental unit.",
                'required'          => true
            ],

            'order' => [
                'type'              => 'integer',
                'description'       => 'Arbitrary value for ordering the rental units.',
                'default'           => 1
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
                'description'       => 'Type of rental unit (that usually comes with extra accommodations, ie meals; or rented as is).',
                'default'           => 'hostel'
            ],

            'is_accomodation' => [
                'type'              => 'boolean',
                'description'       => 'The rental unit is an accommodation (having at least one bed).',
                'default'           => true
            ],

            'capacity' => [
                'type'              => 'integer',
                'description'       => 'The number of persons that may stay in the unit.',
                'default'           => 1
            ],

            'has_children' => [
                'type'              => 'boolean',
                'description'       => 'Flag to mark the unit as having sub-units.',
                'default'           => false
            ],

            'has_parent' => [
                'type'              => 'computed',
                'result_type'       => 'boolean',
                'function'          => 'calcHasParent',
                'description'       => 'Flag to mark the unit as having sub-units.',
                'store'             => true
            ],

            'can_rent' => [
                'type'              => 'boolean',
                'description'       => 'Flag to mark the unit as (temporarily) unavailable for renting.',
                'default'           => true
            ],

            'can_partial_rent' => [
                'type'              => 'boolean',
                'description'       => 'Flag to mark the unit as rentable partially (when children units).',
                'visible'           => [ 'has_children', '=', true ],
                'default'           => false
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
                'foreign_object'    => 'realestate\RentalUnit',
                'onupdate'          => 'onupdateParentId'
            ],

            'repairs_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'sale\booking\Repair',
                'foreign_field'     => 'rental_unit_id',
                'description'       => "The repairs the rental unit is assigned to."
            ],

            // Status relates to current status (NOW) of a rental unit. For availability, refer to related Consumptions
            'status' => [
                'type'              => 'string',
                'selection'         => [
                    'ready',               // unit is available for customers
                    'empty',               // unit is no longer occupied but might require action(s)
                    'busy_full',           // unit is fully occupied
                    'busy_part',           // unit is partially occupied
                    'ooo'                  // unit is out-of-order
                ],
                'description'       => 'Status of the rental unit.',
                'default'           => 'ready',
                // cannot be set manually
                'readonly'          => true
            ],

            'action_required' => [
                'type'              => 'string',
                'selection'         => [
                    'none',                 // unit does not require any action
                    'cleanup_daily',        // unit requires a daily cleanup
                    'cleanup_full',         // unit requires a full cleanup
                    'repair'                // unit requires repair or maintenance
                ],
                'description'       => 'Action required for the rental unit.',
                'default'           => 'none'
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
            ],

            'repairings_ids' => [
                'type'              => 'many2many',
                'foreign_object'    => 'sale\booking\Repairing',
                'foreign_field'     => 'rental_units_ids',
                'rel_table'         => 'sale_rel_repairing_rentalunit',
                'rel_foreign_key'   => 'repairing_id',
                'rel_local_key'     => 'rental_unit_id',
                'description'       => 'List of scheduled repairing assigned to the rental units.'
            ]

        ];
    }

    public static function onupdateParentId($om, $ids, $values, $lang) {
        $om->update(self::getType(), $ids, ['has_parent' => null]);
    }

    public static function calcHasParent($om, $oids, $lang) {
        $result = [];
        $units = $om->read(__CLASS__, $oids, ['parent_id'], $lang);
        foreach($units as $uid => $unit) {
            $result[$uid] = (bool) (!is_null($unit['parent_id']) && $unit['parent_id'] > 0);
        }
        return $result;
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