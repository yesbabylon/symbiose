<?php
namespace symbiose\sale\product;
use equal\orm\Model;

class Family extends Model {

	public static function getName() {
        return "Product Family";
    }

    public static function getColumns() {
        /**
         * A Product Family is a group of goods produced under the same brand.
         * Families support hierarchy.
         */

        return [
            'name' => [
                'type'              => 'string',
                'description'       => "Name of the product family. A family is a group of goods produced under the same brand.",
                'required'          => true
            ],
            'children_ids' => [ 
                'type'              => 'one2many', 
                'foreign_object'    => 'symbiose\sale\product\Family', 
                'foreign_field'     => 'parent_id'
            ],
            'parent_id' => [
                'type'              => 'many2one',
                'description'       => "Product Family which current family belongs to, if any.",
                'foreign_object'    => 'symbiose\sale\product\Family'
            ]

        ];
    }
}