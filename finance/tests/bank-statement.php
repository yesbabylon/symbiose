<?php

use finance\bank\BankAccount;
use finance\bank\BankStatement;
use finance\bank\BankStatementLine;

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
                    'status'            => 'ignored'
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
                    'status'            => 'ignored'
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
            BankStatementLine::ids([$fixture['first_line_id'], $fixture['second_line_id']])->do('generate_accounting_entry');

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
    ]
];
