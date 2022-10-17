<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace yesbabylon\service;
use equal\orm\Model;

class ServiceLine extends \sale\contract\ContractLine {

    public static function getName() {
        return "Service line";
    }

    public static function getColumns() {

        return [


            'service_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'yesbabylon\service\Service',
                'description'       => 'The service the line relates to.',
            ],

            'service_line_group_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'yesbabylon\service\ServiceLineGroup',
                'description'       => 'The service the line relates to.',
            ],

            'vat_rate' => [
                'type'              => 'float',
                'description'       => 'VAT rate to be applied.',
                'required'          => false,
                'default'           => 0.21
            ],

            'unit_price' => [
                'type'              => 'float',
                'usage'             => 'amount/money:4',
                'description'       => 'Tax-excluded price of the product related to the line.',
                'required'          => false,
                'default'           => 1
            ],

            'qty' => [
                'type'              => 'float',
                'description'       => 'Quantity of product.',
                'required'          => true,
                'default'           => 1
            ],

            'total' => [
                'type'              => 'computed',
                'result_type'       => 'float',
                'description'       => 'Total tax-excluded price for all lines (computed).',
                'function'          => 'calcTotal',
                'store'             => true
            ],

            'price' => [
                'type'              => 'computed',
                'result_type'       => 'float',
                'description'       => 'Final tax-included price for all lines (computed).',
                'function'          => 'calcPrice',
                'store'             => true
            ],

            'product_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\catalog\Product',
                'description'       => 'The product (SKU) the line relates to.',
                'required'          => false
            ]


        ];
    }

    public static function calcTotal($om, $oids, $lang) {
        $result = [];

        $lines = $om->read(get_called_class(), $oids, ['qty','unit_price']);

        foreach($lines as $oid => $line) {
            $result[$oid] = $line['unit_price'] * $line['qty'];
        }
        return $result;
    }

    /**
     * Get final tax-included price of the line.
     *
     */
    public static function calcPrice($om, $oids, $lang) {
        $result = [];

        $lines = $om->read(get_called_class(), $oids, ['total','vat_rate']);

        foreach($lines as $oid => $odata) {
            $total = (float) $odata['total'];
            $vat = (float) $odata['vat_rate'];

            $result[$oid] = round($total * (1.0 + $vat), 2);
        }
        return $result;
    }
}



