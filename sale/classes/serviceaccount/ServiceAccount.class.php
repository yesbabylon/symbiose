<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2024
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace sale\serviceaccount;

use sale\customer\Customer;

class ServiceAccount extends \sale\contract\Contract {

    public function getTable() {
        return 'sale_contract_serviceaccount';
    }

    public static function getFlags(): int {
        return EQ_FLAG_OWN_TABLE;
    }

    public static function getDescription() {
        return "Service Accounts relate to Customers and are equivalent to Contracts. These entities are Read-Only and synced from AutoTask.";
    }

    public static function getColumns() {
        return [

            'reporting_from' => [
                'type'              => 'date',
                'description'       => 'First report date and earliest report start date allowed for sending.',
                'dependents'        => ['reports_ids' => ['is_sendable']]
            ],

            'service_account_entries_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'sale\serviceaccount\ServiceAccountEntry',
                'foreign_field'     => 'service_account_id',
                'description'       => 'List of all lines referring to the service account.',
            ],

            'reports_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'sale\serviceaccount\Report',
                'foreign_field'     => 'service_account_id',
                'description'       => 'List of reports of the service account.',
                'ondetach'          => 'delete'
            ],

            'balance_current' => [
                'type'              => 'computed',
                'result_type'       => 'float',
                'store'             => true,
                'description'       => 'The amount of remaining points in the account according to all existing lines.',
                'function'          => 'calcBalanceCurrent'
            ],

            'has_balance_changed' => [
                'type'              => 'boolean',
                'description'       => 'Mark the balance as changed.',
                'default'           => false
            ],

            'last_entry_id' => [
                'type'              => 'computed',
                'result_type'       => 'many2one',
                'foreign_object'    => 'sale\serviceaccount\ServiceAccountEntry',
                'function'          => 'calcLastEntryId',
                'description'       => 'The most recent line relating to the the account.'
            ],

            'is_invoiceable' => [
                'type'              => 'boolean',
                'description'       => 'The contract as implies CutOff (will be listed in Cut-Off Reports).',
                'default'           => true
            ],

            'm_reporting' => [
                'type'              => 'string',
                'description'       => 'Mode for reporting to the customer about the contract.',
                'help'              => 'Indicates how the Reports must be communicated to the customer: no sending, sending, sending + archive.',
                'default'           => 'send',
                'selection'         => [
                    'none',
                    'send',
                    'archive'
                ],
                'dependents'        => ['reports_ids' => ['is_sendable']]
            ],

            'has_monthly_target' => [
                'type'              => 'boolean',
                'description'       => "Flag for Service account with monthly target.",
                'default'           => false
            ],

            'monthly_target' => [
                'type'              => 'float',
                'usage'             => 'numeric/real:5.2',
                'description'       => "Estimated amount of points for internal followup purpose.",
                'visible'           => ['has_monthly_target', '=', true]
            ],

            'renew_auto' => [
                'type'              => 'boolean',
                'description'       => "Automatic Renewal of ServicePackage.",
                'default'           => false
            ],

            'renew_amount' => [
                'type'              => 'float',
                'usage'             => 'amount/money:2',
                'description'       => "Renewal ServicePack amount in €.",
                'visible'           => ['renew_auto', '=', true]
            ],

            'renew_floor' => [
                'type'              => 'float',
                'description'       => "Floor in # for triggering renewal.",
                'default'           => 0,
                'visible'           => ['renew_auto', '=', true]
            ],

            'has_renew_alert_sent' => [
                'type'              => 'boolean',
                'description'       => "Balance is below floor and ticket has been sent to AT.",
                'default'           => false
            ]

        ];
    }

    public static function getActions() {
        return array_merge(parent::getActions(), [
            'generate_report' => [
                'description' => 'Create or update a draft report with all unreported entries of the service account.',
                'help'        => 'The report end date is the date of the most recent entry being attached.',
                'policies'    => [],
                'function'    => 'doGenerateReport'
            ]
        ]);
    }

    protected static function doGenerateReport($self) {
        $self->read(['is_active']);

        $plans = [];
        foreach($self as $id => $serviceAccount) {
            if(!$serviceAccount['is_active']) {
                throw new \Exception('inactive_service_account', EQ_ERROR_INVALID_PARAM);
            }

            $entries = ServiceAccountEntry::search([
                    ['service_account_id', '=', $id],
                    ['has_report', '=', false]
                ], [
                    'sort' => ['date' => 'asc']
                ])
                ->read(['id', 'date'])
                ->get();

            if(!count($entries)) {
                throw new \Exception('no_eligible_entries', EQ_ERROR_INVALID_PARAM);
            }

            $entries_ids = [];
            $date_to = 0;
            foreach($entries as $entry_id => $entry) {
                $entries_ids[] = $entry_id;
                $date_to = max($date_to, (int) ($entry['date'] ?? 0));
            }
            if(!$date_to) {
                $date_to = time();
            }

            $pending_reports_ids = Report::search([
                    ['service_account_id', '=', $id],
                    ['status', '=', 'pending']
                ], [
                    'sort'  => ['date' => 'desc'],
                    'limit' => 2
                ])
                ->ids();

            if(count($pending_reports_ids) > 1) {
                throw new \Exception('multiple_pending_reports', EQ_ERROR_INVALID_PARAM);
            }

            $pending_report = null;
            if(count($pending_reports_ids)) {
                $pending_report = Report::id(reset($pending_reports_ids))
                    ->read(['id', 'date'])
                    ->first();
                $date_to = max($date_to, (int) ($pending_report['date'] ?? 0));
            }

            $plans[] = [
                'service_account_id' => $id,
                'entries_ids'        => $entries_ids,
                'date_to'            => $date_to,
                'pending_report'     => $pending_report
            ];
        }

        $computed_fields = [
            'date_from'       => null,
            'has_lines'       => null,
            'is_empty'        => null,
            'total_points'    => null,
            'total_credits'   => null,
            'balance_old'     => null,
            'balance_new'     => null,
            'is_sendable'     => null,
            'pdf_data'        => null
        ];

        foreach($plans as $plan) {
            $report_id = null;
            $created_report = false;
            $entries_attached = false;

            try {
                if($plan['pending_report']) {
                    $report_id = $plan['pending_report']['id'];
                    Report::id($report_id)->update(['date' => $plan['date_to']]);
                }
                else {
                    $report = Report::create([
                            'date'               => $plan['date_to'],
                            'service_account_id' => $plan['service_account_id'],
                            'status'             => 'pending'
                        ])
                        ->read(['id'])
                        ->first();

                    if(!$report) {
                        throw new \Exception('report_creation_failed', EQ_ERROR_INVALID_PARAM);
                    }

                    $report_id = $report['id'];
                    $created_report = true;
                }

                ServiceAccountEntry::ids($plan['entries_ids'])
                    ->update(['report_id' => $report_id]);
                $entries_attached = true;

                Report::id($report_id)
                    ->update($computed_fields)
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
            }
            catch(\Exception $exception) {
                try {
                    if($created_report && $report_id) {
                        Report::id($report_id)->delete(true);
                    }
                    elseif($report_id) {
                        if($entries_attached) {
                            ServiceAccountEntry::ids($plan['entries_ids'])
                                ->update(['report_id' => null]);
                        }
                        Report::id($report_id)
                            ->update(array_merge(
                                ['date' => $plan['pending_report']['date']],
                                $computed_fields
                            ));
                    }
                }
                catch(\Exception $rollback_exception) {
                    trigger_error(
                        'ORM::error while rolling back report generation - '.$rollback_exception->getMessage(),
                        QN_REPORT_ERROR
                    );
                }
                throw $exception;
            }
        }
    }


    public static function calcBalanceCurrent($self) {
        $result = [];
        foreach($self as $id => $serviceAccount) {
            // retrieve the amount of balance_new from the latest released report
            $report = Report::search([
                        ['service_account_id', '=', $id],
                        ['status', '<>', 'pending']
                    ],
                    [
                        'sort'  => ['id' => 'desc'],
                        'limit' => 1
                    ]
                )
                ->read(['balance_new'])
                ->first();
            // use report balance_new and, if missing, fallback to account initial balance
            if($report) {
                $balance = $report['balance_new'];
            }
            else {
                $balance = 0.0;
            }
            $result[$id] = $balance;
        }
        return $result;
    }

    public static function calcLastEntryId($self) {
        $result = [];
        foreach($self as $id => $serviceAccount) {
            $line = ServiceAccountEntry::search(['service_account_id', '=', $id], ['sort'  => ['date' => 'desc'], 'limit' => 1])
                ->read(['id'])
                ->first();
            if($line) {
                $result[$id] = $line['id'];
            }
        }
        return $result;
    }



}
