<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace sale\price;
use equal\orm\Model;

class Price extends Model {
    public static function getColumns() {
        /**
         */

        return [
            'price' => [
                'type'              => 'float',
                'usage'             => 'amount/money',
                'required'          => true
            ],

            'type' => [
                'type'              => 'string',
                'selection'         => ['simple', 'computed']
            ],

            'description' => [
                'type'              => 'string',
                'description'       => "Short description of the variant."
            ],

            'price_list_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\price\PriceList',
                'description'       => "The Price List the price belongs to.",
                'required'          => true
            ],

            'accounting_rule_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'finance\accounting\AccountingRule'
            ],

            'product_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\catalog\Product',
                'description'       => "The Product the price applies to.",
                'required'          => true
            ],
            
        ];
    }

}