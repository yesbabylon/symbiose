<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace sale\catalog;
use equal\orm\Model;

class ProductAttribute extends Model {
    public static function getColumns() {
        /**
         * A Product Attrribute corresponds to the value of an attribute available for a Product of a given Family.
         * It is equivalent to the M2M table between Product and Option (the possible values for the attribtues are limited by OptionValue).
         */

        return [
            'product_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\catalog\Product',
                'description'       => "The Product this Attribute belongs to.",
                'required'          => true
            ],
            'option_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\catalog\Option',
                'description'       => "Product Option this attribute relates to.",
                'required'          => true
            ],
            'option_value_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\catalog\OptionValue',
                'description'       => "Value of this attribute for the selected option.",
                'required'          => true
            ]
        ];
    }
}