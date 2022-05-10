<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace sale\pos;
use equal\orm\Model;

class OrderPayment extends Model {

    public static function getColumns() {

        return [

            'order_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\pos\Order',
                'description'       => 'The order the line relates to.',
                'onchange'          => 'onchangeOrderId'
            ],

            'status' => [
                'type'              => 'string',
                'selection'         => [
                    'pending',      // payment hasn't been validated yet
                    'paid'          // amount has been received (cannot be undone)
                ],
                'description'       => 'Current status of the payment.',
                'default'           => 'pending'
            ],

            'order_lines_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'sale\pos\OrderLine',
                'foreign_field'     => 'order_payment_id',
                'description'       => 'The order lines selected for the payement.',
                'onchange'          => 'onchangeOrderLinesIds'
            ],

            'order_payment_parts_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'sale\pos\OrderPaymentPart',
                'foreign_field'     => 'order_payment_id',
                'description'       => 'The parts that relate to the payement.',
                'onchange'          => 'onchangeOrderPaymentPartsIds'
            ],

            'total_paid' => [
                'type'              => 'computed',
                'result_type'       => 'float',
                'usage'             => 'amount/money:2',
                'description'       => 'Total paid amount from payment parts.',
                'function'          => 'getTotalPaid'
            ],

            'total_due' => [
                'type'              => 'computed',
                'result_type'       => 'float',
                'usage'             => 'amount/money:2',
                'description'       => 'Total due amount (tax incl.) from selected lines.',
                'function'          => 'getTotalDue'
            ]

        ];
    }

    /**
     * Populate the payement with remaining orderLines.
     * This handled is mostly called upon creation and assignation to an order.
     * 
     */
    public static function onchangeOrderId($om, $ids, $lang) {
        $payments = $om->read(get_called_class(), $ids, ['order_id'], $lang);
        if($payments > 0) {
            foreach($payments as $pid => $payment) {
                // search for remaining (non-assigned) lines from the order
                $order_lines_ids = $om->search('sale\pos\OrderLine', [ ['order_id', '=', $payment['order_id']], ['order_payment_id', '=', '0'] ]);
                if($order_lines_ids > 0 && count($order_lines_ids)) {
                    $om->write(get_called_class(), $pid, ['order_lines_ids' => $order_lines_ids]);    
                }
            }            
        }
    }

    public static function getTotalPaid($om, $ids, $lang) {
        $result = [];
        $payments = $om->read(__CLASS__, $ids, ['order_payment_parts_ids.amount'], $lang);
        if($payments > 0) {
            foreach($payments as $oid => $payment) {
                $result[$oid] = 0.0;
                foreach($payment['order_payment_parts_ids.amount'] as $part) {
                    $result[$oid] += $part['amount'];
                }
                $result[$oid] = round($result[$oid], 2);
            }
        }
        return $result;
    }

    public static function getTotalDue($om, $ids, $lang) {
        $result = [];
        $payments = $om->read(__CLASS__, $ids, ['order_lines_ids.price'], $lang);
        if($payments > 0) {
            foreach($payments as $oid => $payment) {
                $result[$oid] = 0.0;
                foreach($payment['order_lines_ids.price'] as $line) {
                    $result[$oid] += $line['price'];
                }
                $result[$oid] = round($result[$oid], 2);
            }
        }
        return $result;
    }

    public static function onchangeOrderPaymentPartsIds($om, $ids, $lang) {
        $om->write(__CLASS__, $ids, ['total_paid' => null], $lang);
    }

    public static function onchangeOrderLinesIds($om, $ids, $lang) {
        $om->write(__CLASS__, $ids, ['total_due' => null], $lang);
    }

}