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
                'onchange'          => 'sale\catalog\Product::onchangeProductModelId'
            ],

            'pack_lines_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'lodging\sale\catalog\PackLine',
                'foreign_field'     => 'parent_product_id',
                'description'       => "Products that are bundled in the pack.",
            ],

            'label' => [
                'type'              => 'string',
                'description'       => 'Human readable mnemo for identifying the product. Allows duplicates.',
                'required'          => true,
                'onchange'          => 'lodging\sale\catalog\Product::onchangeLabel'
            ],

            'sku' => [
                'type'              => 'string',
                'description'       => "Stock Keeping Unit code for internal reference. Must be unique.",
                'required'          => true,
                'unique'            => true,
                'onchange'          => 'lodging\sale\catalog\Product::onchangeSku'
            ]

        ];
    }

    public static function onchangeLabel($om, $oids, $lang) {
        $om->write(get_called_class(), $oids, ['name' => null], $lang);
    }

    public static function onchangeSku($om, $oids, $lang) {
        $om->write(get_called_class(), $oids, ['name' => null], $lang);
    }
}