<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace sale\customer;

class TourOperator extends Customer {

    public static function getName() {
        return 'Tour Operator';
    }

    public static function getDescription() {
        return 'Tour Operators are travel agent specialized in package holidays or OTA acting as intermediary for completing bookings.';
    }

    public static function getColumns() {

        return [
            'to_code' => [
                'type'              => 'string',
                'description'       => 'Unique reference code of the TO partner.',
                'unique'            => true
            ],

            'to_commission_rate' => [
                'type'              => 'float',
                'usage'             => 'amount/percent',
                'description'       => 'Rate of the commission of the TO.',
                'default'           => 0.0
            ],

            'is_tour_operator' => [
                'type'              => 'boolean',
                'description'       => 'Mark the customer as a Tour Operator.',
                'default'           => true
            ],

            'customer_nature_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\customer\CustomerNature',
                'description'       => 'Nature of the customer (map with rate classes).',
                'readonly'          => true,
                'default'           => 47       // tour-operator nature
            ]

        ];
    }
}