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

            // if partner is a customer, it can be assigned to a rate class
            'rate_class_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\customer\RateClass',
                'description'       => 'Rate class that applies to the customer.',
                'visible'           => ['relationship', '=', 'customer']
            ],

            'customer_nature_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\customer\CustomerNature',
                'description'       => 'Nature of the customer (map with rate classes).',            
                'onchange'          => 'sale\customer\Customer::onchangeCustomerNatureId'
            ],

            // if partner is a customer, it can be assigned a customer type
            'customer_type_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\customer\CustomerType',
                'description'       => 'Type of customer (map with rate classes).',
                'visible'           => ['relationship', '=', 'customer']
            ],
            
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

    public static function onchangeCustomerNatureId($om, $oids, $lang) {
        $customers = $om->read(__CLASS__, $oids, ['customer_nature_id.rate_class_id', 'customer_nature_id.customer_type_id']);
        if($customers > 0 && count($customers)) {
            foreach($customers as $cid => $customer) {
                $customer_type_id = $customer['customer_nature_id.customer_type_id'];
                $rate_class_id = $customer['customer_nature_id.rate_class_id'];
                $om->write(__CLASS__, $oids, ['rate_class_id' => $rate_class_id, 'customer_type_id' => $customer_type_id]);
            }
        }
        
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