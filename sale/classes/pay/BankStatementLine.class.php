<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace sale\pay;
use equal\orm\Model;

class BankStatementLine extends Model {

    public static function getColumns() {

        return [

            'bank_statement_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\pay\BankStatement',
                'description'       => 'The bank statement the line relates to.'
            ],

            'payments_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'sale\pay\Payment',
                'foreign_field'     => 'statement_line_id',
                'description'       => 'The list of payments this line relates to .',
                'onchange'          => 'sale\pay\BankStatementLine::onchangePaymentsIds',
                'ondetach'          => 'delete'
            ],

            'date' => [
                'type'              => 'datetime',
                'description'       => 'Date at which the statement was issued.',
                'readonly'          => true
            ],

            'message' => [
                'type'              => 'string',
                'description'       => 'Message from the payer (or ref from the bank).',
                'readonly'          => true
            ],

            'structured_message' => [
                'type'              => 'string',
                'description'       => 'Structured message, if any.',
                'readonly'          => true
            ],

            'customer_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'identity\Partner',
                'domain'            => ['relationship', '=', 'customer'],
                'description'       => 'The customer the payment relates to, if known.',
                'readonly'          => true
            ],

            'amount' => [
                'type'              => 'float',
                'usage'             => 'amount/money',
                'description'       => 'Amount of the transaction.',
                'readonly'          => true
            ],

            'account_iban' => [
                'type'              => 'string',
                'usage'             => 'uri/urn:iban',
                'description'       => 'IBAN which the payment originates from.',
                'readonly'          => true
            ],

            'account_holder' => [
                'type'              => 'string',
                'description'       => 'Name of the Person whom the payment originates.',
                'readonly'          => true
            ],

            'status' => [
                'type'              => 'string',
                'selection'         => [
                    'pending',              // requires a review
                    'ignored',              // has been processed but does not relate to a booking
                    'reconciled'            // has been processed and assigned to a payment
                ],
                'description'       => 'Status of the line.',
                'default'           => 'pending',
                'readonly'          => true,
                'onchange'          => 'sale\pay\BankStatementLine::onchangeStatus',
            ]

        ];
    }

    /**
     * Update status according to the payments attached to the line.
     * Line is reconciled if its amount matches the sum of its payments.
     *
     */
    public static function onchangePaymentsIds($om, $oids, $lang) {
        $lines = $om->read(__CLASS__, $oids, ['amount', 'payments_ids.amount']);

        if($lines > 0) {
            foreach($lines as $lid => $line) {
                $sum = 0;
                $payments = $line['payments_ids.amount'];
                foreach($payments as $pid => $payment) {
                    $sum += $payment['amount'];
                }
                $status = 'pending';
                if($sum == $line['amount']) {
                    $status = 'reconciled';
                }
                $om->write(__CLASS__, $lid, ['status' => $status]);
            }
        }
    }

    public static function onchangeStatus($om, $oids, $lang) {
        $lines = $om->read(__CLASS__, $oids, ['status', 'bank_statement_id']);

        if($lines > 0) {
            $bank_statements_ids = [];
            foreach($lines as $lid => $line) {
                if($line['status'] == 'reconciled') {
                    $bank_statements_ids[$line['bank_statement_id']] = true;
                }
            }
            $bank_statements_ids = array_keys($bank_statements_ids);
            $om->write('sale\pay\BankStatement', $bank_statements_ids, ['status' => null]);
            // force immediate re-computing
            $om->read('sale\pay\BankStatement', $bank_statements_ids, ['status']);
        }
    }


}