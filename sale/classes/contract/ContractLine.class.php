<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace sale\contract;
use equal\orm\Model;

class ContractLine extends Model {

    public static function getName() {
        return "Contract line";
    }  

    public static function getColumns() {

        return [
            'name' => [
                'type'              => 'string',
                'description'       => 'Short name for the contract.'
            ],

            'contract_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\contract\Contract',
                'description'       => 'The contract the line relates to.',
            ],

            'product_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\catalog\Product',
                'description'       => 'The product (SKU) the line relates to.',
                'required'          => true
            ],

            'price' => [ 
                'type'              => 'float', 
                'description'       => 'Price of the product related to the line.'
            ],

            'vat_rate' => [ 
                'type'              => 'float', 
                'description'       => 'VAT rate to be applied.'
            ],
            
            'qty' => [ 
                'type'              => 'float', 
                'description'       => 'Quantity of product.'
            ],

            'free_qty' => [ 
                'type'              => 'integer', 
                'description'       => 'Free quantity.'
            ],

            'discount' => [ 
                'type'              => 'float', 
                'description'       => 'Total amount of discount to apply, if any.',
                'default'           => 0.0
            ]


        ];
    }

}