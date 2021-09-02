<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace sale\discount;
use equal\orm\Model;

class DiscountList extends Model {

    public static function getColumns() {

        return [
            'name' => [
                'type'              => 'string',
                'description'       => "Short memo for the list (ex. discounts 2025).",
                'required'          => true
            ],

            'valid_from' => [
                'type'              => 'date',
                'description'       => "Date from which the list is valid (included).",
                'default'           => time()
            ],

            'valid_until' => [
                'type'              => 'date',
                'description'       => "Moment until when the list is valid (included).",
                'default'           => time()                
            ],

            'discounts_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'sale\discount\Discount',
                'foreign_field'     => 'discount_list_id',
                'description'       => 'The discounts that are part of the list.'
            ],

            'discount_list_category_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\discount\DiscountListCategory',
                'description'       => 'The category the list belongs to.',
                'required'          => true
            ],

            'discount_class_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\discount\DiscountClass',
                'description'       => 'The discount class the list belongs to.',
                'required'          => true,
                'onchange'          => 'sale\discount\DiscountList::onchangeDiscountClassId'
            ],

            'rate_class_id' => [
                'type'              => 'computed',
                'result_type'       => 'integer',
                'function'          => 'sale\discount\DiscountList::getRateClassId',
                'description'       => "The rate class that applies to the parent class of discount.",
                'store'             => true
            ]

        ];
    }

    public static function onchangeDiscountClassId($om, $oids, $lang) {
        $om->write(__CLASS__, $oids, ['rate_class_id' => null]);
        // force immediate re-computing
        $om->read(__CLASS__, $oids, ['rate_class_id']);
    }    

    public static function getRateClassId($om, $oids, $lang) {
        $result = [];
        $lists = $om->read(__CLASS__, $oids, ['discount_class_id.rate_class_id']);
        foreach($lists as $oid => $list) {
            $result[$oid] = $list['discount_class_id.rate_class_id'];
        }
        return $result;
    }

}