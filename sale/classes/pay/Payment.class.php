<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace sale\pay;
use equal\orm\Model;

class Payment extends Model {

    public static function getColumns() {

        return [

            'partner_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'identity\Partner',
                'description'       => "The partner to whom the booking relates.",
                'required'          => true
            ],

            'amount' => [
                'type'              => 'float',
                'usage'             => 'amount/money:2',
                'description'       => 'Reference from the bank.'
            ],

            'communication' => [
                'type'              => 'string',
                'description'       => "Message from the payer.",
            ],

            'receipt_date' => [
                'type'              => 'datetime',
                'description'       => "Time of reception of the payment.",
                'default'           => time()
            ],

            'payment_method' => [
                'type'              => 'string',
                'selection'         => ['voucher','cashdesk','bank'],
                'description'       => "The method used for payment."
            ],

            'payment_origin' => [
                'type'              => 'string',
                'selection'         => ['cash','bank'],
                'description'       => "Origin of the received money.",
                'visible'           => [ ['payment_method', '=', 'cashdesk'] ]
            ],

            'operation_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\pos\Operation',
                'description'       => 'The operation the payment relates to.',
                'visible'           => [ ['payment_method', '=', 'cashdesk'] ]
            ],

            'statement_line_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\pay\BankStatementLine',
                'description'       => 'The bank statement line the payment relates to.',
                'visible'           => [ ['payment_method', '=', 'bank'] ]
            ],

            'voucher_ref' => [
                'type'              => 'string',
                'description'       => 'The reference of the voucher the payment relates to.',
                'visible'           => [ ['payment_method', '=', 'voucher'] ]
            ],

            'funding_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\pay\Funding',
                'description'       => 'The funding the payement relates to, if any.'
            ]

        ];
    }

}