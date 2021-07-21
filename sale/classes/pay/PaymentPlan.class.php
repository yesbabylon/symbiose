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
                'description'       => 'The customer the payment relates to.',
            ],

            'rate_class_id' => [
                'type'              => 'many2one',                
                'foreign_object'    => 'sale\customer\RateClass',
                'description'       => "The rate class that applies to the payment plan.",
                'required'          => true
            ]

        ];
    }

}