<?php
namespace symbiose\sale\product;
use qinoa\orm\Model;

class Product extends Model {
    public static function getColumns() {
        /**
         * A Product is a variant of a Product Model. And there is always at least one Product for a given Product Model.
         * Within the organisation, a product is always referenced by a SKU code (assigned to each variant of a Product Model).
         * A SKU code identifies a product with all its specific characteristics.
         */

        return [
            'name' => [
                'type'              => 'alias',
                'alias'             => 'sku'
            ],
            'sku' => [
                'type'              => 'string',
                'description'       => "SKU code (Stock Keeping Unit) used as internal reference."
            ],
            'description' => [
                'type'              => 'string',
                'description'       => "Short description of the variant."
            ],
            'product_model_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'symbiose\sale\product\ProductModel',
                'description'       => "Product Model of this variant.",
                'required'          => true
            ]
            // if the organisation uses price-lists, the price is to be found in the Price object related to the product SKU
        ];
    }

    public static function getUnique() {
        return [
            ['sku']
        ];
    }   
}