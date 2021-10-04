<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace sale\discount;
use equal\orm\Model;

class DiscountListCategory extends Model {

    public static function getName() {
        return "Discount list category";
    }

    public static function getDescription() {
        return "Discount lists can be arranged by categories.";
    }
    

    public static function getColumns() {
        /**
         */

        return [
            'name' => [
                'type'              => 'string',
                'description'       => 'Short name of the category.',
                'required'          => true
            ],
            
            'description' => [
                'type'              => 'string',
                'description'       => "Criterias that need to be addressed by children lists.",
                'multilang'         => true
            ],

            'discount_lists_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'sale\discount\DiscountList',
                'foreign_field'     => 'discount_list_category_id',
                'description'       => 'The discount lists that are assigned to the category.'
            ]

        ];
    }

}