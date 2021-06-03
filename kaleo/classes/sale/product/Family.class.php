<?php
namespace lodging\sale\product;


class Family extends \symbiose\sale\product\Family {

    public static function getColumns() {
        /**
         * A Product Family is a group of goods produced under the same brand.
         * Families support hierarchy.
         */

        return [
            'centers_ids' => [ 
                'type'              => 'many2many', 
                'foreign_object'    => 'lodging\identity\Center', 
                'foreign_field'     => 'product_families_ids', 
                'rel_table'         => 'symbiose_sale_product_family_rel_identity_center', 
                'rel_foreign_key'   => 'center_id',
                'rel_local_key'     => 'family_id'
            ]

        ];
    }
}