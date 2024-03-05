<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace sale\pos;

class OrderPaymentPart extends \sale\booking\Payment {

    public function getTable() {
        return 'sale_pos_orderpaymentpart';
    }

    public static function getColumns() {
        return [

            'order_payment_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\pos\OrderPayment',
                'description'       => 'The order payment the part relates to.',
                'onupdate'          => 'onupdateOrderPaymentId'
            ],

            'amount' => [
                'type'              => 'float',
                'usage'             => 'amount/money:2',
                'description'       => 'Amount paid (whatever the origin).',
                'onupdate'          => 'onupdateAmount'
            ],

            'order_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\pos\Order',
                'description'       => 'The order the part relates to (based on payment).'
            ],

            'payment_origin' => [
                'type'              => 'string',
                'description'       => "Origin of the received money.",
                'default'           => 'cashdesk'
            ],

            'status' => [
                'type'              => 'string',
                'selection'         => [
                    'pending',      // payment part hasn't been validated yet
                    'paid'          // amount has been received (cannot be undone)
                ],
                'description'       => 'Current status of the payment part.',
                'default'           => 'pending'
            ]

        ];
    }

    public static function onupdateAmount($om, $ids, $values, $lang) {
        $parts = $om->read(get_called_class(), $ids, ['order_payment_id'], $lang);
        if($parts > 0) {
            $order_payments_ids = array_reduce($parts, function($c, $o) { return array_merge($c, [$o['order_payment_id']]); }, []);
            $om->write('sale\pos\OrderPayment', $order_payments_ids, ['total_paid' => null ], $lang);
        }
    }

    public static function onupdateOrderPaymentId($om, $ids, $values, $lang) {
        $parts = $om->read(get_called_class(), $ids, ['order_payment_id.order_id'], $lang);
        if($parts > 0) {
            foreach($parts as $pid => $part) {
                $om->write(get_called_class(), $pid, ['order_id' => $part['order_payment_id.order_id'] ], $lang);
            }
        }
    }

}