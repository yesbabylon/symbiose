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
                'foreign_object'    => Payment::getType(),
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

            'remaining_amount' => [
                'type'              => 'computed',
                'result_type'       => 'float',
                'usage'             => 'amount/money',
                'description'       => 'Amount that still needs to be assigned to payments.',
                'help'              => 'This value is meant to be used in tests when payments are involved, to make sure the sum of payments never exceeds the amount of the line.',
                'function'          => 'calcRemainingAmount',
                'store'             => false
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
                    'ignored',              // has been manually processed but does not relate to a booking
                    'reconciled',           // has been processed and assigned to a payment
                    'to_refund'             // has been processed and refers to a payment already received
                ],
                'description'       => 'Status of the line.',
                'default'           => 'pending',
                'onupdate'          => 'onupdateStatus',
            ]

        ];
    }

    /**
     * Update status according to the payments attached to the line.
     * Line is considered 'reconciled' if its amount matches the sum of its payments.
     *
     */
    public static function onupdatePaymentsIds($om, $oids, $values, $lang) {
        $lines = $om->read(self::getType(), $oids, ['amount', 'payments_ids.amount']);

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
                $om->update(self::getType(), $lid, ['status' => $status, 'remaining_amount' => null]);
            }
        }
    }

    public static function onupdateStatus($om, $oids, $values, $lang) {
        trigger_error("ORM::calling sale\pay\BankStatementLine::onupdateStatus", QN_REPORT_DEBUG);

        $lines = $om->read(self::getType(), $oids, ['status', 'bank_statement_id', 'payments_ids.partner_id']);

        if($lines > 0) {
            $bank_statements_ids = [];
            foreach($lines as $lid => $line) {
                // mark related statement for re-computing
                $bank_statements_ids[] = $line['bank_statement_id'];
                if($line['status'] == 'reconciled') {
                    // resolve customer_id: retrieve first payment
                    if(isset($line['payments_ids.partner_id']) && count($line['payments_ids.partner_id'])) {
                        $payment = reset($line['payments_ids.partner_id']);
                        $om->update(self::getType(), $lid, ['customer_id' => $payment['partner_id']]);
                    }
                }
            }
            $om->update('sale\pay\BankStatement', $bank_statements_ids, ['status' => null]);
            // force immediate re-computing
            $om->read('sale\pay\BankStatement', $bank_statements_ids, ['status']);
        }
    }

    public static function calcRemainingAmount($om, $oids, $lang) {
        $result = [];
        $lines = $om->read(self::getType(), $oids, ['payments_ids', 'amount'], $lang);
        if($lines > 0) {
            foreach($lines as $lid => $line) {
                $sum = 0.0;
                $payments = $om->read(Payment::getType(), $line['payments_ids'], ['amount'], $lang);
                if($payments > 0 && count($payments)) {
                    foreach($payments as $pid => $payment) {
                        $sum += $payment['amount'];
                    }
                }
                $result[$lid] = $line['amount'] - $sum;
            }
        }
        return $result;
    }

   /**
     * Check wether an object can be updated.
     * These tests come in addition to the unique constraints return by method `getUnique()`.
     * Checks wheter the sum of the fundings of each booking remains lower than the price of the booking itself.
     *
     * @param  \equal\orm\ObjectManager     $om         ObjectManager instance.
     * @param  array                        $oids       List of objects identifiers.
     * @param  array                        $values     Associative array holding the new values to be assigned.
     * @param  string                       $lang       Language in which multilang fields are being updated.
     * @return array            Returns an associative array mapping fields with their error messages. An empty array means that object has been successfully processed and can be updated.
     */
    public static function canupdate($om, $ids, $values, $lang) {
        if(isset($values['payments_ids'])) {
            $new_payments_ids = array_map(function ($a) {return abs($a);}, $values['payments_ids']);
            $new_payments = $om->read(Payment::getType(), $new_payments_ids, ['amount'], $lang);

            $new_payments_diff = 0.0;
            foreach(array_unique($values['payments_ids']) as $pid) {
                if($pid < 0) {
                    $new_payments_diff -= $new_payments[abs($pid)]['amount'];
                }
            }

            $lines = $om->read(self::getType(), $ids, ['payments_ids', 'amount', 'remaining_amount'], $lang);

            if($lines > 0) {
                foreach($lines as $lid => $line) {
                    $payments = $om->read(Payment::getType(), $line['payments_ids'], ['amount'], $lang);
                    $payments_sum = 0;
                    foreach($payments as $pid => $payment) {
                        $payments_sum += $payment['amount'];
                    }

                    if(abs($payments_sum+$new_payments_diff) > abs($line['amount'])) {
                        return ['amount' => ['exceeded_price' => "Sum of the payments cannot be higher than the line total."]];
                    }
                }
            }
            return parent::canupdate($om, $ids, $values, $lang);
        }
    }

}