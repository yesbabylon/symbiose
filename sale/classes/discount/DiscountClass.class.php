<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace sale\discount;
use equal\orm\Model;

class DiscountClass extends Model {

    public static function getColumns() {

        return [
            'name' => [
                'type'              => 'string',
                'required'          => true
            ],

            'description' => [
                'type'              => 'string',
                'description'       => "Context the discount is meant to be used."
            ],

            'rate_min' => [
                'type'              => 'datetime',
                'description'       => "Guaranteed minimal discount, if any.",
                'default'           => 0.0
            ],

            'rate_max' => [
                'type'              => 'datetime',
                'description'       => "Maximal applicable discount, if any.",
                'default'           => 1.0
            ],

            'discount_lists_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'sale\discount\DiscountList',
                'foreign_field'     => 'discount_class_id',
                'description'       => 'The discount list the discount belongs to.'
            ],

            'rate_class_id' => [
                'type'              => 'many2one',                
                'foreign_object'    => 'sale\customer\RateClass',
                'description'       => "The rate class that applies to this class of discount.",
                'required'          => true
            ],
             

        ];
    }

}