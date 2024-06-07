<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2024
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

namespace sale\booking;

class Payment extends \sale\pay\Payment {

    public static function getColumns() {
        return [

            'booking_id' => [
                'type'              => 'computed',
                'result_type'       => 'many2one',
                'function'          => 'calcBookingId',
                'foreign_object'    => 'sale\booking\Booking',
                'description'       => 'The booking the payment relates to, if any (computed).',
                'store'             => true
            ],

            'funding_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\booking\Funding',
                'description'       => 'The funding the payment relates to, if any.',
                'onupdate'          => 'onupdateFundingId'
            ],

            'payment_method' => [
                'type'              => 'string',
                'selection'         => [
                    'cash',                 // cash money
                    'bank_card',            // electronic payment with bank (or credit) card
                    'booking',              // payment through addition to the final (balance) invoice of a specific booking
                    'voucher'               // gift, coupon or tour-operator voucher
                ],
                'description'       => "The method used for payment at the cashdesk.",
                'visible'           => ['payment_origin', '=', 'cashdesk'],
                'default'           => 'cash'
            ]

        ];
    }

    public static function calcBookingId($self) {
        $result = [];
        $self->read(['funding_id']['booking_id']);
        foreach($self as $id => $payment) {
            $result[$id] = $payment['funding_id']['booking_id'];
        }

        return $result;
    }

    public static function onchange($event, $values) {
        $result = [];

        if(isset($event['funding_id'])) {
            $funding = Funding::id($event['funding_id'])
                ->read(['type', 'due_amount', 'booking_id' => ['customer_id' => ['name']], 'invoice_id' => ['customer_id' => ['name']]])
                ->first();

            if(!is_null($funding)) {
                if($funding['funding_type'] == 'invoice')  {
                    $result['customer_id'] = [
                        'id'   => $funding['invoice_id']['customer_id']['id'],
                        'name' => $funding['invoice_id']['customer_id']['name']
                    ];
                }
                else {
                    $result['customer_id'] = [
                        'id'   => $funding['booking_id']['customer_id']['id'],
                        'name' => $funding['booking_id']['customer_id']['name']
                    ];
                }

                if(isset($values['amount']) && $values['amount'] > $funding['due_amount']) {
                    $result['amount'] = $funding['due_amount'];
                }
            }
        }

        return $result;
    }

    /**
     * Assign customer_id and invoice_id from invoice relating to funding, if any.
     * Force recomputing of target funding computed fields (is_paid and paid_amount).
     */
    public static function onupdateFundingId($self, $values) {
        trigger_error("ORM::calling sale\booking\Payment::onupdateFundingId", QN_REPORT_DEBUG);

        $funding_fields = [
            'funding_type',
            'due_amount',
            'booking_id' => ['customer_id'],
        ];

        $self->read(['funding_id', 'customer_id', 'funding_id' => $funding_fields, 'amount', 'statement_line_id']);
        foreach($self as $id => $payment) {
            if($payment['funding_id']) {
                // make sure a customer_id is assigned to the payment
                if(is_null($payment['customer_id']) && isset($payment['funding_id']['booking_id'])) {
                    self::id($id)->update([
                        'customer_id' => $payment['funding_id']['booking_id']['customer_id'],
                        'invoice_id'  => $payment['funding_id']['booking_id']['id'],
                    ]);
                }

                self::id($id)->update(['is_paid' => null, 'paid_amount' => null]);
                $fundings_ids[] = $payment['funding_id'];
            }
        }

        Funding::ids($fundings_ids)->read(['is_paid', 'paid_amount']);
    }
}
