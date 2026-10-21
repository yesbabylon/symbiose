<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2026
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

namespace finance\bank;

use equal\orm\Model;

class BankStatement extends Model {

    public static function getName() {
        return 'Bank statement';
    }

    public static function getDescription() {
        return 'A bank statement summarizes a bank account financial transactions over a period.'
            .' It includes details of deposits, withdrawals, fees, and the account balance.';
    }

    public static function getColumns() {
        return [
            'name' => [
                'type'              => 'computed',
                'result_type'       => 'string',
                'description'       => 'Display name of bank statement.',
                'function'          => 'calcName',
                'store'             => true
            ],
            'raw_data' => [
                'type'              => 'binary',
                'description'       => 'Original file used for creating the statement.'
            ],
            'document_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'document\document\Document',
                'description'       => 'Source document from which the statement was imported.'
            ],
            'bank_account_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'finance\bank\BankAccount',
                'description'       => 'Bank account to which the statement relates.'
            ],
            'statement_number' => [
                'type'              => 'string',
                'description'       => 'Sequence number assigned to the statement by the bank.'
            ],
            'statement_currency' => [
                'type'              => 'string',
                'description'       => 'Currency used for the statement amounts.',
                'default'           => 'EUR'
            ],
            'opening_date' => [
                'type'              => 'date',
                'description'       => 'Opening date of the statement period.'
            ],
            'closing_date' => [
                'type'              => 'computed',
                'result_type'       => 'date',
                'description'       => 'Closing date of the statement period.',
                'function'          => 'calcClosingDate',
                'store'             => true,
                'readonly'          => true
            ],
            'opening_balance' => [
                'type'              => 'computed',
                'result_type'       => 'float',
                'usage'             => 'amount/money:2',
                'description'       => 'Account balance before the transactions.',
                'function'          => 'calcOpeningBalance',
                'store'             => true,
                'readonly'          => true
            ],
            'closing_balance' => [
                'type'              => 'computed',
                'result_type'       => 'float',
                'usage'             => 'amount/money:2',
                'description'       => 'Account balance after the transactions.',
                'function'          => 'calcClosingBalance',
                'store'             => true,
                'readonly'          => true
            ],
            'balance_delta' => [
                'type'              => 'computed',
                'result_type'       => 'float',
                'usage'             => 'amount/money:2',
                'description'       => 'Difference between closing and opening balances.',
                'function'          => 'calcBalanceDelta',
                'store'             => false,
                'readonly'          => true
            ],
            'expected_opening_balance' => [
                'type'              => 'computed',
                'result_type'       => 'float',
                'usage'             => 'amount/money:2',
                'description'       => 'Expected opening balance based on the previous statement.',
                'function'          => 'calcExpectedOpeningBalance',
                'store'             => false,
                'readonly'          => true
            ],
            // Legacy closing date kept as the storage source used by existing views and integrations.
            'date' => [
                'type'              => 'date',
                'description'       => 'Date the statement was received.',
                'required'          => true,
                'readonly'          => true
            ],
            // Legacy balances kept as storage sources until existing views have migrated.
            'old_balance' => [
                'type'              => 'float',
                'usage'             => 'amount/money:2',
                'description'       => 'Account balance before the transactions.',
                'required'          => true,
                'readonly'          => true
            ],
            'new_balance' => [
                'type'              => 'float',
                'usage'             => 'amount/money:2',
                'description'       => 'Account balance after the transactions.',
                'required'          => true,
                'readonly'          => true
            ],
            'statement_lines_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'finance\bank\BankStatementLine',
                'foreign_field'     => 'bank_statement_id',
                'description'       => 'The lines that are assigned to the statement.'
            ],
            'is_reconciled' => [
                'type'              => 'computed',
                'result_type'       => 'boolean',
                'description'       => 'Whether all statement lines are reconciled or ignored.',
                'function'          => 'calcIsReconciled',
                'store'             => true
            ],
            'is_balanced' => [
                'type'              => 'computed',
                'result_type'       => 'boolean',
                'description'       => 'Whether the statement balance delta matches the sum of its lines.',
                'function'          => 'calcIsBalanced',
                'store'             => false
            ],
            'status' => [
                'type'              => 'computed',
                'result_type'       => 'string',
                'function'          => 'calcStatus',
                'selection'         => ['pending', 'reconciled'],
                'description'       => 'Status of the statement, depending on its lines.',
                'store'             => true
            ],
            'is_exported' => [
                'type'              => 'boolean',
                'description'       => 'Whether the statement was exported to an external tool.',
                'default'           => false
            ],
            // CODA statements can contain either an IBAN or a Belgian BBAN.
            'bank_account_number' => [
                'type'              => 'string',
                'description'       => 'Original number of the account as provided in the statement.'
            ],
            'bank_account_bic' => [
                'type'              => 'string',
                'description'       => 'Bank Identification Code of the account.'
            ],
            'bank_account_suffix' => [
                'type'              => 'string',
                'description'       => 'Proprietary or extended account identifier.'
            ],
            'bank_account_iban' => [
                'type'              => 'computed',
                'result_type'       => 'string',
                'usage'             => 'uri/urn.iban',
                'function'          => 'calcBankAccountIban',
                'description'       => 'Normalized IBAN representation of the account number.',
                'store'             => true
            ]
        ];
    }

    public static function getActions(): array {
        return array_merge(parent::getActions(), [
            'attempt_reconcile' => [
                'description'   => 'Attempt to reconcile all statement lines.',
                'function'      => 'doAttemptReconcile'
            ],
            'refresh_status' => [
                'description'   => 'Refresh reconciliation indicators and status.',
                'function'      => 'doRefreshStatus'
            ],
            'generate_fundings' => [
                'description'   => 'Generate fundings for this statement.',
                'function'      => 'doGenerateFundings'
            ],
            'generate_accounting_entries' => [
                'description'   => 'Generate accounting entries for this statement.',
                'function'      => 'doGenerateAccountingEntries'
            ]
        ]);
    }

    protected static function calcName($self) {
        $result = [];
        $self->read(['bank_account_iban', 'bank_account_number', 'statement_number', 'date', 'old_balance', 'new_balance']);
        foreach($self as $id => $statement) {
            $account = $statement['bank_account_iban'] ?: $statement['bank_account_number'];
            $number = $statement['statement_number'] ?: date('Ymd', $statement['date']);
            $result[$id] = sprintf('%s - %s - %.2f - %.2f', $account, $number, $statement['old_balance'], $statement['new_balance']);
        }
        return $result;
    }

    protected static function calcClosingDate($self) {
        $result = [];
        $self->read(['date']);
        foreach($self as $id => $statement) {
            $result[$id] = $statement['date'];
        }
        return $result;
    }

    protected static function calcOpeningBalance($self) {
        $result = [];
        $self->read(['old_balance']);
        foreach($self as $id => $statement) {
            $result[$id] = $statement['old_balance'];
        }
        return $result;
    }

    protected static function calcClosingBalance($self) {
        $result = [];
        $self->read(['new_balance']);
        foreach($self as $id => $statement) {
            $result[$id] = $statement['new_balance'];
        }
        return $result;
    }

    protected static function calcBalanceDelta($self) {
        $result = [];
        $self->read(['old_balance', 'new_balance']);
        foreach($self as $id => $statement) {
            $result[$id] = round($statement['new_balance'] - $statement['old_balance'], 2);
        }
        return $result;
    }

    protected static function calcExpectedOpeningBalance($self) {
        $result = [];
        $self->read(['bank_account_id', 'bank_account_iban', 'opening_date', 'date']);
        foreach($self as $id => $statement) {
            $result[$id] = null;
            $period_start = $statement['opening_date'] ?: $statement['date'];
            if(!$period_start) {
                continue;
            }
            $domain = [['id', '<>', $id], ['date', '<', $period_start]];
            if($statement['bank_account_id']) {
                $domain[] = ['bank_account_id', '=', $statement['bank_account_id']];
            }
            elseif($statement['bank_account_iban']) {
                $domain[] = ['bank_account_iban', '=', $statement['bank_account_iban']];
            }
            else {
                continue;
            }
            $previous = self::search($domain, ['sort' => ['date' => 'desc']])->read(['new_balance'])->first();
            if($previous) {
                $result[$id] = $previous['new_balance'];
            }
        }
        return $result;
    }

    protected static function calcBankAccountIban($self) {
        $result = [];
        $self->read(['bank_account_id' => ['bank_account_iban'], 'bank_account_number']);
        foreach($self as $id => $statement) {
            $account_number = $statement['bank_account_number'];
            if($statement['bank_account_id'] && $statement['bank_account_id']['bank_account_iban']) {
                $account_number = $statement['bank_account_id']['bank_account_iban'];
            }
            $result[$id] = self::convertBbanToIban($account_number);
        }
        return $result;
    }

    protected static function calcIsReconciled($self) {
        $result = [];
        $self->read(['statement_lines_ids' => ['status']]);
        foreach($self as $id => $statement) {
            $result[$id] = ($statement['statement_lines_ids']->count() > 0);
            foreach($statement['statement_lines_ids'] as $line) {
                if(!in_array($line['status'], ['reconciled', 'ignored'], true)) {
                    $result[$id] = false;
                    break;
                }
            }
        }
        return $result;
    }

    protected static function calcIsBalanced($self) {
        $result = [];
        $self->read(['old_balance', 'new_balance', 'statement_lines_ids' => ['amount']]);
        foreach($self as $id => $statement) {
            $sum = 0.0;
            foreach($statement['statement_lines_ids'] as $line) {
                $sum += round($line['amount'], 2);
            }
            $delta = round($statement['new_balance'], 2) - round($statement['old_balance'], 2);
            $result[$id] = (abs($sum - $delta) < 0.01);
        }
        return $result;
    }

    protected static function calcStatus($self) {
        $result = [];
        $self->read(['is_reconciled']);
        foreach($self as $id => $statement) {
            $result[$id] = $statement['is_reconciled'] ? 'reconciled' : 'pending';
        }
        return $result;
    }

    protected static function doAttemptReconcile($self) {
        $self->read(['statement_lines_ids']);
        foreach($self as $statement) {
            BankStatementLine::ids($statement['statement_lines_ids'])->do('attempt_reconcile');
        }
        $self->do('refresh_status');
    }

    protected static function doRefreshStatus($self) {
        $ids = $self->ids();
        if(!count($ids)) {
            return;
        }
        self::ids($ids)->update(['is_reconciled' => null, 'status' => null]);
        self::ids($ids)->read(['is_reconciled', 'status']);
    }

    /** Extension point for the future Funding/FundingAllocation integration. */
    protected static function doGenerateFundings($self) {
    }

    /** Extension point for the future accounting integration. */
    protected static function doGenerateAccountingEntries($self) {
    }

    public static function convertBbanToIban($account_number) {
        $account_number = strtoupper(preg_replace('/[^A-Z0-9]/i', '', (string) $account_number));
        if($account_number === '' || !is_numeric(substr($account_number, 0, 2))) {
            return $account_number;
        }
        $control = 98 - ((int) ($account_number.'111400') % 97);
        return sprintf('BE%02d%s', $control, $account_number);
    }

    public function getUnique() {
        return [
            ['bank_account_iban', 'statement_number', 'opening_date', 'opening_balance', 'closing_date', 'closing_balance']
        ];
    }
}
