<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace lodging\sale\pos;

class OrderPayment extends \sale\pos\OrderPayment {

    public static function getColumns() {

        return [

            'order_id' => [
                'type'              => 'many2one',
                'foreign_object'    => Order::getType(),
                'description'       => 'The order the line relates to.',
                'onupdate'          => 'onupdateOrderId'
            ],
        
            'order_payment_parts_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => OrderPaymentPart::getType(),
                'foreign_field'     => 'order_payment_id',
                'description'       => 'The parts that relate to the payement.',
                'onupdate'          => 'onupdateOrderPaymentPartsIds'
            ],

            'order_lines_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => OrderLine::getType(),
                'foreign_field'     => 'order_payment_id',
                'ondetach'          => 'null',
                'description'       => 'The order lines selected for the payement.',
                'onupdate'          => 'onupdateOrderLinesIds'
            ],

            'has_booking' => [
                'type'              => 'boolean',
                'description'       => 'Mark the payment as done using a booking.',
                'default'           => false,
                'onupdate'          => 'onupdateHasBooking'
            ]

        ];
    }
    
    public static function onupdateHasBooking($om, $ids, $values, $lang) {
        // upon update, update related order lines accordingly
        // upon update of the payment mehtod, adapt parent payment and related line
        $payments = $om->read(self::getType(), $ids, ['has_booking', 'order_lines_ids'], $lang);
        if($payments > 0) {
            foreach($payments as $oid => $payment) {
                $om->update(OrderLine::getType(), $payment['order_lines_ids'], ['has_booking' => $payment['has_booking']], $lang);
            }
        }
    }

}