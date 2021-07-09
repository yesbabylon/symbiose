<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace sale\catalog;
use equal\orm\Model;

class Product extends Model {
    public static function getColumns() {
        /**
         * A Product is a variant of a Product Model. And there is always at least one Product for a given Product Model.
         * Within the organisation, a product is always referenced by a SKU code (assigned to each variant of a Product Model).
         * A SKU code identifies a product with all its specific characteristics.
         */

        return [
            'name' => [
                'type'              => 'string',
                'description'       => 'Human readable memo for identifying the product. Allows duplicates.',
                'required'          => true
            ],

            'sku' => [
                'type'              => 'string',
                'description'       => "SKU code (Stock Keeping Unit) used as internal reference. Must be unique.",
                'unique'            => true
            ],

            'description' => [
                'type'              => 'text',
                'description'       => "Description of the variant (specifics)."
            ],

            'product_model_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\catalog\ProductModel',
                'description'       => "Product Model of this variant.",
                'required'          => true
            ],

            // if the organisation uses price-lists, the price is to be found in the Price object related to the product SKU

            'prices_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'sale\price\Price',
                'foreign_field'     => 'product_id',
                'description'       => "Prices that are related to this product.",
            ],

        ];
    }

}