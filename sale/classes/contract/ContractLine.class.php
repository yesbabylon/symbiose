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
                'type'              => 'computed',
                'function'          => 'sale\contract\ContractLine::getDisplayName',
                'result_type'       => 'string',
                'store'             => true,
                'description'       => 'The display name of the line.'
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

            'unit_price' => [ 
                'type'              => 'float', 
                'description'       => 'Price of the product related to the line.',
                'required'          => true
            ],

            'vat_rate' => [ 
                'type'              => 'float', 
                'description'       => 'VAT rate to be applied.',
                'required'          => true
            ],
            
            'qty' => [ 
                'type'              => 'float', 
                'description'       => 'Quantity of product.',
                'required'          => true
            ],

            'free_qty' => [ 
                'type'              => 'integer', 
                'description'       => 'Free quantity.',
                'default'           => 0
            ],

            'discount' => [ 
                'type'              => 'float', 
                'description'       => 'Total amount of discount to apply, if any.',
                'default'           => 0.0
            ],

            'price' => [
                'type'              => 'computed',
                'result_type'       => 'float',
                'usage'             => 'amount/money:4',                
                'description'       => 'Final (computed) price VAT incl.',
                'function'          => 'sale\contract\ContractLine::getPrice',
                'store'             => true
            ]


        ];
    }

    public static function getDisplayName($om, $oids, $lang) {
        $result = [];
        $res = $om->read(get_called_class(), $oids, ['product_id.label']);
        foreach($res as $oid => $odata) {
            $result[$oid] = "{$odata['product_id.label']}";
        }
        return $result;
    }



    /**
     * Compute the VAT incl. total price of the line, with discounts applied.
     *
     */
    public static function getPrice($om, $oids, $lang) {
        $result = [];
        $lines = $om->read(__CLASS__, $oids, ['unit_price', 'vat_rate', 'qty', 'free_qty', 'discount']);

        if($lines > 0 && count($lines)) {
            foreach($lines as $lid => $line) {
                $price = $line['unit_price'] * (1-$line['discount']);
                $result[$lid] = round($price * ($line['qty'] - $line['free_qty']) * (1 + $line['vat_rate']), 2);
            }
        }
        return $result;
    }    

}