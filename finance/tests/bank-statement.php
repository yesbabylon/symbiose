<?php

use finance\bank\BankAccount;
use finance\bank\BankStatement;
use finance\bank\BankStatementLine;
use finance\accounting\Account;
use finance\accounting\AccountChart;
use finance\accounting\AccountingEntry;
use finance\accounting\FiscalPeriod;
use finance\accounting\FiscalYear;
use finance\accounting\Journal;
use finance\accounting\operation\AccountingOperation;

$tests = [
    '0101' => [
        'description' => 'Compute statement compatibility fields, balances, sequencing and reconciliation.',
        'arrange'     => static function(): array {
            $iban = 'BE68539007547034';
            $bank_account = BankAccount::search(['bank_account_iban', '=', $iban])
                ->read(['id'])
                ->first(true);
            $created_bank_account = false;
            if(!$bank_account) {
                $bank_account = BankAccount::create([
                        'organization_id'   => 1,
                        'bank_name'         => 'Bank statement test',
                        'bank_account_iban' => $iban,
                        'bank_account_bic'  => 'KREDBEBB'
                    ])
                    ->read(['id'])
                    ->first(true);
                $created_bank_account = true;
            }

            $suffix = bin2hex(random_bytes(5));
            $previous = BankStatement::create([
                    'bank_account_id'     => $bank_account['id'],
                    'statement_number'    => 'PREV-'.$suffix,
                    'statement_currency'  => 'EUR',
                    'opening_date'        => strtotime('2026-01-01'),
                    'date'                => strtotime('2026-01-31'),
                    'old_balance'         => 50.0,
                    'new_balance'         => 100.0,
                    'bank_account_number' => $iban,
                    'bank_account_bic'    => 'KREDBEBB'
                ])
                ->read(['id'])
                ->first(true);

            $current = BankStatement::create([
                    'bank_account_id'     => $bank_account['id'],
                    'statement_number'    => 'CURR-'.$suffix,
                    'statement_currency'  => 'EUR',
                    'opening_date'        => strtotime('2026-02-01'),
                    'date'                => strtotime('2026-02-28'),
                    'old_balance'         => 100.0,
                    'new_balance'         => 125.0,
                    'bank_account_number' => $iban,
                    'bank_account_bic'    => 'KREDBEBB'
                ])
                ->read(['id'])
                ->first(true);

            $first_line = BankStatementLine::create([
                    'bank_statement_id' => $current['id'],
                    'date'              => strtotime('2026-02-10'),
                    'amount'            => 10.0,
                    'amount_currency'   => 'EUR',
                    'communication'     => 'First transaction',
                    'message'           => 'First transaction',
                    'reconciliation_status' => 'ignored'
                ])
                ->read(['id'])
                ->first(true);

            $second_line = BankStatementLine::create([
                    'bank_statement_id' => $current['id'],
                    'date'              => strtotime('2026-02-11'),
                    'amount'            => 15.0,
                    'amount_currency'   => 'EUR',
                    'communication'     => 'Second transaction',
                    'message'           => 'Second transaction',
                    'reconciliation_status' => 'ignored'
                ])
                ->read(['id'])
                ->first(true);

            return [
                'bank_account_id'      => $bank_account['id'],
                'created_bank_account' => $created_bank_account,
                'previous_id'          => $previous['id'],
                'current_id'           => $current['id'],
                'first_line_id'        => $first_line['id'],
                'second_line_id'       => $second_line['id'],
                'statement_number'     => 'CURR-'.$suffix,
                'iban'                 => $iban
            ];
        },
        'act'         => static function(array $fixture): array {
            BankStatement::id($fixture['current_id'])->do('refresh_status');
            BankStatement::id($fixture['current_id'])->do('generate_fundings');
            BankStatement::id($fixture['current_id'])->do('generate_accounting_entries');
            BankStatementLine::ids([$fixture['first_line_id'], $fixture['second_line_id']])->do('assert_funding');

            $statement = BankStatement::id($fixture['current_id'])
                ->read([
                    'name', 'closing_date', 'opening_balance', 'closing_balance',
                    'balance_delta', 'expected_opening_balance', 'bank_account_iban',
                    'is_balanced', 'is_reconciled', 'status'
                ])
                ->first(true);
            $lines = BankStatementLine::ids([$fixture['first_line_id'], $fixture['second_line_id']])
                ->read(['id', 'name', 'sequence_number', 'remaining_amount', 'is_reconciled'])
                ->toArray();

            return array_merge($fixture, [
                'statement' => $statement,
                'lines'     => $lines
            ]);
        },
        'assert'      => static function(array $result): bool {
            $statement = $result['statement'];
            $lines = array_values($result['lines']);
            $expected_name = sprintf('%s - %s - 100.00 - 125.00', $result['iban'], $result['statement_number']);

            if($statement['name'] !== $expected_name
                || $statement['closing_date'] !== strtotime('2026-02-28')
                || abs($statement['opening_balance'] - 100.0) >= 0.01
                || abs($statement['closing_balance'] - 125.0) >= 0.01
                || abs($statement['balance_delta'] - 25.0) >= 0.01
                || abs($statement['expected_opening_balance'] - 100.0) >= 0.01
                || $statement['bank_account_iban'] !== $result['iban']
                || !$statement['is_balanced']
                || !$statement['is_reconciled']
                || $statement['status'] !== 'reconciled') {
                throw new RuntimeException('The statement calculations are inconsistent.');
            }

            if(count($lines) !== 2
                || $lines[0]['sequence_number'] !== 1
                || $lines[1]['sequence_number'] !== 2
                || strpos($lines[0]['name'], '#1 First transaction') === false
                || strpos($lines[1]['name'], '#2 Second transaction') === false
                || abs($lines[0]['remaining_amount'] - 10.0) >= 0.01
                || abs($lines[1]['remaining_amount'] - 15.0) >= 0.01
                || !$lines[0]['is_reconciled']
                || !$lines[1]['is_reconciled']) {
                throw new RuntimeException('The statement line calculations are inconsistent.');
            }

            return true;
        },
        'rollback'    => static function(array $result): void {
            BankStatement::ids([$result['current_id'], $result['previous_id']])->delete(true);
            if($result['created_bank_account']) {
                BankAccount::id($result['bank_account_id'])->delete(true);
            }
        }
    ],
    '0201' => [
        'description' => 'Post a reconciled bank statement line as an accounting operation.',
        'arrange'     => static function(): array {
            $suffix = bin2hex(random_bytes(5));
            $year = random_int(2100, 2400);
            $date_from = strtotime("{$year}-01-01");
            $date_to = strtotime("{$year}-12-31");
            $transaction_date = strtotime("{$year}-02-10");
            $statement_date = strtotime("{$year}-02-28");
            $iban = 'BE68539007547034';
            $bank_account = BankAccount::search(['bank_account_iban', '=', $iban])
                ->read(['id'])
                ->first(true);
            $created_bank_account = false;
            if(!$bank_account) {
                $bank_account = BankAccount::create([
                        'organization_id'   => 1,
                        'bank_name'         => 'Bank statement posting test',
                        'bank_account_iban' => $iban,
                        'bank_account_bic'  => 'KREDBEBB'
                    ])
                    ->read(['id'])
                    ->first(true);
                $created_bank_account = true;
            }
            $account_chart = AccountChart::create([
                    'name'            => 'Bank statement test '.$suffix,
                    'organization_id' => 1
                ])
                ->read(['id'])
                ->first(true);
            $bank_accounting_account = Account::create([
                    'code'               => '58'.substr($suffix, 0, 6),
                    'is_control_account' => false,
                    'account_class'      => '05',
                    'account_chart_id'   => $account_chart['id'],
                    'nature'             => 'B'
                ])
                ->read(['id'])
                ->first(true);
            $counterpart_account = Account::create([
                    'code'               => '49'.substr($suffix, 0, 6),
                    'is_control_account' => false,
                    'account_class'      => '04',
                    'account_chart_id'   => $account_chart['id'],
                    'nature'             => 'B'
                ])
                ->read(['id'])
                ->first(true);
            $journal = Journal::create([
                    'code'               => 'BNK'.substr($suffix, 0, 5),
                    'journal_type'       => 'BANK',
                    'organization_id'    => 1,
                    'default_account_id' => $bank_accounting_account['id']
                ])
                ->read(['id'])
                ->first(true);
            $fiscal_year = FiscalYear::create([
                    'organization_id'         => 1,
                    'date_from'               => $date_from,
                    'date_to'                 => $date_to,
                    'fiscal_period_frequency' => 'A',
                    'status'                  => 'open'
                ])
                ->read(['id'])
                ->first(true);
            $fiscal_period = FiscalPeriod::create([
                    'fiscal_year_id' => $fiscal_year['id'],
                    'code'           => 1,
                    'date_from'      => $date_from,
                    'date_to'        => $date_to,
                    'status'         => 'open'
                ])
                ->read(['id'])
                ->first(true);
            $statement = BankStatement::create([
                    'organization_id'    => 1,
                    'bank_account_id'    => $bank_account['id'],
                    'statement_number'   => 'POST-'.$suffix,
                    'statement_currency' => 'EUR',
                    'opening_date'       => strtotime("{$year}-02-01"),
                    'date'               => $statement_date,
                    'old_balance'        => 0.0,
                    'new_balance'        => 25.0,
                    'bank_account_number'=> $iban,
                    'bank_account_bic'   => 'KREDBEBB'
                ])
                ->read(['id'])
                ->first(true);
            $line = BankStatementLine::create([
                    'bank_statement_id'     => $statement['id'],
                    'date'                  => $transaction_date,
                    'amount'                => 25.0,
                    'amount_currency'       => 'EUR',
                    'communication'         => 'Posted bank transaction',
                    'accounting_account_id' => $counterpart_account['id'],
                    'reconciliation_status' => 'reconciled'
                ])
                ->read(['id'])
                ->first(true);

            return [
                'account_chart_id'          => $account_chart['id'],
                'bank_accounting_account_id'=> $bank_accounting_account['id'],
                'counterpart_account_id'    => $counterpart_account['id'],
                'journal_id'                => $journal['id'],
                'fiscal_year_id'            => $fiscal_year['id'],
                'fiscal_period_id'          => $fiscal_period['id'],
                'bank_account_id'           => $bank_account['id'],
                'created_bank_account'      => $created_bank_account,
                'statement_id'              => $statement['id'],
                'line_id'                   => $line['id']
            ];
        },
        'act'         => static function(array $fixture): array {
            BankStatementLine::id($fixture['line_id'])
                ->transition('submit')
                ->transition('post');

            $posted_line = BankStatementLine::id($fixture['line_id'])
                ->read([
                    'status',
                    'operation_number',
                    'is_balanced',
                    'operation_lines_ids' => ['debit', 'credit'],
                    'accounting_entries_ids' => ['id', 'status', 'is_balanced']
                ])
                ->first();

            BankStatementLine::id($fixture['line_id'])->transition('cancel');
            $cancelled_line = BankStatementLine::id($fixture['line_id'])
                ->read([
                    'status',
                    'accounting_entries_ids' => ['id', 'status'],
                    'reversal_operations_ids' => [
                        'id',
                        'status',
                        'accounting_entries_ids' => ['id', 'status']
                    ]
                ])
                ->first();

            $entry_ids = $posted_line['accounting_entries_ids']->ids();
            $reversal_ids = $cancelled_line['reversal_operations_ids']->ids();
            foreach($cancelled_line['reversal_operations_ids'] as $reversal) {
                $entry_ids = array_merge($entry_ids, $reversal['accounting_entries_ids']->ids());
            }

            return array_merge($fixture, [
                'posted_line' => [
                    'status'                 => $posted_line['status'],
                    'operation_number'       => $posted_line['operation_number'],
                    'is_balanced'            => $posted_line['is_balanced'],
                    'operation_lines_ids'    => $posted_line['operation_lines_ids']->toArray(),
                    'accounting_entries_ids' => $posted_line['accounting_entries_ids']->toArray()
                ],
                'cancelled_line' => [
                    'status'                       => $cancelled_line['status'],
                    'accounting_entries_ids'       => $cancelled_line['accounting_entries_ids']->toArray(),
                    'reversal_operations_ids'      => $cancelled_line['reversal_operations_ids']->toArray()
                ],
                'entry_ids'    => array_values(array_unique($entry_ids)),
                'reversal_ids' => $reversal_ids
            ]);
        },
        'assert'      => static function(array $result): bool {
            $line = $result['posted_line'];
            if($line['status'] !== 'posted'
                || !$line['operation_number']
                || !$line['is_balanced']
                || count($line['operation_lines_ids']) !== 2
                || count($line['accounting_entries_ids']) !== 1
                || $line['accounting_entries_ids'][0]['status'] !== 'posted'
                || !$line['accounting_entries_ids'][0]['is_balanced']) {
                throw new RuntimeException(sprintf(
                    'Unexpected posting result: status=%s number=%s balanced=%s operation_lines=%d entries=%d entry_status=%s entry_balanced=%s.',
                    $line['status'],
                    $line['operation_number'] ?: 'empty',
                    $line['is_balanced'] ? 'yes' : 'no',
                    count($line['operation_lines_ids']),
                    count($line['accounting_entries_ids']),
                    $line['accounting_entries_ids'][0]['status'] ?? 'missing',
                    !empty($line['accounting_entries_ids'][0]['is_balanced']) ? 'yes' : 'no'
                ));
            }
            $cancelled_line = $result['cancelled_line'];
            if($cancelled_line['status'] !== 'cancelled'
                || count($cancelled_line['reversal_operations_ids']) !== 1
                || $cancelled_line['reversal_operations_ids'][0]['status'] !== 'posted'
                || $cancelled_line['accounting_entries_ids'][0]['status'] !== 'reversed') {
                throw new RuntimeException('The posted bank statement line was not reversed correctly.');
            }
            return true;
        },
        'rollback'    => static function(array $result): void {
            AccountingEntry::ids($result['entry_ids'] ?? [])->delete(true);
            foreach($result['reversal_ids'] ?? [] as $reversal_id) {
                AccountingOperation::id($reversal_id)
                    ->update(['status' => 'pending'])
                    ->delete(true);
            }
            BankStatement::id($result['statement_id'])->delete(true);
            if($result['created_bank_account']) {
                BankAccount::id($result['bank_account_id'])->delete(true);
            }
            FiscalYear::id($result['fiscal_year_id'])->update(['status' => 'draft']);
            FiscalPeriod::id($result['fiscal_period_id'])->delete(true);
            FiscalYear::id($result['fiscal_year_id'])->delete(true);
            Journal::id($result['journal_id'])->delete(true);
            Account::ids([
                $result['bank_accounting_account_id'],
                $result['counterpart_account_id']
            ])->delete(true);
            AccountChart::id($result['account_chart_id'])->delete(true);
        }
    ]
];
