<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace sale\discount;
use equal\orm\Model;

class DiscountList extends Model {

    public static function getColumns() {

        return [
            'name' => [
                'type'              => 'string',
                'required'          => true
            ],

            'valid_from' => [
                'type'              => 'date',
                'description'       => "Date from which the list is valid."
            ],

            'valid_until' => [
                'type'              => 'date',
                'description'       => "Moment until when the list is valid."
            ],

            'discounts_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'sale\discount\Discount',
                'foreign_field'     => 'discount_list_id',
                'description'       => 'The discounts that are part of the list.'
            ],

            'discount_list_category_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\discount\Category',
                'description'       => 'The category the discount list belongs to.',
                'required'          => true
            ],

        ];
    }

}