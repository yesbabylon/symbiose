<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace lodging\sale\pos;

class OrderPaymentPart extends \sale\pos\OrderPaymentPart {

    public static function getColumns() {
        return [

            'order_payment_id' => [
                'type'              => 'many2one',
                'foreign_object'    => OrderPayment::getType(),
                'description'       => 'The order payment the part relates to.',
                'onupdate'          => 'onupdateOrderPaymentId'
            ],

            'order_id' => [
                'type'              => 'many2one',
                'foreign_object'    => Order::getType(),
                'description'       => 'The order the part relates to (based on payment).'
            ],

            'payment_method' => [
                'type'              => 'string',
                'selection'         => [
                    'cash',                 // cash money
                    'bank_card',            // electronic payment with bank (or credit) card
                    'booking',              // payment through addition to the final (balance) invoice of a specific booking
                    'voucher'               // gift, coupon, or tour-operator voucher
                ],
                'description'       => "The method used for payment at the cashdesk.",
                'visible'           => [ ['payment_origin', '=', 'cashdesk'] ],
                'default'           => 'cash',
                'onupdate'          => 'onupdatePaymentMethod'
            ]
        ];
    }


    /**
     * @param \equal\orm\ObjectManager  $om Instance of the ObjectManager service.
     */
    public static function onupdatePaymentMethod($om, $ids, $values, $lang) {
        // upon update of the payment mehtod, adapt parent payment and related line
        $parts = $om->read(self::getType(), $ids, ['payment_method', 'order_payment_id'], $lang);
        if($parts > 0) {
            foreach($parts as $oid => $part) {
                $om->update(OrderPayment::getType(), $part['order_payment_id'], ['has_booking' => ($part['payment_method'] == 'booking')], $lang);
            }
        }
    }

}