<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace sale\pos;
use equal\orm\Model;

class Order extends Model {

    public static function getColumns() {

        return [

            'name' => [
                'type'              => 'computed',
                'result_type'       => 'string',
                'function'          => 'calcName',
                'store'             => true,
                'description'       => 'Number of the order.'
            ],

            'sequence' => [
                'type'              => 'computed',
                'result_type'       => 'integer',
                'function'          => 'calcSequence',
                'store'             => true,
                'description'       => 'Sequence number (used for naming).'
            ],

            'status' => [
                'type'              => 'string',
                'selection'         => [
                    'pending',           // consumptions (lines) are being added to the order
                    'payment',           // a waiter is proceeding to the payement
                    'paid'               // order is closed and payment has been received
                ],
                'description'       => 'Current status of the order.',
                'default'           => 'pending'
            ],

            'customer_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\customer\Customer',
                'description'       => 'The customer the order relates to.',
                'required'          => true
            ],

            'session_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\pos\CashdeskSession',
                'description'       => 'The session the order belongs to.',
                'onupdate'          => 'onupdateSessionId',
                'required'          => true
            ],

            'has_invoice' => [
                'type'              => 'boolean',
                'description'       => 'Does the order relate to an invoice?',
                'default'           => false
            ],

            'invoice_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'finance\accounting\Invoice',
                'description'       => 'The invoice that relates to the order, if any.',
                'visible'           => ['has_invoice', '=', true]
            ],

            'total' => [
                'type'              => 'computed',
                'result_type'       => 'float',
                'usage'             => 'amount/money',                
                'description'       => 'Total tax-excluded price for all lines (computed).',
                'function'          => 'calcTotal',
                'store'             => true
            ],

            'price' => [
                'type'              => 'computed',
                'result_type'       => 'float',
                'usage'             => 'amount/money',                
                'description'       => 'Final tax-included price for all lines (computed).',
                'function'          => 'calcPrice',
                'store'             => true
            ],

            'order_lines_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'sale\pos\OrderLine',
                'foreign_field'     => 'order_id',
                'ondetach'          => 'delete',
                'onupdate'          => 'onupdateOrderLinesIds',
                'description'       => 'The lines that relate to the order.'
            ],

            'order_payments_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'sale\pos\OrderPayment',
                'foreign_field'     => 'order_id',
                'ondetach'          => 'delete',
                'description'       => 'The payments that relate to the order.'
            ]

        ];
    }

    public static function onupdateSessionId($om, $ids, $lang) {
        $om->write(get_called_class(), $ids, ['name' => null, 'sequence' => null], $lang);
    }

    public static function onupdateOrderLinesIds($om, $ids, $lang) {
        $om->write(get_called_class(), $ids, ['price' => null, 'total' => null], $lang);
    }

    public static function calcName($om, $ids, $lang) {
        $result = [];
        $orders = $om->read(get_called_class(), $ids, ['sequence', 'session_id', 'session_id.cashdesk_id'], $lang);
        if($orders > 0) {
            foreach($orders as $oid => $order) {
                $result[$oid] = sprintf("%03d.%05d.%03d", $order['session_id.cashdesk_id'], $order['session_id'], $order['sequence']);
            }
        }
        return $result;
    }

    public static function calcSequence($om, $ids, $lang) {
        trigger_error("QN_DEBUG_ORM::calling sale\pos\Order:calcSequence", QN_REPORT_DEBUG);
        $result = [];
        $orders = $om->read(get_called_class(), $ids, ['session_id'], $lang);
        if($orders > 0) {
            foreach($orders as $oid => $order) {
                $result[$oid] = 1;
                $orders_ids = $om->search(get_called_class(), ['session_id', '=', $order['session_id']]);
                if($orders_ids >= 0) {
                    $result[$oid] = count($orders_ids) + 1;
                }                
            }
        }
        return $result;
    }


    public static function calcTotal($om, $ids, $lang) {
        $result = [];
        $orders = $om->read(__CLASS__, $ids, ['order_lines_ids.total']);
        if($orders > 0) {
            foreach($orders as $oid => $order) {
                $result[$oid] = 0.0;
                if($order['order_lines_ids.total'] > 0) {
                    foreach($order['order_lines_ids.total'] as $lid => $line) {
                        $result[$oid] += $line['total'];
                    }
                    $result[$oid] = round($result[$oid], 2);
                }
            }
        }
        return $result;
    }

    public static function calcPrice($om, $ids, $lang) {
        $result = [];
        $orders = $om->read(__CLASS__, $ids, ['order_lines_ids.price']);
        if($orders > 0) {
            foreach($orders as $oid => $order) {
                $result[$oid] = 0.0;
                if($order['order_lines_ids.price'] > 0) {
                    foreach($order['order_lines_ids.price'] as $lid => $line) {
                        $result[$oid] += $line['price'];
                    }
                    $result[$oid] = round($result[$oid], 2);
                }
            }
        }
        return $result;
    }


    public static function canupdate($om, $ids, $values, $lang) {
        if(isset($values['session_id'])) {
            $res = $om->read('sale\pos\CashdeskSession', $values['session_id'], [ 'status' ]);

            if($res > 0) {
                $session = reset($res);
                if($session['status'] != 'pending') {
                    return ['session_id' => ['non_editable' => 'Orders can only be assigned to open sessions.']];
                }
            }
        }
        return parent::canupdate($om, $ids, $values, $lang);
    }
}