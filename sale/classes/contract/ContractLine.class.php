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
                'function'          => 'calcName',
                'result_type'       => 'string',
                'store'             => true,
                'description'       => 'The display name of the line.'
            ],

            'description' => [
                'type'              => 'string',
                'description'       => 'Complementary description of the line. If set, replaces the product name.',
                'default'           => ''
            ],

            'contract_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\contract\Contract',
                'description'       => 'The contract the line relates to.',
            ],

            'contract_line_group_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\contract\ContractLineGroup',
                'description'       => 'The contract the line relates to.',
            ],

            'product_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\catalog\Product',
                'description'       => 'The product (SKU) the line relates to.',
                'required'          => true
            ],

            'price_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\price\Price',
                'description'       => 'The price the line relates to, if any.'
            ],

            'unit_price' => [
                'type'              => 'float',
                'usage'             => 'amount/money:4',
                'description'       => 'Tax-excluded price of the product related to the line.',
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

            // #memo - important: to allow the maximum flexibility, percent values can hold 4 decimal digits (must not be rounded, except for display)
            'discount' => [
                'type'              => 'float',
                'usage'             => 'amount/rate',
                'description'       => 'Total amount of discount to apply, if any.',
                'default'           => 0.0
            ],

            'total' => [
                'type'              => 'computed',
                'result_type'       => 'float',
                'usage'             => 'amount/money:4',
                'description'       => 'Total tax-excluded price of the line.',
                'function'          => 'calcTotal',
                'store'             => true
            ],

            'price' => [
                'type'              => 'computed',
                'result_type'       => 'float',
                'usage'             => 'amount/money:2',
                'description'       => 'Final tax-included price of the line.',
                'function'          => 'calcPrice',
                'store'             => true
            ]

        ];
    }

    public static function calcName($om, $oids, $lang) {
        $result = [];
        $res = $om->read(get_called_class(), $oids, ['product_id.label']);
        foreach($res as $oid => $odata) {
            $result[$oid] = "{$odata['product_id.label']}";
        }
        return $result;
    }

    /**
     * Compute the VAT excl. total price of the line, with discounts applied.
     *
     */
    public static function calcTotal($om, $oids, $lang) {
        $result = [];
        $lines = $om->read(__CLASS__, $oids, ['unit_price', 'qty', 'free_qty', 'discount']);

        if($lines > 0 && count($lines)) {
            foreach($lines as $lid => $line) {
                $result[$lid] = $line['unit_price'] * (1 - $line['discount']) * ($line['qty'] - $line['free_qty']);
            }
        }
        return $result;
    }

    /**
     * Compute the final VAT incl. price of the line.
     *
     */
    public static function calcPrice($om, $oids, $lang) {
        $result = [];
        $lines = $om->read(__CLASS__, $oids, ['total', 'vat_rate']);

        if($lines > 0 && count($lines)) {
            foreach($lines as $lid => $line) {
                $result[$lid] = round($line['total'] * (1 + $line['vat_rate']), 2);
            }
        }
        return $result;
    }

}