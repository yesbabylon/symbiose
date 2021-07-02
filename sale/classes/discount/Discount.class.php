<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace sale\discount;
use equal\orm\Model;

class Discount extends Model {

    public static function getColumns() {

        return [
            'name' => [
                'type'              => 'string',
                'required'          => true
            ],

            'rate_max' => [
                'type'              => 'float',
                'description'       => "Maximum possible value of the discount."
            ],

            'rate_min' => [
                'type'              => 'float',
                'description'       => "Guaranteed minimal discount, if any.",
                'default'           => 0.0
            ],

            'discount_list_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\discount\DiscountList',
                'description'       => 'The discount list the discount belongs to.',
                'required'          => true
            ],

        ];
    }

}