<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2024
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

namespace sale\booking;

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
                'dependents'        => ['name', 'amount_share']
            ],

            'invoice_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\booking\Invoice',
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
                'foreign_object'    => 'sale\booking\Payment',
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

            /**
             * Specific Booking Funding columns
             */

            'amount_share' => [
                'type'              => 'computed',
                'result_type'       => 'float',
                'usage'             => 'amount/percent',
                'function'          => 'calcAmountShare',
                'store'             => true,
                'description'       => "Share of the payment over the total due amount (booking)."
            ],

            'booking_id' => [
                'type'              => 'many2one',
                'foreign_object'    => Booking::getType(),
                'description'       => 'Booking the contract relates to.',
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


    public static function calcName($self) {
        $result = [];
        $self->read(['booking_id' => ['name'], 'due_amount']);
        foreach($self as $id => $funding) {
            $result[$id] = Setting::format_number_currency($funding['due_amount']);
            if(isset($funding['booking_id']['name'])) {
                $result[$id] .= '    '.$funding['booking_id']['name'];
            }
        }

        return $result;
    }

    public static function calcAmountShare($self) {
        $result = [];
        $self->read(['booking_id' => ['price'], 'due_amount']);
        foreach($self as $id => $funding) {
            $total = round($funding['booking_id']['price'], 2);
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
        $self->read(['booking_id' => ['name'], 'type', 'order', 'payment_deadline_id' => ['code']]);
        foreach($self as $id => $funding) {
            $booking_code = intval($funding['booking_id']['name']);
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
            $result[$id] = self::_get_payment_reference($code_ref, $booking_code);
        }

        return $result;
    }

    public static function cancreate($self, $values) {
        if(isset($values['booking_id'], $values['due_amount'])) {
            $booking = Booking::id($values['booking_id'])
                ->read(['price', 'fundings_ids' => ['due_amount']])
                ->first();

            if(!is_null($booking)) {
                $fundings_price = (float) $values['due_amount'];
                foreach($booking['fundings_ids'] as $funding) {
                    $fundings_price += (float) $funding['due_amount'];
                }
                if($fundings_price > $booking['price'] && abs($booking['price'] - $fundings_price) >= 0.0001) {
                    return ['status' => ['exceeded_price' => 'Sum of the fundings cannot be higher than the booking total.']];
                }
            }
        }

        return parent::cancreate($self, $values);
    }

    public static function canupdate($self, $values) {
        if(isset($values['due_amount'])) {
            $self->read(['booking_id' => ['price', 'fundings_ids' => ['due_amount']]]);
            foreach($self as $funding) {
                $fundings_price = 0;
                foreach($funding['booking_id']['fundings_ids'] as $booking_funding) {
                    $fundings_price += (float) $booking_funding['due_amount'];
                }
                if(
                    $fundings_price > $funding['booking_id']['price']
                    && abs($funding['booking_id']['price'] - $fundings_price) >= 0.0001
                ) {
                    return ['status' => ['exceeded_price' => "Sum of the fundings cannot be higher than the booking total."]];
                }
            }
        }

        return parent::canupdate($self, $values);
    }
}
