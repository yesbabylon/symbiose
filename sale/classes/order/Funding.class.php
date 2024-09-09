<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2024
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace sale\order;

use core\setting\Setting;

class Funding extends \sale\pay\Funding {

    public static function getColumns() {

        return [

            /**
             * Override Pay Funding columns
             */

            'due_amount' => [
                'type'              => 'float',
                'usage'             => 'amount/money:2',
                'description'       => 'Amount expected for the funding.',
                'required'          => true,
                'dependencies'      => ['is_paid' , 'name']
            ],

            'name' => [
                'type'              => 'computed',
                'result_type'       => 'string',
                'description'       => 'Display name of funding.',
                'function'          => 'calcName',
                'store'             => true,
                'instant'           => true
            ],

            'invoice_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\order\Invoice',
                'description'       => 'The invoice targeted by the funding, if any.',
                'visible'           => ['funding_type', '=', 'invoice']
            ],

            'payment_reference' => [
                'type'              => 'computed',
                'result_type'       => 'string',
                'function'          => 'calcPaymentReference',
                'description'       => 'Message for identifying the purpose of the transaction.',
                'store'             => true
            ],

            'payments_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'sale\order\Payment',
                'foreign_field'     => 'funding_id',
                'description'       => 'Customer payments of the funding.'
            ],

            'funding_type' => [
                'type'              => 'string',
                'selection'         => [
                    'installment',
                    'invoice'
                ],
                'default'           => 'installment',
                'description'       => "Deadlines are installment except for last one: final invoice."
            ],

            'due_date' => [
                'type'              => 'date',
                'description'       => "Deadline before which the funding is expected.",
                'default'           => time()
            ],

            /**
             * Specific Order Funding columns
             */

            'amount_share' => [
                'type'              => 'computed',
                'result_type'       => 'float',
                'usage'             => 'amount/percent',
                'function'          => 'calcAmountShare',
                'store'             => true,
                'description'       => "Share of the payment over the total due amount (order).",
                'dependencies'      => ['is_paid']
            ],

            'order_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\order\Order',
                'description'       => 'Order the contract relates to.',
                'ondelete'          => 'cascade',
                'required'          => true
            ],

            'payment_deadline_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\pay\PaymentDeadline',
                'description'       => "The deadline model used for creating the funding, if any.",
                'onupdate'          => 'onupdatePaymentDeadlineId',
                'default'           => 1
            ],

            'order' => [
                'type'              => 'integer',
                'description'       => 'Order by which the fundings have to be sorted when presented.',
                'default'           => 0
            ]

        ];
    }


    public static function onchange($event, $values) {
        $result = [];
        if(isset($event['due_amount'])) {
            $name_funding =  Setting::format_number_currency($event['due_amount']);
            if(isset($values['order_id'])) {
                $order = Order::id($values['order_id'])->read(['name'])->first(true);
                $name_funding .= '    '.$order['name'];
            }
            $result['name'] = $name_funding;
        }

        return $result;
    }

    public static function calcName($self) {
        $result = [];
        $self->read(['order_id' => ['name'], 'due_amount']);
        foreach($self as $id => $funding) {
            $result[$id] =  Setting::format_number_currency($funding['due_amount']);
            if(isset($funding['order_id']['name'])) {
                $result[$id] .= '    '.$funding['order_id']['name'];
            }
        }

        return $result;
    }

    public static function calcAmountShare($self) {
        $result = [];
        $self->read(['order_id' => ['price'], 'due_amount']);
        foreach($self as $id => $funding) {
            $total = round($funding['order_id']['price'], 2);
            if($total == 0) {
                $share = 1;
            }
            else {
                $share = round(abs($funding['due_amount']) / abs($total), 2);
            }
            $sign = ($funding['due_amount'] < 0) ? -1 : 1;
            $result[$id] = $share * $sign;
        }

        return $result;
    }

    public static function calcPaymentReference($self) {
        $result = [];
        $self->read(['order_id' => ['name'], 'type', 'order', 'payment_deadline_id' => ['code']]);
        foreach($self as $id => $funding) {
            $order_code = intval($funding['order_id']['name']);
            if($funding['payment_deadline_id']['code']) {
                $code_ref = intval($funding['payment_deadline_id']['code']);
            }
            else {
                // arbitrary value : 151 for first funding, 152 for second funding, ...
                $code_ref = 150;
                if($funding['order']) {
                    $code_ref += $funding['order'];
                }
            }
            $result[$id] = self::_get_payment_reference($code_ref, $order_code);
        }

        return $result;
    }

    public static function cancreate($self, $values) {
        if(isset($values['order_id'], $values['due_amount'])) {
            $order = Order::id($values['order_id'])
                ->read(['price', 'fundings_ids' => ['due_amount']])
                ->first();

            if(!is_null($order)) {
                $fundings_price = (float) $values['due_amount'];
                foreach($order['fundings_ids'] as $funding) {
                    $fundings_price += (float) $funding['due_amount'];
                }
                if($fundings_price > $order['price'] && abs($order['price'] - $fundings_price) >= 0.0001) {
                    return ['status' => ['exceeded_price' => 'Sum of the fundings cannot be higher than the order total.']];
                }
            }
        }

        return parent::cancreate($self, $values);
    }

    public static function canupdate($self, $values) {
        if(isset($values['due_amount'])) {
            $self->read(['order_id' => ['price', 'fundings_ids' => ['due_amount']]]);
            foreach($self as $funding) {
                $fundings_price = 0;
                foreach($funding['order_id']['fundings_ids'] as $order_funding) {
                    $fundings_price += (float) $order_funding['due_amount'];
                }
                if(
                    $fundings_price > $funding['order_id']['price']
                    && abs($funding['order_id']['price'] - $fundings_price) >= 0.0001
                ) {
                    return ['status' => ['exceeded_price' => "Sum of the fundings cannot be higher than the order total."]];
                }
            }
        }

        return parent::canupdate($self, $values);
    }

}
