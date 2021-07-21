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
                'description'       => 'The customer the payment relates to.',
            ],

            'delay_from_event' => [
                'type'              => 'string',
                'selection'         => ['booking','checkin', 'checkout'],
                'description'       => "Start event date of the deadline counter."
            ],

            'delay_count' => [
                'type'              => 'integer',
                'description'       => "Number of days before reaching the deadline."
            ],

            'type' => [
                'type'              => 'string',
                'selection'         => ['installment','invoice'],
                'description'       => "Deadlines are installment except for last one: final invoice."
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
                'required'          => true
            ]

        ];
    }

}