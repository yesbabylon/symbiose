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
                'type'              => 'computed',
                'result_type'       => 'string',
                'function'          => 'sale\catalog\Product::getFullName',
                'store'             => true,
                'description'       => 'The full name of the product (label + sku).'
            ],

            'label' => [
                'type'              => 'string',
                'description'       => 'Human readable mnemo for identifying the product. Allows duplicates.',
                'required'          => true,
                'onchange'          => 'sale\catalog\Product::onchangeLabel'
            ],

            'sku' => [
                'type'              => 'string',
                'description'       => "Stock Keeping Unit code for internal reference. Must be unique.",
                'required'          => true,
                'unique'            => true,
                'onchange'          => 'sale\catalog\Product::onchangeSku'
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
                'required'          => true,
                'onchange'          => 'sale\catalog\Product::onchangeProductModelId'
            ],

            'family_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\catalog\Family',
                'description'       => "Product Family which current product belongs to.",
                'default'           => 'sale\catalog\Product::defaultFamilyId'
            ],

            'is_pack' => [
                'type'              => 'computed',
                'result_type'       => 'boolean',
                'function'          => 'sale\catalog\Product::getIsPack',
                'description'       => 'Is the product a pack? (from model).',
                'store'             => true,                
                'readonly'          => true
            ],

            'has_own_price' => [
                'type'              => 'computed',
                'result_type'       => 'boolean',
                'function'          => 'sale\catalog\Product::getHasOwnPrice',
                'description'       => 'Product is a pack with its own price (from model).',
                'visible'           => ['is_pack', '=', true],
                'store'             => true,
                'readonly'          => true
            ],

            'pack_lines_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'sale\catalog\PackLine',
                'foreign_field'     => 'parent_product_id',
                'description'       => "Products that are bundled in the pack.",
                'ondetach'          => 'delete'
            ],

            'product_attributes_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'sale\catalog\ProductAttribute',
                'foreign_field'     => 'product_id',
                'description'       => "Attributes set for the product.",
                'ondetach'          => 'delete'
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
                'ondetach'          => 'delete'
            ],

            'stat_section_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'finance\stats\StatSection',
                'description'       => 'Statistics section (overloads the model one, if any).'
            ],

            /* can_buy and can_sell are adapted when related values are changed in parent product_model */

            'can_buy' => [
                'type'              => 'boolean',
                'description'       => "Can this product be purchassed?",
                'default'           => false
            ],

            'can_sell' => [
                'type'              => 'boolean',
                'description'       => "Can this product be sold?",
                'default'           => true
            ],

            'groups_ids' => [
                'type'              => 'many2many',
                'foreign_object'    => 'sale\catalog\Group',
                'foreign_field'     => 'products_ids',
                'rel_table'         => 'sale_catalog_product_rel_product_group',
                'rel_foreign_key'   => 'group_id',
                'rel_local_key'     => 'product_id'
            ]

        ];
    }

    /**
     * Computes the display_name of the product as a concatenation of Label and SKU.
     *
     */
    public static function getFullName($om, $oids, $lang) {
        $result = [];
        $res = $om->read(get_called_class(), $oids, ['label', 'sku'], $lang);
        foreach($res as $oid => $odata) {
            if( (isset($odata['label']) && strlen($odata['label']) > 0 ) || (isset($odata['sku']) && strlen($odata['sku']) > 0) ) {
                $result[$oid] = "{$odata['label']} ({$odata['sku']})";
            }
        }
        return $result;
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

    public static function getIsPack($om, $oids, $lang) {
        $result = [];

        $res = $om->read(get_called_class(), $oids, ['product_model_id.is_pack']);

        if($res > 0 && count($res)) {
            foreach($res as $oid => $odata) {
                $result[$oid] = $odata['product_model_id.is_pack'];
            }
        }

        return $result;
    }

    public static function getHasOwnPrice($om, $oids, $lang) {
        $result = [];

        $res = $om->read(get_called_class(), $oids, ['product_model_id.has_own_price']);

        if($res > 0 && count($res)) {
            foreach($res as $oid => $odata) {
                $result[$oid] = (bool) $odata['product_model_id.has_own_price'];
            }
        }

        return $result;
    }

    public static function onchangeLabel($om, $oids, $lang) {
        $om->write(__CLASS__, $oids, ['name' => null], $lang);
    }

    public static function onchangeSku($om, $oids, $lang) {
        $products = $om->read(__CLASS__, $oids, ['prices_ids']);
        if($products > 0 && count($products)) {
            $prices_ids = [];
            foreach($products as $product) {
                $prices_ids = array_merge($prices_ids, $product['prices_ids']);
            }
            $om->write('sale\price\Price', $prices_ids, ['name' => null], $lang);
        }
        $om->write(__CLASS__, $oids, ['name' => null], $lang);
    }

    public static function onchangeProductModelId($om, $oids, $lang) {
        $products = $om->read(get_called_class(), $oids, ['product_model_id.can_sell', 'product_model_id.groups_ids', 'product_model_id.family_id']);
        foreach($products as $pid => $product) {
            $om->write(get_called_class(), $pid, [
                'can_sell'      => $product['product_model_id.can_sell'],
                'groups_ids'    => $product['product_model_id.groups_ids'],
                'family_id'     => $product['product_model_id.family_id']
            ]);
        }
    }


}