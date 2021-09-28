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

            'family_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\catalog\Family',
                'description'       => "Product Family which current product belongs to.",
                'default'           => 'sale\catalog\Product::defaultFamilyId'
            ],

            'is_pack' => [                
                'type'              => 'boolean',
                'description'       => 'Is the product a pack? (from model).',
                'default'           => 'sale\catalog\Product::defaultIsPack'
            ],

            'is_locked' => [
                'type'              => 'boolean',
                'description'       => 'Is the pack static? (cannot be modified).',
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

            'stat_section_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'finance\stats\StatSection',
                'description'       => 'Statistics section (overloads the model one, if any).'
            ]

        ];
    }

    public static function defaultFamilyId($om, $values=[]) {
        if(isset($values['product_model_id'])) {
            $models = $om->read('sale\catalog\ProductModel', $values['product_model_id'], ['family_id']);
            if($models > 0 && count($models)) {
                return $models[$values['product_model_id']]['family_id'];
            }            
        }
        return null;
    }

    public static function defaultIsPack($om, $values=[]) {
        if(isset($values['product_model_id'])) {
            $models = $om->read('sale\catalog\ProductModel', $values['product_model_id'], ['is_pack']);
            if($models > 0 && count($models)) {
                return $models[$values['product_model_id']]['is_pack'];
            }            
        }
        return null;
    }    

}