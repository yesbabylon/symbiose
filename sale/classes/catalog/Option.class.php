<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2024
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

namespace sale\catalog;

use equal\orm\Model;

class Option extends Model {

    public static function getDescription() {
        return 'A product option is a characteristic of the product to witch a value can be attributed.';
    }

    public static function getColumns() {
        return [

            'name' => [
                'type'              => 'string',
                'description'       => 'Name of the option.',
                'multilang'         => true,
                'required'          => true
            ],

            'description' => [
                'type'              => 'string',
                'usage'             => 'text/plain',
                'description'       => 'Short description of the option.',
                'multilang'         => true
            ],

            'product_model_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\catalog\ProductModel',
                'description'       => 'Product Model this option belongs to.',
                'required'          => true
            ],

            'option_values_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'sale\catalog\OptionValue',
                'foreign_field'     => 'option_id',
                'description'       => 'Option values that belongs to this option.'
            ]

        ];
    }

    public function getUnique() {
        return [
            ['name', 'product_model_id']
        ];
    }
}
