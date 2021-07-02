<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace lodging\sale\catalog;


class Family extends \sale\catalog\Family {

    public static function getName() {
        return "Product Family";
    }

    public static function getDescription() {
        return "A Product Family is a group of goods produced under the same brand. Families support hierarchy.";
    }    

    public static function getColumns() {
        return [
            'centers_ids' => [ 
                'type'              => 'many2many', 
                'foreign_object'    => 'lodging\identity\Center', 
                'foreign_field'     => 'product_families_ids', 
                'rel_table'         => 'sale_product_family_rel_identity_center', 
                'rel_foreign_key'   => 'center_id',
                'rel_local_key'     => 'family_id'
            ]

        ];
    }
}