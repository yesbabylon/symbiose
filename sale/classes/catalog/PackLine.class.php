<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace sale\catalog;
use equal\orm\Model;

class PackLine extends Model {

    public static function getColumns() {
        /**
         * A Pack Line corresponds to the relation between a 'pack' product (bundle) and another product that it includes.
         * It is equivalent of M2M table between Product and itself.
         */
        return [
            'name' => [
                'type'              => 'computed',
                'function'          => 'sale\catalog\PackLine::getDisplayName',
                'result_type'       => 'string',
                'store'             => true,
                'description'       => 'The display name of the pack line.'
            ],

            'parent_product_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\catalog\Product',
                'description'       => "The Product this line belongs to.",
                'required'          => true
            ],

            'child_product_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\catalog\Product',
                'description'       => "The Product this line refers to.",
                'required'          => true,
                'onchange'          => 'sale\catalog\PackLine::onchangeChildProductId'                
            ],

            'has_own_qty' => [
                'type'              => 'boolean',
                'description'       => "Does product have its own quantity (whatever the quantityt applied to the parent product)?"
            ],

            'own_qty' => [
                'type'              => 'integer',
                'description'       => "Self assigned quantity for this product.",
                'visible'           => ['has_own_qty', '=', true]
            ],

            'share' => [
                'type'              => 'float',
                'usage'             => 'amount/percent',
                'default'           => 1.0,
                'description'       => "Percent share of the line for analytics."
            ]

        ];
    }

    public static function onchangeChildProductId($om, $oids, $lang) {
        $om->write(__CLASS__, $oids, [ 'name' => null ], $lang);
    }

    public static function getDisplayName($om, $oids, $lang) {
        $result = [];
        $lines = $om->read(__CLASS__, $oids, ['child_product_id.name', 'child_product_id.sku',]);
        foreach($oids as $oid) {
            $result[$oid] = $lines[$oid]['child_product_id.name'].' ('.$lines[$oid]['child_product_id.sku'].')';
        }
        return $result;
    }

    public static function getCanSell($om, $oids, $lang) {
        $result = [];
        $lines = $om->read(__CLASS__, $oids, ['child_product_id.can_sell']);
        foreach($lines as $lid => $line) {
            $result[$lid] = $line['child_product_id.can_sell'];
        }
        return $result;
    }

    public function getUnique() {
        return [
            ['parent_product_id', 'child_product_id']
        ];
    }       
}