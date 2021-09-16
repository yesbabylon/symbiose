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

            'center_id' => [
                'type'              => 'many2one',
                'description'       => "Center which current unit belongs to, if any.",
                'foreign_object'    => 'lodging\identity\Center'
            ],

            'type' => [
                'type'              => 'string',
                'selection'         => ['building', 'bedroom', 'bed', 'meetingroom', 'room'],
                'description'       => 'Type of rental unit (that relates to capacity).',
                'required'          => true
            ],

            'category' => [
                'type'              => 'string',
                'selection'         => ['hostel', 'lodge'],         // hostel is GA, lodge is GG
                'description'       => 'Type of rental unit (that usually comes with extra accomodations, ie meals; or rented as is).',
                'required'          => true
            ],

            'capacity' => [
                'type'              => 'integer',
                'description'       => 'The number of persons that may stay in the unit.'
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
            ]

        ];
    }
}