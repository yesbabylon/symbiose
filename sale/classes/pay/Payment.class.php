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

            'customer_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'identity\Partner',
                'domain'            => ['relationship', '=', 'customer'],
                'description'       => 'The customer the payment relates to.',
            ],

            'amount' => [
                'type'              => 'float',
                'usage'             => 'amount/money',
                'description'       => 'Reference from the bank.'
            ],

            'communication' => [
                'type'              => 'string',
                'description'       => "Message from the payer.",
            ],

            'receipt_date' => [
                'type'              => 'datetime',
                'description'       => "Time of reception of the payment.",
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

            'fundings_ids' => [ 
                'type'              => 'many2many',
                'foreign_object'    => 'sale\pay\Funding',
                'foreign_field'     => 'payments_ids',
                'rel_table'         => 'sale_pay_rel_payment_funding',
                'rel_foreign_key'   => 'funding_id',
                'rel_local_key'     => 'payment_id'
            ]            

        ];
    }

}