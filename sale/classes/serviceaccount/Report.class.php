<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2024
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace sale\serviceaccount;

use document\document\Document;

class Report extends \equal\orm\Model {

    public static function getColumns() {

        return [

            'name' => [
                'type'              => 'computed',
                'result_type'       => 'string',
                'description'       => 'Short readable identifier of the report.',
                'function'          => 'calcName',
                'store'             => true,
                'instant'           => true,
                'readonly'          => true
            ],

            'date' => [
                'type'              => 'date',
                'description'       => 'Last calendar day of the reporting period.',
                'help'              => 'This date is determined by the account reporting frequency.',
                'default'           => time(),
                'dependents'        => ['name']
            ],

            'date_from' => [
                'type'              => 'computed',
                'result_type'       => 'date',
                'description'       => 'Day after the previous finalized report end date.',
                'help'              => 'There might be some remaining time entries included in report whose date precedes the report\'s date_from.',
                'function'          => 'calcDateFrom',
                'store'             => true,
                'dependents'        => ['is_sendable']
            ],

            'service_account_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\serviceaccount\ServiceAccount',
                'description'       => 'The service account the line belongs to.',
                'required'          => true,
                'dependents'        => ['customer_id', 'balance_old', 'has_lines', 'is_sendable']
            ],

            'customer_id' => [
                'type'              => 'computed',
                'result_type'       => 'many2one',
                'foreign_object'    => 'sale\customer\Customer',
                'description'       => 'The customer the report relates to (from service account).',
                'relation'          => ['service_account_id' => ['customer_id']],
                'store'             => true,
                'instant'           => true
            ],

            'has_lines' => [
                'type'              => 'computed',
                'result_type'       => 'boolean',
                'store'             => true,
                'instant'           => true,
                'function'          => 'calcHasLines',
                'description'       => 'Flag for telling if the report has at least one line.'
            ],

            'service_account_entries_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'sale\serviceaccount\ServiceAccountEntry',
                'foreign_field'     => 'report_id',
                'order'             => 'date',
                'description'       => 'SA Lines assigned to the report.',
                'dependents'        => ['has_lines']
            ],

            'link' => [
                'type'              => 'computed',
                'result_type'       => 'string',
                'usage'             => 'uri/url',
                'description'       => 'URL for generating the PDF version of the report.',
                'function'          => 'calcLink',
                'readonly'          => true
            ],

            'document_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'document\document\Document',
                'description'       => 'PDF document generated when the report is released.',
                'ondelete'          => 'null'
            ],

            'is_sendable' => [
                'type'              => 'computed',
                'result_type'       => 'boolean',
                'description'       => 'Flag telling if the Report is ready to be sent per app logic.',
                'help'              => 'The Report must be released, use the `send` reporting mode, and not start before `reporting_from`.',
                'function'          => 'calcIsSendable',
                'store'             => true
            ],

            'total_points' => [
                'type'              => 'computed',
                'result_type'       => 'float',
                'store'             => true,
                'function'          => 'calcTotalPoints',
                'description'       => 'Sum of all positive service lines (negative value).'
            ],

            'total_credits' => [
                'type'              => 'computed',
                'result_type'       => 'float',
                'store'             => true,
                'function'          => 'calcTotalCredits',
                'description'       => 'Sum of all credit and correction lines (positive value).'
            ],

            'balance_old' => [
                'type'              => 'computed',
                'result_type'       => 'float',
                'store'             => true,
                'instant'           => true,
                'function'          => 'calcBalanceOld',
                'description'       => 'Service Account balance from the previous report.',
                'help'              => "Tells the balance after invoicing the lines assigned to the report.
                                        For released reports, this value might differ from the relating ServiceAccount's balance."
            ],

            'balance_new' => [
                'type'              => 'computed',
                'result_type'       => 'float',
                'function'          => 'calcBalanceNew',
                'store'             => true,
                'description'       => 'New balance of the related Service Account.',
                'help'              => "Tells the balance after invoicing the lines assigned to the report.
                                        This value is automatically updated upon status change (when report is released).
                                        For pending reports, this value might differ from the relating ServiceAccount's balance."
            ],

            'is_empty' => [
                'type'              => 'computed',
                'result_type'       => 'boolean',
                'store'             => true,
                'function'          => 'calcIsEmpty',
                'description'       => 'Report does not contain any line.'
            ],

            'mails_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'core\Mail',
                'foreign_field'     => 'object_id',
                'domain'            => ['object_class', '=', self::getType()]
            ],

            'status' => [
                'type'              => 'string',
                'selection'         => [
                    'pending',                    // the report is a draft
                    'released',                   // final report: cannot be updated anymore
                    'archived',
                    'sent'
                ],
                'description'       => 'Status of the report.',
                'default'           => 'pending',
                'dependents'        => ['name', 'is_sendable']
            ]
        ];
    }

    protected static function calcName($self) {
        $result = [];
        $self->read(['date', 'status']);
        foreach($self as $id => $report) {
            $result[$id] = self::computeName($id, $report['date'], $report['status']);
        }
        return $result;
    }

    private static function computeName($id, $date, $status) {
        return sprintf(
            '%d-%04d %s',
            date('Ymd', $date),
            $id,
            $status === 'pending' ? ' (DRAFT)' : ''
        );
    }

    public static function getActions() {
        return array_merge(parent::getActions(), [
            'release' => [
                'description' => 'Release the report and update its service account.',
                'help'        => 'Only pending reports can be released.',
                'policies'    => ['can_release'],
                'function'    => 'doRelease'
            ]
        ]);
    }

    public static function getPolicies(): array {
        return array_merge(parent::getPolicies(), [
            'is_pending' => [
                'description' => 'Check that reports are still pending.',
                'function'    => 'policyIsPending'
            ],
            'is_single_pending' => [
                'description' => 'Check that the report is the only pending report for its service account.',
                'function'    => 'policyIsSinglePending'
            ],
            'has_no_entries' => [
                'description' => 'Check that reports have no service account entries.',
                'function'    => 'policyHasNoEntries'
            ],
            'can_release' => [
                'description' => 'Check whether reports can be released.',
                'function'    => 'policyCanRelease'
            ]
        ]);
    }

    public static function getOperationPolicies(): array {
        return array_replace_recursive(parent::getOperationPolicies(), [
            EQ_R_CREATE => [
                '*' => ['is_single_pending']
            ],
            EQ_R_UPDATE => [
                '*'           => ['is_pending'],
                'status'      => ['is_single_pending'],
                'is_sendable' => true
            ],
            EQ_R_DELETE => [
                '*' => ['is_pending', 'has_no_entries']
            ]
        ]);
    }

    protected static function policyIsPending($self, $values): array {
        $result = [];

        if(!count($self)) {
            if(($values['status'] ?? 'pending') !== 'pending') {
                $result['new'] = [
                    'invalid_status' => 'New reports must start in pending status.'
                ];
            }

            return $result;
        }

        $self->read(['status']);
        foreach($self as $id => $report) {
            if($report['status'] !== 'pending') {
                $result[$id] = [
                    'not_allowed' => 'Released Reports cannot be changed.'
                ];
            }
        }

        return $result;
    }

    protected static function policyIsSinglePending($self, $values): array {
        $result = [];

        if(!count($self)) {
            $service_account_id = $values['service_account_id'] ?? null;
            if(!$service_account_id) {
                return $result;
            }

            $pending_report_ids = self::search([
                    ['service_account_id', '=', $service_account_id],
                    ['status', '=', 'pending']
                ], [
                    'limit' => 1
                ])
                ->ids();
            if($pending_report_ids) {
                $result['new'] = [
                    'second_pending_report' => 'Only one pending report is allowed per service account.'
                ];
            }

            return $result;
        }

        $self->read(['status', 'service_account_id']);
        foreach($self as $id => $report) {
            if($report['status'] !== 'pending') {
                $result[$id] = [
                    'not_pending' => 'Only pending Reports can change status.'
                ];
                continue;
            }

            $pending_reports_ids = self::search([
                    ['service_account_id', '=', $report['service_account_id']],
                    ['status', '=', 'pending']
                ], [
                    'limit' => 2
                ])
                ->ids();
            if(count($pending_reports_ids) !== 1 || (int) $pending_reports_ids[0] !== (int) $id) {
                $result[$id] = [
                    'multiple_pending_reports' => 'The Report must be the only pending Report for its service account.'
                ];
            }
        }

        return $result;
    }

    protected static function policyHasNoEntries($self): array {
        $result = [];

        $self->read(['service_account_entries_ids']);
        foreach($self as $id => $report) {
            if(count($report['service_account_entries_ids'])) {
                $result[$id] = [
                    'report_has_entries' => 'Reports with service account entries cannot be deleted.'
                ];
            }
        }

        return $result;
    }

    protected static function policyCanRelease($self): array {
        $result = [];

        $self->read(['status', 'date', 'service_account_id']);

        foreach($self as $id => $report) {
            if($report['status'] !== 'pending') {
                $result[$id]['already_released_report'] = 'Only pending reports can be released.';
            }
            if((int) $report['date'] > strtotime('today')) {
                $result[$id]['report_period_not_ended'] = 'The report cannot be released before its period has ended.';
            }

            $service_account_id = $report['service_account_id'] ?? null;
            if(!$service_account_id) {
                $result[$id]['missing_service_account'] = 'The report must be linked to a service account.';
                continue;
            }

            $next_day = strtotime('+1 day', (int) $report['date']);
            $entries_after_cutoff = ServiceAccountEntry::search([
                    ['report_id', '=', $id],
                    ['date', '>=', $next_day]
                ], [
                    'limit' => 1
                ])
                ->ids();
            if($entries_after_cutoff) {
                $result[$id]['entry_after_period'] = 'The report contains an entry after its period end.';
            }

            $missing_entries_ids = ServiceAccountEntry::search([
                    ['service_account_id', '=', $service_account_id],
                    ['status', '=', 'pending'],
                    ['report_id', '=', null],
                    ['date', '<', $next_day]
                ], [
                    'limit' => 1
                ])
                ->ids();
            if($missing_entries_ids) {
                $result[$id]['unreported_entries_before_cutoff'] = 'Entries dated before the period end must be assigned to the report.';
            }
        }

        return $result;
    }

    protected static function doRelease($self) {
        $self->read([
            'status',
            'date',
            'service_account_id' => ['id', 'reporting_mode']
        ]);

        foreach($self as $id => $report) {
            $service_account_id = $report['service_account_id']['id'];

            ServiceAccount::id($service_account_id)->do('generate_report');

            self::id($id)
                ->update([
                    'date_from'     => null,
                    'has_lines'     => null,
                    'is_empty'      => null,
                    'total_points'  => null,
                    'total_credits' => null,
                    'balance_old'   => null,
                    'balance_new'   => null,
                    'is_sendable'   => null
                ]);

            $status = (($report['service_account_id']['reporting_mode'] ?? null) === 'archive')
                ? 'archived'
                : 'released';

            try {
                $document = Document::create([
                        'name' => self::computeName($id, $report['date'], 'released') . '.pdf',
                        'data' => \eQual::run('get', 'sale_serviceaccount_Report_render-pdf', ['id' => $id])
                    ])
                    ->first();
            }
            catch(\Exception $e) {
                // silently ignore errors
                trigger_error("APP::unable to generate Report Document", EQ_REPORT_ERROR);
            }

            self::id($id)->update([
                'status'        => $status,
                'document_id'   => $document['id'] ?? null
            ]);

            ServiceAccountEntry::search(['report_id', '=', $id])->update(['status' => 'released']);
            ServiceAccount::id($service_account_id)->do('sync_balance');
        }

    }

    /**
     * Compute the value of the date_from` field: the day following the date_to of the previous report, or 01/01/2023 if none exists.
     * @param \equal\orm\Collection $self
     */
    protected static function calcDateFrom($self) {
        $result = [];
        $self->read([
            'date',
            'service_account_id' => ['id', 'reporting_from', 'date_from']
        ]);

        foreach($self as $id => $report) {
            $previous = self::search([
                    ['service_account_id', '=', $report['service_account_id']['id']],
                    ['status', '<>', 'pending'],
                    ['date', '<', $report['date']]
                ], [
                    'sort'  => ['date' => 'desc'],
                    'limit' => 1
                ])
                ->read(['date'])
                ->first();
            $fallback_date = $report['service_account_id']['reporting_from']
                ?? $report['service_account_id']['date_from']
                ?? null;

            if(!$fallback_date) {
                $fallback_date = $report['date'];
            }

            $result[$id] = ($previous)?(strtotime('+1 day', $previous['date'])):$fallback_date;
        }
        return $result;
    }

    protected static function calcHasLines($self) {
        $result = [];
        $self->read(['service_account_entries_ids']);
        foreach($self as $id => $report) {
            $result[$id] = (bool) count($report['service_account_entries_ids']);
        }
        return $result;
        // return array_map(fn ($a) => (bool) count((array) $a['service_account_entries_ids']), $self->read(['service_account_entries_ids'])->get());
    }

    protected static function calcTotalPoints($self) {
        $result = [];
        $self->read(['service_account_entries_ids' => ['points']]);
        foreach($self as $id => $report) {
            $total_points = 0.0;
            foreach($report['service_account_entries_ids'] as $line) {
                if($line['points'] > 0) {
                    // Service lines consume points and are displayed as negative values on reports.
                    $total_points -= $line['points'];
                }
            }
            $result[$id] = $total_points;
        }
        return $result;
    }


    protected static function calcTotalCredits($self) {
        $result = [];
        $self->read(['service_account_entries_ids' => ['points']]);
        foreach($self as $id => $report) {
            $total_credits = 0.0;
            foreach($report['service_account_entries_ids'] as $line) {
                if($line['points'] < 0) {
                    $total_credits -= $line['points'];
                }
            }
            $result[$id] = $total_credits;
        }
        return $result;
    }

    /**
     * Compute the Service Account Balance before invoicing the lines assigned to the Report.
     * The value of this field should be computed only once and fetched upon creation.
     * #memo - Calculation is triggered upon assignation of the service_account_id.
     */
    protected static function calcBalanceOld($self) {
        $result = [];
        $self->read([
            'date',
            'service_account_id' => ['id', 'balance_effective']
        ]);
        foreach($self as $id => $report) {
            $previous = self::search([
                    ['service_account_id', '=', $report['service_account_id']['id']],
                    ['status', '<>', 'pending'],
                    ['date', '<', $report['date']]
                ], [
                    'sort'  => ['date' => 'desc'],
                    'limit' => 1
                ])
                ->read(['balance_new'])
                ->first();
            // balance_old is the balance_new of the most recent previous report, if any
            $result[$id] = $previous
                ? (float) $previous['balance_new']
                : (float) ($report['service_account_id']['balance_effective'] ?? 0.0);
        }
        return $result;
    }

    /**
     * Compute the Service Account Balance before invoicing the lines assigned to the Report.
     * The value of this field should be computed only once and fetched upon creation.
     * #memo - Calculation is triggered upon assignation of the service_account_id.
     */
    protected static function calcBalanceNew($self) {
        $result = [];
        $self->read(['balance_old', 'total_points', 'total_credits']);
        foreach($self as $id => $report) {
            $result[$id] = round(
                (float) $report['balance_old'] + (float) $report['total_points'] + (float) $report['total_credits'],
                2
            );
        }
        return $result;
    }

    /**
     * Provide the link for generating the PDF version of the Report.
     */
    protected static function calcLink($self) {
        $result = [];
        foreach($self as $id => $report) {
            $result[$id] = '/?get=sale_serviceaccount_Report_render-pdf&id='.$id;
        }
        return $result;
    }

    protected static function calcIsEmpty($self) {
        $result = [];
        $self->read(['service_account_entries_ids']);
        foreach($self as $id => $report) {
            $result[$id] = count($report['service_account_entries_ids']) === 0;
        }
        return $result;
    }

    protected static function calcIsSendable($self) {
        $result = [];
        $self->read([
            'status',
            'date_from',
            'service_account_id' => ['reporting_mode', 'reporting_from']
        ]);
        foreach($self as $id => $report) {
            $service_account = $report['service_account_id'] ?? [];
            $reporting_from = $service_account['reporting_from'] ?? null;

            $result[$id] = $report['status'] === 'released'
                && ($service_account['reporting_mode'] ?? null) === 'send'
                && (!$reporting_from || $report['date_from'] >= $reporting_from);
        }
        return $result;
    }

    protected static function onbeforedelete($self) {
        $self->read(['service_account_id', 'service_account_entries_ids']);
        foreach($self as $id => $report) {
            ServiceAccountEntry::ids($report['service_account_entries_ids'])->update(['report_id' => null]);
        }
    }

}
