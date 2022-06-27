<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace sale\booking;

class Invoice extends \finance\accounting\Invoice {
    
    public static function getColumns() {

        return [

            'booking_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\booking\Booking',
                'description'       => 'Booking the invoice relates to.',
                'required'          => true
            ],

            'funding_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\booking\Funding',
                'description'       => 'The funding the invoice originates from, if any.'
            ],

            'unit_price' => [
                'type'              => 'float',
                'usage'             => 'amount/money:4',
                'description'       => 'Unit price of the product related to the line (from booking line).'
            ],

            'vat_rate' => [
                'type'              => 'float',
                'usage'             => 'amount/rate',
                'description'       => 'VAT rate to be applied (from booking line).',
                'default'           => 0.0
            ]

        ];
    }

}