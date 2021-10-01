<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace lodging\sale\catalog;

class Group extends \sale\catalog\Group {
    public static function getColumns() {

        return [

            'center_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'lodging\identity\Center',
                'description'       => "Center targeted by the group.",
                'required'          => true
            ],
            
            'product_models_ids' => [ 
                'type'              => 'many2many', 
                'foreign_object'    => 'lodging\sale\catalog\ProductModel', 
                'foreign_field'     => 'groups_ids', 
                'rel_table'         => 'lodging_catalog_product_rel_productmodel_group', 
                'rel_foreign_key'   => 'product_model_id',
                'rel_local_key'     => 'group_id'
            ]

        ];
    }
}