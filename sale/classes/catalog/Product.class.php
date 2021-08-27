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
         * A Product is a variant of a Product Model. There is always at least one Product for a given Product Model.
         * Within the organisation, a product is always referenced by a SKU code (assigned to each variant of a Product Model).
         * A SKU code identifies a product with all its specific characteristics.
         */

        return [
            'name' => [
                'type'              => 'string',
                'description'       => 'Human readable mnemo for identifying the product. Allows duplicates.',
                'required'          => true
            ],

            'sku' => [
                'type'              => 'string',
                'description'       => "Stock Keeping Unit code for internal reference. Must be unique.",
                'unique'            => true
            ],

            'ean' => [
                'type'              => 'string',
                'usage'             => 'uri/urn:ean',
                'description'       => "IAN/EAN code for barcode generation."
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

            'is_pack' => [
                'type'              => 'computed',
                'result_type'       => 'boolean',
                'description'       => 'Is the product a pack? (from product model)',
                'function'          => 'sale\catalog\Product::getIsPack',
                'store'             => true
            ],

            'is_locked' => [
                'type'              => 'boolean',
                'description'       => 'Is the pack static? (cannot be modified)',
                'default'           => false,
                'visible'           => [ ['is_pack', '=', true] ]                
            ],

            // if the organisation uses price-lists, the price to use depends on the applicable

            'prices_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'sale\price\Price',
                'foreign_field'     => 'product_id',
                'description'       => "Prices that are related to this product.",
            ],

        ];
    }


    public static function getIsPack($om, $oids, $lang) {
        $result = [];
        $lines = $om->read(__CLASS__, $oids, ['product_model_id.is_pack']);
        foreach($lines as $oid => $odata) {
            $result[$oid] = $odata['product_model_id.is_pack'];
        }
        return $result;
    }

}