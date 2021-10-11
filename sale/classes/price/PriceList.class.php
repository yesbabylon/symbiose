<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace sale\price;
use equal\orm\Model;

class PriceList extends Model {
    public static function getColumns() {
        /**
         */

        return [
            'name' => [
                'type'              => 'string',
                'description'       => "Short label to ease identification of the list."
            ],

            'date_from' => [
                'type'              => 'date',
                'description'       => "Start of validity period.",
                'required'          => true
            ],

            'date_to' => [
                'type'              => 'date',
                'description'       => "End of validity period.",
                'required'          => true
            ],

            'duration' => [
                'type'              => 'computed',
                'result_type'       => 'integer',
                'function'          => 'sale\price\PriceList::getDuration',
                'store'             => true,
                'description'       => "Pricelist validity duration, in days."
            ],

            'is_active' => [
                'type'              => 'computed',
                'result_type'       => 'boolean',
                'function'          => 'sale\price\PriceList::getIsActive',
                'store'             => true,
                'description'       => "Is the pricelist still applicable?"
            ],

            'prices_count' => [
                'type'              => 'computed',
                'result_type'       => 'integer',
                'function'          => 'sale\price\PriceList::getPricesCount',
                // 'store'             => true,
                'description'       => "Amount of prices defined in list."
            ],

            'prices_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'sale\price\Price',
                'foreign_field'     => 'price_list_id',
                'description'       => "Prices that are related to this list, if any.",
            ],

            'price_list_category_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\price\PriceListCategory',
                'description'       => "Category this list is related to, if any.",
            ]

        ];
    }

    public static function getDuration($om, $oids, $lang) {
        $result = [];
        $lists = $om->read(__CLASS__, $oids, ['date_from', 'date_to']);

        if($lists > 0 && count($lists)) {
            foreach($lists as $lid => $list) {
                $result[$lid] = round( ($list['date_to'] - $list['date_from']) / (60 * 60 * 24));
            }
        }
        return $result;
    }

    public static function getIsActive($om, $oids, $lang) {
        $result = [];
        $lists = $om->read(__CLASS__, $oids, ['date_from', 'date_to']);

        $now = time();

        if($lists > 0 && count($lists)) {
            foreach($lists as $lid => $list) {
                $result[$lid] = boolval($list['date_to'] > $now);
            }
        }
        return $result;
    }


    public static function getPricesCount($om, $oids, $lang) {
        $result = [];
        $lists = $om->read(__CLASS__, $oids, ['prices_ids']);

        $now = time();

        if($lists > 0 && count($lists)) {
            foreach($lists as $lid => $list) {
                $result[$lid] = count($list['prices_ids']);
            }
        }
        return $result;        
    }


}