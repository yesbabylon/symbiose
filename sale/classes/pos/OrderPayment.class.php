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
                'foreign_object'    => Order::getType(),
                'description'       => 'The order the line relates to.',
                'onupdate'          => 'onupdateOrderId'
            ],

            'status' => [
                'type'              => 'string',
                'selection'         => [
                    'pending',      // payment hasn't been validated yet
                    'paid'          // amount has been received (cannot be undone)
                ],
                'description'       => 'Current status of the payment.',
                'default'           => 'pending',
                'onupdate'          => 'onupdateStatus'
            ],

            'has_funding' => [
                'type'              => 'boolean',
                'description'       => 'Mark the line as relating to a funding.',
                'default'           => false
            ],

            'funding_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\pay\Funding',
                'description'       => 'The funding the line relates to, if any.',
                'visible'           => ['has_funding', '=', true],
                'onupdate'          => 'onupdateFundingId'
            ],

            /*
                #memo - if the payment is attached to a funding, it can have only one line
            */

            'order_lines_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => OrderLine::getType(),
                'foreign_field'     => 'order_payment_id',
                'ondetach'          => 'null',
                'description'       => 'The order lines selected for the payment.',
                'onupdate'          => 'onupdateOrderLinesIds'
            ],

            'order_payment_parts_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => OrderPaymentPart::getType(),
                'foreign_field'     => 'order_payment_id',
                'description'       => 'The parts that relate to the payment.',
                'onupdate'          => 'onupdateOrderPaymentPartsIds'
            ],

            'total_paid' => [
                'type'              => 'computed',
                'result_type'       => 'float',
                'usage'             => 'amount/money:2',
                'description'       => 'Total paid amount from payment parts.',
                'function'          => 'calcTotalPaid'
            ],

            'total_due' => [
                'type'              => 'computed',
                'result_type'       => 'float',
                'usage'             => 'amount/money:2',
                'description'       => 'Total due amount (tax incl.) from selected lines.',
                'function'          => 'calcTotalDue'
            ],

            'total_change' => [
                'type'              => 'computed',
                'result_type'       => 'float',
                'usage'             => 'amount/money:2',
                'description'       => 'Total due amount (tax incl.) from selected lines.',
                'function'          => 'calcTotalChange'
            ]

        ];
    }

    /**
     * Populate the payement with remaining orderLines.
     * This handled is mostly called upon creation and assignation to an order.
     *
     */
    public static function onupdateOrderId($om, $ids, $values, $lang) {
    }

    /**
     *
     */
    public static function onupdateStatus($om, $ids, $values, $lang) {
        /*
        $payments = $om->read(self::getType(), $ids, ['status', 'order_payment_parts_ids'], $lang);
        if($payments > 0) {
            foreach($payments as $pid => $payment) {
            }
        }
        */
    }

    public static function onupdateFundingId($om, $ids, $values, $lang) {
        $payments = $om->read(self::getType(), $ids, ['order_id', 'funding_id'], $lang);
        if($payments > 0) {
            foreach($payments as $pid => $payment) {
                $om->update(self::getType(), $pid, ['has_funding' => ($payment['funding_id'] > 0)], $lang);
                $om->update(Order::getType(), $payment['order_id'], ['funding_id' => $payment['funding_id']], $lang);
            }
        }
    }

    public static function calcTotalPaid($om, $ids, $lang) {
        $result = [];
        $payments = $om->read(self::getType(), $ids, ['order_payment_parts_ids'], $lang);
        if($payments > 0) {
            foreach($payments as $id => $payment) {
                $result[$id] = 0.0;
                $parts = $om->read(OrderPaymentPart::getType(), $payment['order_payment_parts_ids'], ['status', 'amount'], $lang);
                foreach($parts as $part) {
                    if($part['status'] == 'paid') {
                        $result[$id] += $part['amount'];
                    }
                }
                $result[$id] = round($result[$id], 2);
            }
        }
        return $result;
    }

    public static function calcTotalDue($om, $ids, $lang) {
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

    public static function calcTotalChange($om, $ids, $lang) {
        $result = [];
        $payments = $om->read(__CLASS__, $ids, ['total_due', 'total_paid'], $lang);
        if($payments > 0) {
            foreach($payments as $id => $payment) {
                $result[$id] = 0.0;
                if($payment['total_due'] > 0) {
                    $result[$id] = -round($payment['total_paid'] - $payment['total_due'], 2);
                }
            }
        }
        return $result;
    }

    public static function onupdateOrderPaymentPartsIds($om, $ids, $values, $lang) {
        $om->write(__CLASS__, $ids, ['total_paid' => null], $lang);
    }

    public static function onupdateOrderLinesIds($om, $ids, $values, $lang) {
        $om->write(__CLASS__, $ids, ['total_due' => null], $lang);
    }

}