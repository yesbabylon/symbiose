<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace sale\pay;
use equal\orm\Model;

class PaymentTerms extends Model {

    public static function getColumns() {

        return [

            'name' => [
                'type'              => 'string',
                'description'       => 'Short memo of the terms.',
                'multilang'         => true,
                'required'          => true
            ],

            'description' => [
                'type'              => 'string',
                'description'       => 'Description of the terms (1 sentence, displayed in docs).',
                'multilang'         => true,
                'required'          => true                
            ],

            'delay_from' => [
                'type'              => 'string',
                'selection'         => ['created','next_month'],
                'description'       => "Event from which the delay is relative to."
            ],

            'delay_count' => [
                'type'              => 'integer',
                'description'       => "Number of days before reaching the deadline."
            ],

            'is_active' => [
                'type'              => 'boolean',
                'description'       => "Can these terms be used?",
                'default'           => true
            ]

        ];
    }

}