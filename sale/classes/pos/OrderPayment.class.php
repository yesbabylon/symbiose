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
                'onupdate'          => 'onupdateOrderId'
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

            'has_funding' => [
                'type'              => 'boolean',
                'description'       => 'Mark the line as relating to a funding.',
                'default'           => false
            ],

            'funding_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\pay\Funding',
                'description'       => 'The funding the line relates to, if any.',
                'visible'           => ['has_funding', '=', true]
            ],

            /*
                #memo - if the payment is attached to a funding, it can only have one line
            */

            'order_lines_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'sale\pos\OrderLine',
                'foreign_field'     => 'order_payment_id',
                'ondetach'          => 'null',
                'description'       => 'The order lines selected for the payement.',
                'onupdate'          => 'onupdateOrderLinesIds'
            ],

            'order_payment_parts_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => OrderPaymentPart::getType(),
                'foreign_field'     => 'order_payment_id',
                'description'       => 'The parts that relate to the payement.',
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

    public static function calcTotalPaid($om, $ids, $lang) {
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

    public static function onupdateOrderPaymentPartsIds($om, $ids, $values, $lang) {
        $om->write(__CLASS__, $ids, ['total_paid' => null], $lang);
    }

    public static function onupdateOrderLinesIds($om, $ids, $values, $lang) {
        $om->write(__CLASS__, $ids, ['total_due' => null], $lang);
    }

}