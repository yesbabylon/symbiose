<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2026
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

namespace finance\bank;

use finance\accounting\AccountingEntry;
use finance\accounting\FiscalPeriod;
use finance\accounting\FiscalYear;
use finance\accounting\Journal;
use finance\accounting\operation\AccountingOperation;
use finance\accounting\operation\AccountingOperationLine;
use sale\pay\Payment;

class BankStatementLine extends AccountingOperation {

    public static function getName() {
        return 'Bank statement line';
    }

    public static function getDescription() {
        return 'A bank statement line represents one financial transaction on a bank account. It is a part of a bank statement.';
    }

    public static function getColumns() {
        return [
            'organization_id' => [
                'type'           => 'computed',
                'result_type'    => 'many2one',
                'foreign_object' => 'identity\Organization',
                'description'    => 'Organization inherited from the bank statement.',
                'relation'       => ['bank_statement_id' => 'organization_id'],
                'store'          => true,
                'instant'        => true,
                'readonly'       => true,
                'dependents'     => ['journal_id', 'fiscal_year_id']
            ],
            'operation_type' => [
                'type'        => 'string',
                'description' => 'Semantic type of the accounting operation.',
                'selection'   => ['bank_statement'],
                'default'     => 'bank_statement',
                'required'    => true,
                'readonly'    => true
            ],
            'posting_date' => [
                'type'        => 'computed',
                'result_type' => 'date',
                'description' => 'Accounting date inherited from the transaction date.',
                'relation'    => ['date'],
                'store'       => true,
                'instant'     => true,
                'readonly'    => true,
                'dependents'  => ['fiscal_year', 'fiscal_year_id', 'fiscal_period_id']
            ],
            'journal_id' => [
                'type'           => 'computed',
                'result_type'    => 'many2one',
                'foreign_object' => 'finance\accounting\Journal',
                'description'    => 'Bank journal used to post the statement line.',
                'function'       => 'calcJournalId',
                'store'          => true,
                'instant'        => true,
                'readonly'       => true
            ],
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
                'type'              => 'date',
                'description'       => 'Value date of the transaction.',
                'required'          => true,
                'readonly'          => true,
                'dependents'        => ['name', 'posting_date', 'fiscal_year_id', 'fiscal_period_id']
            ],
            'fiscal_year_id' => [
                'type'           => 'computed',
                'result_type'    => 'many2one',
                'foreign_object' => 'finance\accounting\FiscalYear',
                'description'    => 'Fiscal year containing the transaction date.',
                'function'       => 'calcFiscalYearId',
                'store'          => true,
                'instant'        => true,
                'readonly'       => true,
                'dependents'     => ['fiscal_period_id']
            ],
            'fiscal_period_id' => [
                'type'           => 'computed',
                'result_type'    => 'many2one',
                'foreign_object' => 'finance\accounting\FiscalPeriod',
                'description'    => 'Fiscal period containing the transaction date.',
                'function'       => 'calcFiscalPeriodId',
                'store'          => true,
                'instant'        => true,
                'readonly'       => true
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
            'accounting_account_id' => [
                'type'           => 'many2one',
                'foreign_object' => 'finance\accounting\Account',
                'description'    => 'Counterpart accounting account used for the bank movement.',
                'domain'         => ['is_control_account', '=', false],
                'ondelete'       => 'null'
            ],
            'accounting_account_code' => [
                'type'        => 'computed',
                'result_type' => 'string',
                'description' => 'Code of the counterpart accounting account.',
                'relation'    => ['accounting_account_id' => 'code'],
                'store'       => true,
                'instant'     => true,
                'readonly'    => true
            ],
            'accounting_entries_ids' => [
                'type'           => 'one2many',
                'foreign_object' => 'finance\accounting\AccountingEntry',
                'foreign_field'  => 'origin_object_id',
                'domain'         => [
                    'origin_object_class',
                    '=',
                    self::class
                ],
                'description' => 'Accounting entries generated from the bank statement line.',
                'dependents'  => ['is_balanced']
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
            'reconciliation_status' => [
                'type'              => 'string',
                'selection'         => ['pending', 'ignored', 'reconciled', 'to_refund'],
                'description'       => 'Current reconciliation status of the line.',
                'default'           => 'pending',
                'onupdate'          => 'onupdateReconciliationStatus'
            ]
        ];
    }

    public static function getActions(): array {
        return array_merge(parent::getActions(), [
            'attempt_reconcile' => [
                'description'   => 'Attempt to reconcile the line with suitable fundings.',
                'policies'      => ['editable'],
                'function'      => 'doAttemptReconcile'
            ],
            'assert_funding' => [
                'description'   => 'Ensure a funding exists for the line.',
                'policies'      => ['editable'],
                'function'      => 'doAssertFunding'
            ],
            'generate_accounting_entry' => [
                'description'   => 'Generate the accounting entry for the line.',
                'policies'      => ['is-valid'],
                'function'      => 'doGenerateAccountingEntry'
            ],
            'prepare_accounting_operation_lines' => [
                'description'   => 'Prepare the balanced accounting operation lines for the bank movement.',
                'policies'      => ['is-valid'],
                'function'      => 'doPrepareAccountingOperationLines'
            ],
            'assign_bank_operation_number' => [
                'description'   => 'Assign the definitive bank operation number.',
                'policies'      => ['is-valid'],
                'function'      => 'doAssignBankOperationNumber'
            ],
            'refresh_bank_statement_status' => [
                'description'   => 'Refresh the parent bank statement status.',
                'policies'      => ['has-bank-statement'],
                'function'      => 'doRefreshBankStatementStatus'
            ]
        ]);
    }

    public static function getPolicies(): array {
        return array_merge(parent::getPolicies(), [
            'editable' => [
                'description' => 'Check whether reconciliation data can still be updated.',
                'function'    => 'policyEditable'
            ],
            'has-bank-statement' => [
                'description' => 'Check whether the line belongs to a bank statement.',
                'function'    => 'policyHasBankStatement'
            ]
        ]);
    }

    public static function getWorkflow() {
        $workflow = parent::getWorkflow();
        $workflow['pending']['transitions']['submit']['onbefore'] = 'onbeforeSubmit';
        $workflow['proforma']['transitions']['post']['onafter'] = 'onafterAccountingStatusChange';
        $workflow['posted']['transitions']['cancel']['onafter'] = 'onafterAccountingStatusChange';
        return $workflow;
    }

    protected static function calcName($self): array {
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

    protected static function calcJournalId($self): array {
        $result = [];
        $self->read(['organization_id']);
        foreach($self as $id => $line) {
            if(!$line['organization_id']) {
                continue;
            }
            $journal = Journal::search([
                    ['organization_id', '=', $line['organization_id']],
                    ['journal_type', '=', 'BANK']
                ])
                ->read(['id'])
                ->first();
            if($journal) {
                $result[$id] = $journal['id'];
            }
        }
        return $result;
    }

    protected static function calcFiscalYearId($self): array {
        $result = [];
        $self->read(['organization_id', 'date']);
        foreach($self as $id => $line) {
            if(!$line['organization_id'] || !$line['date']) {
                continue;
            }
            $fiscal_year = FiscalYear::search([
                    ['organization_id', '=', $line['organization_id']],
                    ['date_from', '<=', $line['date']],
                    ['date_to', '>=', $line['date']]
                ])
                ->read(['id'])
                ->first();
            if($fiscal_year) {
                $result[$id] = $fiscal_year['id'];
            }
        }
        return $result;
    }

    protected static function calcFiscalPeriodId($self): array {
        $result = [];
        $self->read([
            'date',
            'fiscal_year_id' => [
                'fiscal_periods_ids' => ['date_from', 'date_to']
            ]
        ]);
        foreach($self as $id => $line) {
            if(!$line['date'] || !$line['fiscal_year_id']) {
                continue;
            }
            foreach($line['fiscal_year_id']['fiscal_periods_ids'] as $period) {
                if($line['date'] >= $period['date_from'] && $line['date'] <= $period['date_to']) {
                    $result[$id] = $period['id'];
                    break;
                }
            }
        }
        return $result;
    }

    protected static function onupdatePaymentsIds($om, $ids, $values, $lang) {
        $lines = $om->read(self::getType(), $ids, ['amount', 'payments_ids.amount']);
        if($lines > 0) {
            foreach($lines as $id => $line) {
                $sum = 0.0;
                foreach((array) $line['payments_ids.amount'] as $payment) {
                    $sum += $payment['amount'];
                }
                $status = abs(round($line['amount'] - $sum, 2)) < 0.01 ? 'reconciled' : 'pending';
                $om->update(self::getType(), $id, [
                    'reconciliation_status' => $status,
                    'remaining_amount'  => null,
                    'is_reconciled'     => null
                ]);
            }
        }
    }

    protected static function onupdateReconciliationStatus($om, $ids, $values, $lang) {
        $lines = $om->read(self::getType(), $ids, ['reconciliation_status', 'bank_statement_id', 'payments_ids.customer_id']);
        if($lines > 0) {
            $statement_ids = [];
            foreach($lines as $id => $line) {
                if($line['bank_statement_id']) {
                    $statement_ids[] = $line['bank_statement_id'];
                }
                if($line['reconciliation_status'] === 'reconciled' && count((array) $line['payments_ids.customer_id'])) {
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
        $self->read(['reconciliation_status', 'payments_ids' => ['amount'], 'amount']);
        foreach($self as $id => $line) {
            if($line['reconciliation_status'] === 'ignored') {
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

    protected static function doGenerateAccountingEntry($self) {
        $self
            ->do('prepare_accounting_operation_lines')
            ->do('generate_accounting_entries');
    }

    protected static function doPrepareAccountingOperationLines($self) {
        $self->read([
            'amount',
            'communication',
            'accounting_account_id',
            'operation_lines_ids',
            'accounting_entries_ids',
            'journal_id' => ['default_account_id']
        ]);

        foreach($self as $id => $line) {
            if(count($line['accounting_entries_ids']) > 0) {
                continue;
            }

            if(count($line['operation_lines_ids']) > 0) {
                AccountingOperationLine::ids($line['operation_lines_ids'])->delete(true);
            }

            $amount = round(abs((float) $line['amount']), 2);
            $is_incoming = (float) $line['amount'] > 0.0;
            $description = $line['communication'] ?: self::getName();

            AccountingOperationLine::create([
                'accounting_operation_id' => $id,
                'description'             => $description,
                'account_id'              => $line['journal_id']['default_account_id'],
                'debit'                   => $is_incoming ? $amount : 0.0,
                'credit'                  => $is_incoming ? 0.0 : $amount
            ]);
            AccountingOperationLine::create([
                'accounting_operation_id' => $id,
                'description'             => $description,
                'account_id'              => $line['accounting_account_id'],
                'debit'                   => $is_incoming ? 0.0 : $amount,
                'credit'                  => $is_incoming ? $amount : 0.0
            ]);
        }
    }

    protected static function doAssignBankOperationNumber($self, $orm) {
        $self->read([
            'operation_number',
            'posting_date',
            'sequence_number',
            'bank_statement_id' => ['id', 'statement_number']
        ]);

        foreach($self as $id => $line) {
            if($line['operation_number']) {
                continue;
            }
            $statement_number = $line['bank_statement_id']['statement_number']
                ?: $line['bank_statement_id']['id'];
            $orm->write(self::getType(), $id, [
                'operation_number' => sprintf(
                    'BANK/%s/%s/%03d',
                    date('Y', $line['posting_date']),
                    $statement_number,
                    $line['sequence_number'] ?: $id
                )
            ]);
        }
    }

    protected static function doReverse($self) {
        $self->read([
            'name',
            'organization_id',
            'journal_id',
            'accounting_entries_ids' => ['id'],
            'operation_lines_ids' => [
                'description',
                'account_id',
                'debit',
                'credit',
                'vat_rate'
            ]
        ]);

        foreach($self as $line) {
            $reversal = AccountingOperation::create([
                    'description'     => 'Reversal of ' . $line['name'],
                    'operation_type'  => 'misc',
                    'organization_id' => $line['organization_id'],
                    'journal_id'      => $line['journal_id'],
                    'posting_date'    => time(),
                    'status'          => 'pending',
                    'reversal_of_id'  => $line['id']
                ])
                ->read(['id'])
                ->first();

            foreach($line['operation_lines_ids'] as $operation_line) {
                AccountingOperationLine::create([
                    'accounting_operation_id' => $reversal['id'],
                    'description'             => $operation_line['description'],
                    'account_id'              => $operation_line['account_id'],
                    'debit'                   => $operation_line['credit'],
                    'credit'                  => $operation_line['debit'],
                    'vat_rate'                => $operation_line['vat_rate']
                ]);
            }

            AccountingOperation::id($reversal['id'])
                ->transition('submit')
                ->transition('post');

            $entry_ids = $line['accounting_entries_ids']->ids();
            if(count($entry_ids) > 0) {
                AccountingEntry::ids($entry_ids)->update(['status' => 'reversed']);
            }
        }
    }

    protected static function onbeforePost($self) {
        parent::onbeforePost($self);
        $self->do('assign_bank_operation_number');
    }

    protected static function onbeforeSubmit($self) {
        $self->do('prepare_accounting_operation_lines');
    }

    protected static function onafterAccountingStatusChange($self) {
        $self->do('refresh_bank_statement_status');
    }

    protected static function policyEditable($self): array {
        $result = [];
        $self->read(['status']);
        foreach($self as $id => $line) {
            if($line['status'] !== 'pending') {
                $result[$id] = [
                    'non_editable' => 'Only pending bank statement lines can be reconciled.'
                ];
            }
        }
        return $result;
    }

    protected static function policyHasBankStatement($self): array {
        $result = [];
        $self->read(['bank_statement_id']);
        foreach($self as $id => $line) {
            if(!$line['bank_statement_id']) {
                $result[$id] = [
                    'missing_bank_statement' => 'Bank statement is required.'
                ];
            }
        }
        return $result;
    }

    protected static function policyIsValid($self): array {
        $result = [];
        $self->read([
            'status',
            'organization_id',
            'posting_date',
            'amount',
            'reconciliation_status',
            'accounting_account_id' => ['is_control_account'],
            'journal_id' => [
                'organization_id',
                'default_account_id' => ['is_control_account']
            ],
            'fiscal_year_id' => ['status'],
            'fiscal_period_id' => ['status'],
            'bank_statement_id' => ['is_balanced']
        ]);

        foreach($self as $id => $line) {
            $errors = [];
            if(!in_array($line['status'], ['pending', 'proforma'], true)) {
                $errors['invalid_status'] = 'Only pending or proforma bank statement lines can be posted.';
            }
            if(!$line['organization_id']) {
                $errors['missing_organisation'] = 'Organization is required.';
            }
            if(!$line['posting_date']) {
                $errors['missing_posting_date'] = 'Posting date is required.';
            }
            if(abs(round((float) $line['amount'], 2)) < 0.01) {
                $errors['invalid_amount'] = 'A non-zero amount is required.';
            }
            if($line['reconciliation_status'] !== 'reconciled') {
                $errors['invalid_reconciliation_status'] = 'Only reconciled bank statement lines can be posted.';
            }
            if(!$line['accounting_account_id']) {
                $errors['missing_accounting_account'] = 'A counterpart accounting account is required.';
            }
            elseif($line['accounting_account_id']['is_control_account']) {
                $errors['invalid_accounting_account'] = 'The counterpart account cannot be a control account.';
            }
            if(!$line['journal_id']) {
                $errors['missing_journal'] = 'A bank journal is required.';
            }
            else {
                if($line['journal_id']['organization_id'] !== $line['organization_id']) {
                    $errors['invalid_journal'] = 'The bank journal belongs to another organization.';
                }
                if(!$line['journal_id']['default_account_id']) {
                    $errors['missing_bank_account'] = 'The bank journal requires a default accounting account.';
                }
                elseif($line['journal_id']['default_account_id']['is_control_account']) {
                    $errors['invalid_bank_account'] = 'The bank journal default account cannot be a control account.';
                }
            }
            if(!$line['fiscal_year_id'] || !in_array($line['fiscal_year_id']['status'], ['preopen', 'open', 'preclosed'], true)) {
                $errors['invalid_fiscal_year'] = 'Fiscal year must accept accounting entries.';
            }
            if(!$line['fiscal_period_id'] || !in_array($line['fiscal_period_id']['status'], ['open', 'preclosed'], true)) {
                $errors['invalid_fiscal_period'] = 'Fiscal period must accept accounting entries.';
            }
            if(!$line['bank_statement_id'] || !$line['bank_statement_id']['is_balanced']) {
                $errors['unbalanced_bank_statement'] = 'The bank statement must be balanced before posting its lines.';
            }
            if(count($errors) > 0) {
                $result[$id] = $errors;
            }
        }
        return $result;
    }

    public static function canupdate($self, $values) {
        if(isset($values['payments_ids'])) {
            $new_payment_ids = array_map(static fn($id) => abs($id), $values['payments_ids']);
            $new_payments = Payment::ids($new_payment_ids)->read(['amount']);
            $map_payment_amounts = [];
            foreach($new_payments as $payment_id => $payment) {
                $map_payment_amounts[$payment_id] = (float) $payment['amount'];
            }
            $payments_diff = 0.0;
            foreach(array_unique($values['payments_ids']) as $payment_id) {
                if($payment_id < 0) {
                    $payments_diff -= $map_payment_amounts[abs($payment_id)] ?? 0.0;
                }
                else {
                    $payments_diff += $map_payment_amounts[$payment_id] ?? 0.0;
                }
            }
            $self->read(['payments_ids' => ['amount'], 'amount']);
            foreach($self as $line) {
                $payments_sum = 0.0;
                foreach($line['payments_ids'] as $payment) {
                    $payments_sum += (float) $payment['amount'];
                }
                if(abs($payments_sum + $payments_diff) > abs($line['amount'])) {
                    return ['amount' => ['exceeded_price' => 'Sum of the payments cannot be higher than the line total.']];
                }
            }
        }
        return parent::canupdate($self, $values);
    }
}
