<?php
namespace symbiose\sale\product;
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
                'foreign_object'    => 'symbiose\sale\product\ProductModel', 
                'foreign_field'     => 'group_ids', 
                'rel_table'         => 'symbiose_sale_product_rel_productmodel_group', 
                'rel_foreign_key'   => 'productmodel_id',
                'rel_local_key'     => 'group_id'
            ]
        ];
    }
}