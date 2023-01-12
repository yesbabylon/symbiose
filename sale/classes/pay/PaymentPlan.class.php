<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace sale\pay;
use equal\orm\Model;

class PaymentPlan extends Model {

    public static function getColumns() {

        return [

            'name' => [
                'type'              => 'string',
                'description'       => 'The name of the plan.',
                'required'          => true
            ],

            'description' => [
                'type'              => 'string',
                'usage'             => 'text/plain',
                'description'       => 'The name of the plan.',
                'required'          => true
            ],

            'rate_class_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\customer\RateClass',
                'description'       => "The rate class that applies to the payment plan."
            ],

            'booking_type_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\booking\BookingType',
                'description'       => "Filter for selecting the plan according to booking type.",
                'default'           => 1                // default to 'general public'
            ],

            'payment_deadlines_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'sale\pay\PaymentDeadline',
                'foreign_field'     => 'payment_plan_id',
                'description'       => 'List of deadlines related to the plan, if any.',
                'ondetach'          => 'delete'
            ]

        ];
    }

}