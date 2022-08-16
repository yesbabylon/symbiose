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
                'visible'           => ['relationship', '=', 'customer'],
                'default'           => 1,
                'readonly'          => true
            ],

            'customer_nature_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\customer\CustomerNature',
                'description'       => 'Nature of the customer (map with rate classes).',
                'onupdate'          => 'sale\customer\Customer::onupdateCustomerNatureId'
            ],

            // if partner is a customer, it can be assigned a customer type
            'customer_type_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\customer\CustomerType',
                'description'       => 'Type of customer (map with rate classes).',
                'visible'           => ['relationship', '=', 'customer'],
                'default'           => 1                                                // default is 'individual'
            ],

            'relationship' => [
                'type'              => 'string',
                'default'           => 'customer',
                'description'       => 'Force relationship to Customer'
            ],

            'is_tour_operator' => [
                'type'              => 'boolean',
                'description'       => 'Mark the customer as a Tour Operator.',
                'default'           => false
            ],

            'count_booking_12' => [
                'type'              => 'computed',
                'result_type'       => 'integer',
                'function'          => 'sale\customer\Customer::calcCountBooking12',
                'description'       => 'Number of bookings made during last 12 months (one year).'
            ],

            'count_booking_24' => [
                'type'              => 'computed',
                'result_type'       => 'integer',
                'function'          => 'sale\customer\Customer::calcCountBooking24',
                'description'       => 'Number of bookings made during last 24 months (2 years).'
            ],

            'address' => [
                'type'              => 'computed',
                'result_type'       => 'string',
                'function'          => 'calcAddress',
                'description'       => 'Main address from related Identity.',
                'store'             => true
            ],

            'bookings_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'sale\booking\Booking',
                'foreign_field'     => 'customer_id',
                'description'       => "The bookings history of the customer.",
            ],

            'ref_account' => [
                'type'              => 'string',
                'usage'             => 'uri/urn.iban',
                'description'       => 'Arbitrary reference account number for identifying the customer in external accounting softwares.',
                'readonly'          => true
            ]

        ];
    }

    public static function onupdateCustomerNatureId($om, $oids, $values, $lang) {
        $customers = $om->read(__CLASS__, $oids, ['customer_nature_id.rate_class_id', 'customer_nature_id.customer_type_id']);
        if($customers > 0 && count($customers)) {
            foreach($customers as $cid => $customer) {
                $customer_type_id = $customer['customer_nature_id.customer_type_id'];
                $rate_class_id = $customer['customer_nature_id.rate_class_id'];
                if(!empty($customer_type_id) && !empty($rate_class_id)) {
                    $om->write(__CLASS__, $oids, ['rate_class_id' => $rate_class_id, 'customer_type_id' => $customer_type_id]);
                }
            }
        }
    }

    /**
     * Computes the number of bookings made by the customer during the last two years.
     *
     */
    public static function calcAddress($om, $oids, $lang) {
        $result = [];

        $customers = $om->read(__CLASS__, $oids, ['partner_identity_id.address_street', 'partner_identity_id.address_city'], $lang);
        foreach($customers as $oid => $customer) {
            $result[$oid] = "{$customer['partner_identity_id.address_street']} {$customer['partner_identity_id.address_city']}";
        }
        return $result;
    }

    /**
     * Computes the number of bookings made by the customer during the last 12 months.
     *
     */
    public static function calcCountBooking12($om, $oids, $lang) {
        $result = [];
        $time = time();
        $from = mktime(0, 0, 0, date('m', $time)-12, date('d', $time), date('Y', $time));
        foreach($oids as $oid) {
            $bookings_ids = $om->search('sale\booking\Booking', [
                ['customer_id', '=', $oid],
                ['created', '>=', $from],
                ['is_cancelled', '=', false],
                ['status', 'not in', ['quote', 'option']]
            ]);
            $result[$oid] = count($bookings_ids);
        }
        return $result;
    }

    /**
     * Computes the number of bookings made by the customer during the last two years.
     *
     */
    public static function calcCountBooking24($om, $oids, $lang) {
        $result = [];
        $time = time();
        $from = mktime(0, 0, 0, date('m', $time)-24, date('d', $time), date('Y', $time));
        foreach($oids as $oid) {
            $bookings_ids = $om->search('sale\booking\Booking', [
                ['customer_id', '=', $oid],
                ['created', '>=', $from],
                ['is_cancelled', '=', false],
                ['status', 'not in', ['quote', 'option']]
            ]);
            $result[$oid] = count($bookings_ids);
        }
        return $result;
    }
}