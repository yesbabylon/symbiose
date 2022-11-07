<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace sale\catalog;
use equal\orm\Model;

class ProductFavorite extends Model {

    public static function getName() {
        return "Product favorite";
    }

    public static function getDescription() {
        return "Product favorites allow to highlight some specific products (most used or most relevant) in the lists presented to user.";
    }

    public static function getColumns() {
        return [

            'name' => [
                'type'              => 'computed',
                'result_type'       => 'string',
                'function'          => 'calcName',
                'store'             => true,
                'description'       => 'The full name of the product (label + sku).'
            ],

            'order' => [
                'type'              => 'integer',
                'description'       => 'Arbitrary value for ordering the favorites.',
                'default'           => 1
            ],

            'product_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\catalog\Product',
                'description'       => "Targeted product.",
                'onupdate'          => 'onupdateProductId',
                'required'          => true
            ]

        ];
    }

    /**
     * Computes the display name of the product as a concatenation of Label and SKU.
     *
     */
    public static function calcName($om, $oids, $lang) {
        $result = [];
        $res = $om->read(self::getType(), $oids, ['product_id.name'], $lang);
        foreach($res as $oid => $odata) {
            $result[$oid] = "{$odata['product_id.name']}";
        }
        return $result;
    }

    public static function onupdateProductId($om, $oids, $values, $lang) {
        $om->update(self::getType(), $oids, ['name' => null], $lang);
    }

}