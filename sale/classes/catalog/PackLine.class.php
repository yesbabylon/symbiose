<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace sale\catalog;
use equal\orm\Model;

class PackLine extends Model {
    public static function getColumns() {
        /**
         * A Pack Line corresponds to the relation between a 'pack' product (bundle) and another product that it includes.
         * It is equivalent of M2M table between Product and itself.
         */
        return [
            'parent_product_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\catalog\Product',
                'description'       => "The Product this Attribute belongs to.",
                'required'          => true
            ],
            'child_product_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\catalog\Product',
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