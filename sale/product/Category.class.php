<?php
namespace symbiose\sale\product;
use equal\orm\Model;

class Category extends Model {
    public static function getColumns() {
        /**
         * Categories are not related to Families and allow a different way of grouping Products.
         */
        return [
            'name' => [
                'type'              => 'string',
                'description'       => "Name of the product category (used for all variants).",
                'required'          => true
            ],
            'product_model_ids' => [ 
                'type'              => 'many2many', 
                'foreign_object'    => 'symbiose\sale\product\ProductModel', 
                'foreign_field'     => 'category_ids', 
                'rel_table'         => 'symbiose_sale_prooduct_rel_productmodel_category', 
                'rel_foreign_key'   => 'productmodel_id',
                'rel_local_key'     => 'category_id'
            ]
        ];
    }
}