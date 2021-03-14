<?php
namespace symbiose\realestate\rental;
use qinoa\orm\Model;

class Unit extends Model {
    public static function getColumns() {
        /**
         * A Product Family is a group of goods produced under the same brand.
         * Families support hierarchy.
         */

        return [
            'name' => [
                'type'              => 'string',
                'description'       => "Name of the rental unit.",
                'required'          => true
            ],
            'children_ids' => [ 
                'type'              => 'one2many', 
                'description'       => "The list of rental units the current unit can be divided into, if any (i.e. a dorm might be rent as individual beds).",
                'foreign_object'    => 'symbiose\realestate\rental\Unit', 
                'foreign_field'     => 'parent_id'
            ],
            'parent_id' => [
                'type'              => 'many2one',
                'description'       => "Rental Unit which current unit belongs to, if any.",
                'foreign_object'    => 'symbiose\realestate\rental\Unit'
            ]

        ];
    }
}