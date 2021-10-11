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
            ]

        ];
    }

}