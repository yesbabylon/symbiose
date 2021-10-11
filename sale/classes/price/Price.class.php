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

            'name' => [
                'type'              => 'computed',
                'function'          => 'sale\price\Price::getDisplayName',
                'result_type'       => 'string',
                'store'             => true,
                'description'       => 'The display name of the price.'
            ],

            'price' => [
                'type'              => 'float',
                'usage'             => 'amount/money',
                'required'          => true,
                'description'       => "Tax excluded price."
            ],

            'type' => [
                'type'              => 'string',
                'selection'         => ['direct', 'computed'],
                'default'           => 'direct'
            ],

            'calculation_method_id' => [
                'type'              => 'string',
                'description'       => "Method to use for price computation.",
                'visible'           => ['type', '=', 'computed']
            ],

            'price_list_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\price\PriceList',
                'description'       => "The Price List the price belongs to.",
                'required'          => true,
                'ondelete'          => 'cascade'
            ],

            'is_active' => [
                'type'              => 'computed',
                'result_type'       => 'boolean',
                'function'          => 'sale\price\Price::getIsActive',
                'store'             => true,
                'description'       => "Is the price currently applicable?"
            ],

            'accounting_rule_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'finance\accounting\AccountingRule',
                'description'       => "Selling accounting rule. If set, overrides the rule of the product this price is assigned to."
            ],

            'product_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\catalog\Product',
                'description'       => "The Product (sku) the price applies to.",
                'required'          => true
            ]

        ];
    }


    public static function getDisplayName($om, $oids, $lang) {
        $result = [];
        $res = $om->read(__CLASS__, $oids, ['product_id.name', 'product_id.sku']);
        foreach($res as $oid => $odata) {
            $result[$oid] = "{$odata['product_id.name']} ({$odata['product_id.sku']})";
        }
        return $result;
    }

    public static function getIsActive($om, $oids, $lang) {
        $result = [];
        $prices = $om->read(__CLASS__, $oids, ['price_list_id.is_active']);

        if($prices > 0 && count($prices)) {
            foreach($prices as $pid => $price) {
                $result[$pid] = $price['price_list_id.is_active'];
            }
        }
        return $result;
    }

    public function getUnique() {
        return [
            ['product_id', 'price_list_id']
        ];
    }       


}