<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2024
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace sale\catalog;

use equal\orm\Model;

class ProductAttribute extends Model {

    public static function getName() {
        return "Attribute";
    }

    public static function getDescription() {
        return "A Product Attribute corresponds to the value of an attribute available for a Product of a given Family."
            ." It is equivalent to the M2M table between Product and Option (the possible values for the attributes are limited by OptionValue).";
    }

    public static function getColumns() {
        return [

            'product_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\catalog\Product',
                'description'       => "The Product this Attribute belongs to.",
                'required'          => true
            ],

            'product_model_id' => [
                'type'              => 'computed',
                'result_type'       => 'many2one',
                'foreign_object'    => 'sale\catalog\ProductModel',
                'description'       => "The Product Model this Attribute belongs to.",
                'store'             => true,
                'function'          => 'calcProductModelId',
                'readonly'          => true
            ],

            'option_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\catalog\Option',
                'description'       => "Product Option this attribute relates to.",
                'required'          => true,
                'domain'            => ['product_model_id', '=', 'object.product_model_id']
            ],

            'option_value_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\catalog\OptionValue',
                'description'       => "Value of this attribute for the selected option.",
                'required'          => true,
                'domain'            => ['option_id', '=', 'object.option_id']
            ]
        ];
    }

    public static function onchange($event, $values) {
        $result = [];

        if(isset($event['option_id'])) {
            $result['option_value_id'] = null;
        }

        return $result;
    }

    public static function calcProductModelId($self): array {
        $result = [];
        $self->read(['product_id' => ['product_model_id']]);
        foreach($self as $id => $product_attribute) {
            $result[$id] = $product_attribute['product_id']['product_model_id'];
        }

        return $result;
    }

    public function getUnique() {
        return [
            ['product_id', 'option_id']
        ];
    }
}
