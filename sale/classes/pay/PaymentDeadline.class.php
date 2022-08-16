<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace sale\pay;
use equal\orm\Model;

class PaymentDeadline extends Model {

    public static function getColumns() {

        return [

            'name' => [
                'type'              => 'string',
                'description'       => 'Short memo of the deadline.',
                'multilang'         => true
            ],

            'code' => [
                'type'              => 'string',
                'usage'             => 'numeric/integer:3',
                'description'       => '3 digits code to serve in payment references.',
                'required'          => true
            ],

            'delay_from_event' => [
                'type'              => 'string',
                'selection'         => ['booking','checkin', 'checkout'],
                'description'       => "Start event date of the deadline counter."
            ],

            'delay_from_event_offset' => [
                'type'              => 'integer',
                'description'       => "Offset to apply on 'from_event' for getting the issue date.",
                'default'           => 0
            ],

            'delay_count' => [
                'type'              => 'integer',
                'description'       => "Number of days before reaching the deadline."
            ],

            'type' => [
                'type'              => 'string',
                'selection'         => [
                    'installment',                 // pre-payment (can be converted to invoice): there can be many of those
                    'invoice'                      // balance invoice (there should be only one ot that type)
                ],
                'description'       => "Deadlines are installment except for last one, the final invoice."
            ],

            'is_balance_invoice' => [
                'type'              => 'boolean',
                'default'           => false,
                'description'       => "Mark invoice as the final balance invoice (no installment).",
                'visible'           => ['type', '=', 'invoice']
            ],

            'amount_share' => [
                'type'              => 'float',
                'usage'             => 'amount/percent',
                'description'       => "Share of the payment over the total due amount.",
                'default'           => 1.0
            ],

            'payment_plan_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\pay\PaymentPlan',
                'description'       => "The payment plan the deadline applies to.",
                'ondelete'          => 'delete'
            ]
        ];
    }

}