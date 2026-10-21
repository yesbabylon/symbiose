<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2026
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

namespace finance\bank;

use equal\orm\Model;
use sale\pay\Payment;

class BankStatementLine extends Model {

    public static function getName() {
        return 'Bank statement line';
    }

    public static function getDescription() {
        return 'A bank statement line represents one financial transaction on a bank account. It is a part of a bank statement.';
    }

    public static function getColumns() {
        return [
            'name' => [
                'type'              => 'computed',
                'result_type'       => 'string',
                'function'          => 'calcName',
                'description'       => 'Display name of the bank statement line.',
                'store'             => true
            ],
            'description' => [
                'type'              => 'alias',
                'alias'             => 'communication'
            ],
            'sequence_number' => [
                'type'              => 'integer',
                'description'       => 'Sequence number of the line within its statement.',
                'default'           => 'defaultSequenceNumber'
            ],
            'bank_statement_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'finance\bank\BankStatement',
                'description'       => 'The bank statement the line relates to.',
                'required'          => true,
                'ondelete'          => 'cascade'
            ],
            'payments_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'sale\pay\Payment',
                'foreign_field'     => 'statement_line_id',
                'description'       => 'The list of payments this line relates to.',
                'onupdate'          => 'onupdatePaymentsIds',
                'ondetach'          => 'delete'
            ],
            'date' => [
                'type'              => 'datetime',
                'description'       => 'Value date of the transaction.',
                'required'          => true,
                'readonly'          => true
            ],
            'communication' => [
                'type'              => 'string',
                'usage'             => 'text/plain:255',
                'description'       => 'Communication associated with the transaction.'
            ],
            'communication_type' => [
                'type'              => 'string',
                'selection'         => ['free', 'RF', 'SCOR', 'VCS'],
                'description'       => 'Type of transaction communication.',
                'default'           => 'free'
            ],
            'mandate_identifier' => [
                'type'              => 'string',
                'description'       => 'Mandate identifier for a SEPA direct debit.'
            ],
            // Legacy communication fields kept for existing views and integrations.
            'message' => [
                'type'              => 'string',
                'description'       => 'Message from the payer or reference from the bank.',
                'readonly'          => true
            ],
            'structured_message' => [
                'type'              => 'string',
                'description'       => 'Structured message, if any.',
                'readonly'          => true
            ],
            'customer_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\customer\Customer',
                'description'       => 'The customer the payment relates to, if known.',
                'readonly'          => true
            ],
            'amount' => [
                'type'              => 'float',
                'usage'             => 'amount/money:2',
                'description'       => 'Amount of the transaction.',
                'required'          => true,
                'readonly'          => true
            ],
            'amount_currency' => [
                'type'              => 'string',
                'description'       => 'Currency of the transaction.',
                'default'           => 'EUR'
            ],
            'transaction_type' => [
                'type'              => 'string',
                'selection'         => [
                    'transfer', 'transfer_out', 'transfer_fee', 'transfer_in', 'transfer_rejected',
                    'transfer_fee_reimbursement', 'cheque', 'cheque_payment', 'voucher_payment',
                    'store_cheque', 'bank_cheque_issue', 'certified_cheque', 'cheque_fee',
                    'cheque_rejected', 'cheque_credit_provisional', 'cheque_credit', 'cheque_reversal',
                    'cheque_fee_reimbursement', 'card', 'card_payment', 'card_withdrawal', 'card_fee',
                    'card_payment_received', 'card_deposit', 'card_fee_reimbursement', 'direct_debit',
                    'direct_debit_out', 'direct_debit_rejected', 'direct_debit_refund', 'direct_debit_fee',
                    'direct_debit_in', 'direct_debit_credit_provisional', 'direct_debit_reversal',
                    'direct_debit_fee_reimbursement', 'bill_of_exchange', 'bill_payment', 'bill_rejected',
                    'bill_fee', 'bill_credit', 'bill_credit_provisional', 'bill_discount',
                    'bill_fee_reimbursement', 'cash', 'cash_withdrawal', 'cash_fee', 'cash_deposit',
                    'cash_fee_reimbursement', 'securities', 'securities_purchase', 'securities_fee',
                    'securities_sale', 'securities_income', 'securities_fee_reimbursement', 'loan',
                    'loan_repayment', 'loan_fee', 'loan_disbursement', 'loan_fee_reimbursement', 'misc',
                    'fx_purchase', 'term_deposit', 'misc_fee', 'fx_sale', 'misc_fee_reimbursement',
                    'account_settlement', 'account_settlement_out', 'account_settlement_fee',
                    'account_settlement_in', 'account_settlement_fee_reimbursement', 'bank_fee',
                    'bank_fee_correction', 'bank_fee_reimbursement'
                ],
                'description'       => 'Normalized type of the transaction.',
                'default'           => 'transfer'
            ],
            'remaining_amount' => [
                'type'              => 'computed',
                'result_type'       => 'float',
                'usage'             => 'amount/money:2',
                'description'       => 'Amount that still needs to be assigned to payments.',
                'function'          => 'calcRemainingAmount',
                'store'             => true
            ],
            'is_reconciled' => [
                'type'              => 'computed',
                'result_type'       => 'boolean',
                'description'       => 'Whether the sum of payments matches the transaction amount.',
                'function'          => 'calcIsReconciled',
                'store'             => true
            ],
            'account_iban' => [
                'type'              => 'string',
                'usage'             => 'uri/urn.iban',
                'description'       => 'Counterparty IBAN, if any.',
                'readonly'          => true
            ],
            'account_suffix' => [
                'type'              => 'string',
                'description'       => 'Proprietary or extended counterparty account identifier.'
            ],
            'account_bic' => [
                'type'              => 'string',
                'description'       => 'Counterparty BIC, if any.'
            ],
            'account_holder' => [
                'type'              => 'string',
                'description'       => 'Name of the transaction originator.',
                'readonly'          => true
            ],
            'logs' => [
                'type'              => 'string',
                'usage'             => 'text/plain',
                'description'       => 'Logs produced by reconciliation and future generation actions.'
            ],
            'is_suspense' => [
                'type'              => 'boolean',
                'description'       => 'Whether the line is assigned to a suspense account.',
                'default'           => false
            ],
            'status' => [
                'type'              => 'string',
                'selection'         => ['pending', 'ignored', 'reconciled', 'to_refund'],
                'description'       => 'Current reconciliation status of the line.',
                'default'           => 'pending',
                'onupdate'          => 'onupdateStatus'
            ]
        ];
    }

    public static function getActions(): array {
        return array_merge(parent::getActions(), [
            'attempt_reconcile' => [
                'description'   => 'Attempt to reconcile the line with suitable fundings.',
                'function'      => 'doAttemptReconcile'
            ],
            'assert_funding' => [
                'description'   => 'Ensure a funding exists for the line.',
                'function'      => 'doAssertFunding'
            ],
            'generate_accounting_entry' => [
                'description'   => 'Generate the accounting entry for the line.',
                'function'      => 'doGenerateAccountingEntry'
            ],
            'refresh_bank_statement_status' => [
                'description'   => 'Refresh the parent bank statement status.',
                'function'      => 'doRefreshBankStatementStatus'
            ]
        ]);
    }

    protected static function calcName($self) {
        $result = [];
        $self->read(['date', 'sequence_number', 'communication']);
        foreach($self as $id => $line) {
            $result[$id] = trim(sprintf('%s #%s %s', date('Y-m-d', $line['date']), $line['sequence_number'], $line['communication']));
        }
        return $result;
    }

    protected static function defaultSequenceNumber($values) {
        if(!isset($values['bank_statement_id'])) {
            return null;
        }
        $statement = BankStatement::id($values['bank_statement_id'])
            ->read(['statement_lines_ids' => ['@domain' => ['state', '=', 'instance']]])
            ->first();
        return $statement ? count($statement['statement_lines_ids']) + 1 : 1;
    }

    public static function onupdatePaymentsIds($om, $ids, $values, $lang) {
        $lines = $om->read(self::getType(), $ids, ['amount', 'payments_ids.amount']);
        if($lines > 0) {
            foreach($lines as $id => $line) {
                $sum = 0.0;
                foreach((array) $line['payments_ids.amount'] as $payment) {
                    $sum += $payment['amount'];
                }
                $status = abs(round($line['amount'] - $sum, 2)) < 0.01 ? 'reconciled' : 'pending';
                $om->update(self::getType(), $id, [
                    'status'            => $status,
                    'remaining_amount'  => null,
                    'is_reconciled'     => null
                ]);
            }
        }
    }

    public static function onupdateStatus($om, $ids, $values, $lang) {
        $lines = $om->read(self::getType(), $ids, ['status', 'bank_statement_id', 'payments_ids.customer_id']);
        if($lines > 0) {
            $statement_ids = [];
            foreach($lines as $id => $line) {
                if($line['bank_statement_id']) {
                    $statement_ids[] = $line['bank_statement_id'];
                }
                if($line['status'] === 'reconciled' && count((array) $line['payments_ids.customer_id'])) {
                    $payment = reset($line['payments_ids.customer_id']);
                    $om->update(self::getType(), $id, ['customer_id' => $payment['customer_id']]);
                }
            }
            $statement_ids = array_values(array_unique($statement_ids));
            if(count($statement_ids)) {
                $om->update('finance\bank\BankStatement', $statement_ids, ['is_reconciled' => null, 'status' => null]);
                $om->read('finance\bank\BankStatement', $statement_ids, ['is_reconciled', 'status']);
            }
        }
    }

    protected static function calcRemainingAmount($self) {
        $result = [];
        $self->read(['payments_ids' => ['amount'], 'amount']);
        foreach($self as $id => $line) {
            $sum = 0.0;
            foreach($line['payments_ids'] as $payment) {
                $sum += $payment['amount'];
            }
            $result[$id] = round($line['amount'] - $sum, 2);
        }
        return $result;
    }

    protected static function calcIsReconciled($self) {
        $result = [];
        $self->read(['status', 'payments_ids' => ['amount'], 'amount']);
        foreach($self as $id => $line) {
            if($line['status'] === 'ignored') {
                $result[$id] = true;
                continue;
            }
            $sum = 0.0;
            foreach($line['payments_ids'] as $payment) {
                $sum += $payment['amount'];
            }
            $result[$id] = count($line['payments_ids']) > 0
                && abs(round($line['amount'] - $sum, 2)) < 0.01;
        }
        return $result;
    }

    protected static function doRefreshBankStatementStatus($self) {
        $self->read(['bank_statement_id']);
        foreach($self as $line) {
            if($line['bank_statement_id']) {
                BankStatement::id($line['bank_statement_id'])->do('refresh_status');
            }
        }
    }

    /** Future Funding/FundingAllocation reconciliation hook. */
    protected static function doAttemptReconcile($self) {
    }

    /** Future creation of an unexpected Funding for the line. */
    protected static function doAssertFunding($self) {
    }

    /** Future Funding creation hook kept compatible with the FMT model. */
    protected static function doCreateFundings($self) {
    }

    /** Future accounting entry generation hook. */
    protected static function doGenerateAccountingEntry($self) {
    }

    public static function canupdate($orm, $ids, $values, $lang) {
        if(isset($values['payments_ids'])) {
            $new_payment_ids = array_map(static function($id) {
                return abs($id);
            }, $values['payments_ids']);
            $new_payments = $orm->read(Payment::getType(), $new_payment_ids, ['amount'], $lang);
            $payments_diff = 0.0;
            foreach(array_unique($values['payments_ids']) as $payment_id) {
                if($payment_id < 0) {
                    $payments_diff -= $new_payments[abs($payment_id)]['amount'];
                }
                else {
                    $payments_diff += $new_payments[$payment_id]['amount'];
                }
            }
            $lines = $orm->read(self::getType(), $ids, ['payments_ids', 'amount'], $lang);
            if($lines > 0) {
                foreach($lines as $line) {
                    $payments = $orm->read(Payment::getType(), $line['payments_ids'], ['amount'], $lang);
                    $payments_sum = 0.0;
                    foreach($payments as $payment) {
                        $payments_sum += $payment['amount'];
                    }
                    if(abs($payments_sum + $payments_diff) > abs($line['amount'])) {
                        return ['amount' => ['exceeded_price' => 'Sum of the payments cannot be higher than the line total.']];
                    }
                }
            }
        }
        return parent::canupdate($orm, $ids, $values, $lang);
    }
}
