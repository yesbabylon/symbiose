<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace lodging\sale\catalog;


class Product extends \sale\catalog\Product {
    public static function getColumns() {

        return [
            'code_legacy' => [
                'type'              => 'string',
                'description'       => "Old code of the product."
            ],

            'product_model_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'lodging\sale\catalog\ProductModel',
                'description'       => "Product Model of this variant.",
                'required'          => true,
                'onupdate'          => 'sale\catalog\Product::onupdateProductModelId'
            ],

            'pack_lines_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'lodging\sale\catalog\PackLine',
                'foreign_field'     => 'parent_product_id',
                'description'       => "Products that are bundled in the pack.",
                'ondetach'          => 'delete'
            ],

            // #todo - deprecate
            'ref_pack_lines_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'lodging\sale\catalog\PackLine',
                'foreign_field'     => 'child_product_id',
                'description'       => "Pack lines that relate to the product."
            ],

            'label' => [
                'type'              => 'string',
                'description'       => 'Human readable mnemo for identifying the product. Allows duplicates.',
                'required'          => true,
                'onupdate'          => 'onupdateLabel'
            ],

            'sku' => [
                'type'              => 'string',
                'description'       => "Stock Keeping Unit code for internal reference. Must be unique.",
                'required'          => true,
                'unique'            => true,
                'onupdate'          => 'onupdateSku'
            ]

        ];
    }

    public static function onupdateLabel($om, $oids, $values, $lang) {
        $om->write(__CLASS__, $oids, ['name' => null], $lang);
    }

    public static function onupdateSku($om, $oids, $values, $lang) {
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
}