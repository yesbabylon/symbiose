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
                'onupdate'          => 'onupdatePaymentsIds',
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

            'is_suspense' => [
                'type'              => 'boolean',
                'description'       => 'Origin is unknown (or unsure) and line has been put on suspense account.',
                'default'           => false
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
                'onupdate'          => 'onupdateStatus',
            ]

        ];
    }

    /**
     * Update status according to the payments attached to the line.
     * Line is reconciled if its amount matches the sum of its payments.
     *
     */
    public static function onupdatePaymentsIds($om, $oids, $values, $lang) {
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

    public static function onupdateStatus($om, $oids, $values, $lang) {
        trigger_error("QN_DEBUG_ORM::calling sale\pay\BankStatementLine::onupdateStatus", QN_REPORT_DEBUG);

        $lines = $om->read(get_called_class(), $oids, ['status', 'bank_statement_id', 'payments_ids.partner_id']);

        if($lines > 0) {
            $bank_statements_ids = [];
            foreach($lines as $lid => $line) {
                if($line['status'] == 'reconciled') {
                    // mark related statement for re-computing
                    $bank_statements_ids[$line['bank_statement_id']] = true;
                    // resolve customer_id: retrieve first payment
                    if(isset($line['payments_ids.partner_id']) && count($line['payments_ids.partner_id'])) {
                        $payment = reset($line['payments_ids.partner_id']);
                        $om->write(get_called_class(), $lid, ['customer_id' => $payment['partner_id']]);
                    }
                }
            }
            $bank_statements_ids = array_keys($bank_statements_ids);
            $om->write('sale\pay\BankStatement', $bank_statements_ids, ['status' => null]);
            // force immediate re-computing
            $om->read('sale\pay\BankStatement', $bank_statements_ids, ['status']);
        }
    }


}