<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2024
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace sale\serviceaccount;

class ServiceAccount extends \sale\contract\Contract {

    public static function getModelTable(): string {
        return self::getSlug();
    }

    public static function getDescription() {
        return 'Service Accounts relate to Customers and are equivalent to Contracts.';
    }

    public static function getColumns() {
        return [
            'reporting_from' => [
                'type'        => 'date',
                'description' => 'Start date of the first reporting period.',
                'dependents'  => ['reports_ids' => ['is_sendable']]
            ],

            'reporting_mode' => [
                'type'        => 'string',
                'description' => 'Mode for reporting to the customer about the contract.',
                'help'        => 'Indicates how reports must be communicated to the customer.',
                'default'     => 'send',
                'selection'   => ['none', 'send', 'archive'],
                'dependents'  => ['reports_ids' => ['is_sendable']]
            ],

            'reporting_frequency' => [
                'type'        => 'string',
                'description' => 'Frequency used to build sequential reporting periods.',
                'default'     => 'monthly',
                'selection'   => ['monthly']
            ],

            // Kept temporarily as a migration source. New code must use reporting_mode.
            'm_reporting' => [
                'type'        => 'string',
                'description' => 'Legacy reporting mode kept during data migration.',
                'default'     => 'send',
                'selection'   => ['none', 'send', 'archive'],
                'readonly'    => true
            ],

            'service_account_entries_ids' => [
                'type'           => 'one2many',
                'foreign_object' => 'sale\serviceaccount\ServiceAccountEntry',
                'foreign_field'  => 'service_account_id',
                'description'    => 'List of all lines referring to the service account.'
            ],

            'reports_ids' => [
                'type'           => 'one2many',
                'foreign_object' => 'sale\serviceaccount\Report',
                'foreign_field'  => 'service_account_id',
                'description'    => 'List of reports of the service account.',
                'ondetach'       => 'delete'
            ],

            'balance_effective' => [
                'type'        => 'computed',
                'result_type' => 'float',
                'store'       => true,
                'description' => 'Balance from the latest finalized report.',
                'function'    => 'calcBalanceEffective'
            ],

            'balance_estimated' => [
                'type'        => 'computed',
                'result_type' => 'float',
                'store'       => true,
                'description' => 'Balance including the current pending report.',
                'function'    => 'calcBalanceEstimated'
            ],

            // Kept temporarily as a migration source. New code must use balance_effective.
            'balance_current' => [
                'type'        => 'computed',
                'result_type' => 'float',
                'store'       => true,
                'description' => 'Legacy effective balance kept during data migration.',
                'function'    => 'calcBalanceEffective',
                'readonly'    => true
            ],

            'last_entry_id' => [
                'type'           => 'computed',
                'result_type'    => 'many2one',
                'foreign_object' => 'sale\serviceaccount\ServiceAccountEntry',
                'function'       => 'calcLastEntryId',
                'description'    => 'The most recent line relating to the account.'
            ],

            'is_invoiceable' => [
                'type'        => 'boolean',
                'description' => 'The contract is included in cut-off reports.',
                'default'     => true
            ],

            'has_monthly_target' => [
                'type'        => 'boolean',
                'description' => 'Flag for Service account with monthly target.',
                'default'     => false
            ],

            'monthly_target' => [
                'type'        => 'float',
                'usage'       => 'numeric/real:5.2',
                'description' => 'Estimated amount of points for internal followup purpose.',
                'visible'     => ['has_monthly_target', '=', true]
            ],

            'renew_auto' => [
                'type'        => 'boolean',
                'description' => 'Automatic Renewal of ServicePackage.',
                'default'     => false
            ],

            'renew_amount' => [
                'type'        => 'float',
                'usage'       => 'amount/money:2',
                'description' => 'Renewal ServicePack amount in €.',
                'visible'     => ['renew_auto', '=', true]
            ],

            'renew_floor' => [
                'type'        => 'float',
                'description' => 'Floor in # for triggering renewal.',
                'default'     => 0,
                'visible'     => ['renew_auto', '=', true]
            ],

            'has_renew_alert_sent' => [
                'type'        => 'boolean',
                'description' => 'Balance is below floor and ticket has been sent to AT.',
                'default'     => false
            ]
        ];
    }

    public static function getActions() {
        return array_merge(parent::getActions(), [
            'generate_report' => [
                'description' => 'Create or update the single pending report for the next sequential period.',
                'help'        => 'All pending entries up to the period end are attached, including older unreported entries.',
                'policies'    => [],
                'function'    => 'doGenerateReport'
            ],
            'refresh_balances' => [
                'description' => 'Invalidate and recompute effective and estimated balances.',
                'policies'    => [],
                'function'    => 'doRefreshBalances'
            ]
        ]);
    }

    protected static function doGenerateReport($self, $values = []) {
        $self->read(['is_active', 'reporting_from', 'reporting_frequency', 'date_from']);

        ['db' => $db_connector] = \eQual::inject(['db']);
        $db = $db_connector->connect();
        if(!$db) {
            throw new \Exception('missing_database', EQ_ERROR_INVALID_CONFIG);
        }

        $manage_transaction = !isset($values['manage_transaction']) || $values['manage_transaction'];
        if($manage_transaction) {
            $db->sendQuery('START TRANSACTION;');
        }
        try {
            foreach($self as $id => $service_account) {
                if(!$service_account['is_active']) {
                    throw new \Exception('inactive_service_account', EQ_ERROR_INVALID_PARAM);
                }

                $pending_reports_ids = Report::search([
                        ['service_account_id', '=', $id],
                        ['status', '=', 'pending']
                    ], [
                        'sort'  => ['date' => 'desc', 'id' => 'desc'],
                        'limit' => 2
                    ])
                    ->ids();

                if(count($pending_reports_ids) > 1) {
                    throw new \Exception('multiple_pending_reports', EQ_ERROR_INVALID_PARAM);
                }

                $report_id = reset($pending_reports_ids) ?: null;
                if($report_id) {
                    $report = Report::id($report_id)
                        ->read(['id', 'date', 'date_from'])
                        ->first();
                }
                else {
                    $date_from = self::getNextPeriodStart($id, $service_account);
                    $date_to = self::getPeriodEnd($date_from, $service_account['reporting_frequency'] ?? 'monthly');

                    $report = Report::create([
                            'date'               => $date_to,
                            'service_account_id' => $id,
                            'status'             => 'pending'
                        ])
                        ->read(['id', 'date', 'date_from'])
                        ->first();

                    if(!$report) {
                        throw new \Exception('report_creation_failed', EQ_ERROR_INVALID_PARAM);
                    }
                    $report_id = $report['id'];
                }

                $next_day = strtotime('+1 day', (int) $report['date']);

                // Keep entries after the cut-off available for a later report.
                $future_entries_ids = ServiceAccountEntry::search([
                        ['report_id', '=', $report_id],
                        ['status', '=', 'pending'],
                        ['date', '>=', $next_day]
                    ])
                    ->ids();
                if($future_entries_ids) {
                    ServiceAccountEntry::ids($future_entries_ids)->update(['report_id' => null]);
                }

                $entries_ids = ServiceAccountEntry::search([
                        ['service_account_id', '=', $id],
                        ['status', '=', 'pending'],
                        ['report_id', '=', null],
                        ['date', '<', $next_day]
                    ], [
                        'sort' => ['date' => 'asc', 'id' => 'asc']
                    ])
                    ->ids();

                if($entries_ids) {
                    ServiceAccountEntry::ids($entries_ids)->update(['report_id' => $report_id]);
                }

                Report::id($report_id)
                    ->update(self::getReportComputedInvalidation())
                    ->read([
                        'date_from',
                        'has_lines',
                        'is_empty',
                        'total_points',
                        'total_credits',
                        'balance_old',
                        'balance_new',
                        'is_sendable'
                    ]);

                self::id($id)->do('refresh_balances');
            }
            if($manage_transaction) {
                $db->sendQuery('COMMIT;');
            }
        }
        catch(\Throwable $throwable) {
            if($manage_transaction) {
                $db->sendQuery('ROLLBACK;');
            }
            throw $throwable;
        }
    }

    protected static function doRefreshBalances($self) {
        $self->update([
            'balance_effective' => null,
            'balance_estimated' => null,
            'balance_current'   => null
        ]);
        $self->read(['balance_effective', 'balance_estimated']);
    }

    public static function calcBalanceEffective($self) {
        $result = [];
        foreach($self as $id => $service_account) {
            $report = Report::search([
                    ['service_account_id', '=', $id],
                    ['status', '<>', 'pending']
                ], [
                    'sort'  => ['date' => 'desc', 'id' => 'desc'],
                    'limit' => 1
                ])
                ->read(['balance_new'])
                ->first();
            $result[$id] = (float) ($report['balance_new'] ?? 0.0);
        }
        return $result;
    }

    public static function calcBalanceEstimated($self) {
        $result = [];
        $self->read(['balance_effective']);
        foreach($self as $id => $service_account) {
            $pending_report = Report::search([
                    ['service_account_id', '=', $id],
                    ['status', '=', 'pending']
                ], [
                    'sort'  => ['date' => 'desc', 'id' => 'desc'],
                    'limit' => 1
                ])
                ->read(['balance_new'])
                ->first();
            $result[$id] = $pending_report
                ? (float) $pending_report['balance_new']
                : (float) ($service_account['balance_effective'] ?? 0.0);
        }
        return $result;
    }

    public static function calcLastEntryId($self) {
        $result = [];
        foreach($self as $id => $service_account) {
            $line = ServiceAccountEntry::search([
                    ['service_account_id', '=', $id]
                ], [
                    'sort'  => ['date' => 'desc', 'id' => 'desc'],
                    'limit' => 1
                ])
                ->read(['id'])
                ->first();
            if($line) {
                $result[$id] = $line['id'];
            }
        }
        return $result;
    }

    private static function getNextPeriodStart(int $service_account_id, $service_account): int {
        $last_report = Report::search([
                ['service_account_id', '=', $service_account_id],
                ['status', '<>', 'pending']
            ], [
                'sort'  => ['date' => 'desc', 'id' => 'desc'],
                'limit' => 1
            ])
            ->read(['date'])
            ->first();

        if($last_report) {
            return strtotime('+1 day', (int) $last_report['date']);
        }

        $date_from = $service_account['reporting_from'] ?? $service_account['date_from'] ?? null;
        if(!$date_from) {
            throw new \Exception('missing_reporting_start', EQ_ERROR_INVALID_PARAM);
        }
        return strtotime('midnight', (int) $date_from);
    }

    private static function getPeriodEnd(int $date_from, string $frequency): int {
        if($frequency !== 'monthly') {
            throw new \Exception('unsupported_reporting_frequency', EQ_ERROR_INVALID_PARAM);
        }

        $start = new \DateTimeImmutable('@'.$date_from);
        $start = $start->setTimezone(new \DateTimeZone('UTC'));
        $year = (int) $start->format('Y');
        $month = (int) $start->format('n') + 1;
        if($month === 13) {
            $month = 1;
            ++$year;
        }
        $day = min((int) $start->format('j'), cal_days_in_month(CAL_GREGORIAN, $month, $year));
        $next_start = $start->setDate($year, $month, $day);

        return $next_start->modify('-1 day')->getTimestamp();
    }

    private static function getReportComputedInvalidation(): array {
        return [
            'date_from'     => null,
            'has_lines'     => null,
            'is_empty'      => null,
            'total_points'  => null,
            'total_credits' => null,
            'balance_old'   => null,
            'balance_new'   => null,
            'is_sendable'   => null,
            'pdf_data'      => null
        ];
    }
}
