<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace sale\customer;

class Customer extends \identity\Partner {

    public static function getName() {
        return 'Customer';
    }

    public static function getDescription() {
        return "A customer is a partner from who originates one or more bookings.";
    }

    public static function getColumns() {

        return [

            'relationship' => [
                'type'              => 'string',
                'default'           => 'customer',
                'description'       => 'Force relationship to Customer'
            ],

            'count_booking_24' => [
                'type'              => 'computed',
                'result_type'       => 'integer',
                'function'          => 'sale\customer\Customer::getCountBooking24',
                'description'       => 'Number of bookings made during last 24 months.'
            ]

        ];
    }

    /**
     * Computes the number of bookings made by the customer during the last two years.
     * 
     */
    public static function getCountBooking24($om, $oids, $lang) {
        $result = [];
        $time = time();
        $from = mktime(0, 0, 0, date('m', $time)-24, date('d', $time), date('Y', $time));
        foreach($oids as $oid) {
            $bookings_ids = $om->search('sale\booking\Booking', [ ['customer_id', '=', $oid], ['created', '>=', $from], ['status', '=', 'validated'] ]);
            $result[$oid] = count($bookings_ids);
        }
        return $result;
    }

}