<?php
namespace symbiose\sale\product;
use qinoa\orm\Model;

class PackLine extends Model {
    public static function getColumns() {
        /**
         * A Pack Line corresponds to the relation between a 'pack' product (bundle) and another product that it includes.
         * It is equivalent of M2M table between Product and itself.
         */
        return [
            'parent_product_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'symbiose\sale\product\Product',
                'description'       => "The Product this Attribute belongs to.",
                'required'          => true
            ],
            'child_product_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'symbiose\sale\product\Product',
                'description'       => "The Product this Attribute belongs to.",
                'required'          => true
            ],
            'has_own_qty' => [
                'type'              => 'boolean',
                'description'       => "Does product have its own quantity (whatever the quantityt applied to the parent product)?"
            ],
            'own_qty' => [
                'type'              => 'integer',
                'description'       => "Self assigned quantity for this product.",
                'visible'           => ['has_own_qty', '=', true]
            ],

        ];
    }
}