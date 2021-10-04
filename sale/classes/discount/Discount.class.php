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

            'description' => [
                'type'              => 'string',
                'description'       => "Context the discount is meant to be used."
            ],

            'value' => [
                'type'              => 'float',
                'usage'             => 'amount/percent',
                'description'       => "Discount value.",
                'default'           => 0.0
            ],

            'discount_list_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\discount\DiscountList',
                'description'       => 'The discount list the discount belongs to.',
                'required'          => true
            ],

            'discount_category_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\discount\DiscountCategory',
                'description'       => 'The discount category the discount belongs to.',
                'required'          => true
            ],

            'type' => [
                'type'              => 'string',
                'selection'         => [ 
                    'amount',           // discount is a fixed value
                    'percent',          // discount is a rate to be applied
                    'freebie'           // discount is a count of free products
                ],
                'description'       => 'The kind of contact, based on its responsibilities.'
            ],            

            'conditions_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'sale\discount\Condition',
                'foreign_field'     => 'discount_id',
                'description'       => 'The conditions that apply to the discount.'
            ],            
        ];
    }

}