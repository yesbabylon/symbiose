<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace sale\product;
use equal\orm\Model;

class Group extends Model {
    public static function getColumns() {
        /**
         *
         */

        return [
            'name' => [
                'type'              => 'string',
                'description'       => "Name of the product model group (used for all variants).",
                'required'          => true
            ],
            'product_model_ids' => [ 
                'type'              => 'many2many', 
                'foreign_object'    => 'sale\product\ProductModel', 
                'foreign_field'     => 'group_ids', 
                'rel_table'         => 'sale_product_rel_productmodel_group', 
                'rel_foreign_key'   => 'productmodel_id',
                'rel_local_key'     => 'group_id'
            ]
        ];
    }
}