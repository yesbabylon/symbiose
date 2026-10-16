<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2024
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace sale\serviceaccount;

class Report extends \equal\orm\Model {

    private static $status_transition_ids = [];
    private static $deleted_service_account_ids = [];

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

            'pdf_data' => [
                'type'              => 'computed',
                'result_type'       => 'binary',
                'usage'             => 'application/pdf',
                'description'       => 'Generated PDF data for the report.',
                'function'          => 'calcPdfData',
                'store'             => true
            ],

            'is_sendable' => [
                'type'              => 'computed',
                'result_type'       => 'boolean',
                'description'       => 'Flag telling if the Report is ready to be sent according to the app logic.',
                'help'              => 'The Report must be released, use the `send` reporting mode, and not start before `reporting_from`.',
                'function'          => 'calcIsSendable',
                'store'             => true
            ],

            'total_points' => [
                'type'              => 'computed',
                'result_type'       => 'float',
                'store'             => true,
                'function'          => 'calcTotalPoints',
                'description'       => 'Sum of all TT lines (negative value).'
            ],

            'total_credits' => [
                'type'              => 'computed',
                'result_type'       => 'float',
                'store'             => true,
                'function'          => 'calcTotalCredits',
                'description'       => 'Sum of all CC lines.'
            ],

            'balance_old' => [
                'type'              => 'computed',
                'result_type'       => 'float',
                'store'             => true,
                'instant'           => true,
                'function'          => 'calcBalanceOld',
                'description'       => 'Previous balance of the related Service Account (from last previous report).',
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
                'onupdate'          => 'onupdateStatus',
                'dependents'        => ['name', 'is_sendable']
            ]
        ];
    }

    public static function calcName($self) {
        $result = [];
        $self->read(['date', 'status']);
        foreach($self as $id => $report) {
            $result[$id] = sprintf(
                '%d-%04d %s',
                date('Ymd', $report['date']),
                $id,
                $report['status'] === 'pending' ? ' (DRAFT)' : ''
            );
        }

        return $result;
    }

    public static function getActions() {
        return array_merge(parent::getActions(), [
            'release' => [
                'description' => 'Release the report and update its service account.',
                'help'        => 'Only pending reports can be released.',
                'policies'    => [],
                'function'    => 'doRelease'
            ]
        ]);
    }

    protected static function doRelease($self) {
        $self->read([
            'status',
            'date',
            'service_account_id' => ['id', 'reporting_mode']
        ]);

        foreach($self as $id => $report) {
            if($report['status'] !== 'pending') {
                throw new \Exception('already_released_report', EQ_ERROR_NOT_ALLOWED);
            }
            if((int) $report['date'] > strtotime('today')) {
                throw new \Exception('report_period_not_ended', EQ_ERROR_NOT_ALLOWED);
            }
            if(empty($report['service_account_id']['id'])) {
                throw new \Exception('missing_service_account', EQ_ERROR_INVALID_PARAM);
            }
        }

        ['db' => $db_connector] = \eQual::inject(['db']);
        $db = $db_connector->connect();
        if(!$db) {
            throw new \Exception('missing_database', EQ_ERROR_INVALID_CONFIG);
        }

        $db->sendQuery('START TRANSACTION;');
        try {
            foreach($self as $id => $report) {
                $service_account_id = $report['service_account_id']['id'];

                ServiceAccount::id($service_account_id)->do('generate_report', ['manage_transaction' => false]);

                $entries_ids = ServiceAccountEntry::search([
                        ['report_id', '=', $id],
                        ['status', '=', 'pending']
                    ])
                    ->ids();
                if($entries_ids) {
                    ServiceAccountEntry::ids($entries_ids)->do('sync_from_receivable');
                }

                $entries_after_cutoff = ServiceAccountEntry::search([
                        ['report_id', '=', $id],
                        ['date', '>=', strtotime('+1 day', (int) $report['date'])]
                    ], [
                        'limit' => 1
                    ])
                    ->ids();
                if($entries_after_cutoff) {
                    throw new \Exception('entry_after_period', EQ_ERROR_CONFLICT_OBJECT);
                }

                $missing_entries_ids = ServiceAccountEntry::search([
                        ['service_account_id', '=', $service_account_id],
                        ['status', '=', 'pending'],
                        ['report_id', '=', null],
                        ['date', '<', strtotime('+1 day', (int) $report['date'])]
                    ], [
                        'limit' => 1
                    ])
                    ->ids();
                if($missing_entries_ids) {
                    throw new \Exception('unreported_entries_before_cutoff', EQ_ERROR_CONFLICT_OBJECT);
                }

                $final_report = self::id($id)
                    ->update(self::getComputedInvalidation())
                    ->read(['is_empty', 'total_points', 'total_credits', 'balance_old', 'balance_new'])
                    ->first();

                $status = (
                        ($report['service_account_id']['reporting_mode'] ?? null) === 'archive'
                        || ($final_report['is_empty'] ?? false)
                    )
                    ? 'archived'
                    : 'released';

                self::$status_transition_ids[$id] = true;
                self::id($id)->update(['status' => $status]);

                if($entries_ids) {
                    ServiceAccountEntry::ids($entries_ids)->do('release');
                }
                ServiceAccount::id($service_account_id)->do('refresh_balances');
            }
            $db->sendQuery('COMMIT;');
        }
        catch(\Throwable $throwable) {
            $db->sendQuery('ROLLBACK;');
            throw $throwable;
        }
        finally {
            foreach($self->ids() as $id) {
                unset(self::$status_transition_ids[$id]);
            }
        }
    }

    /**
     * Hook invoked upon status change.
     * When a report is released, all SA lines that are attached to it are locked; and the current balance of the related service account is updated.
     * #memo - a reports is generated only once: upon update, a new draft report is created.
     *
     * @param  \equal\orm\Collection    $self       Collection instance.
     * @param  array                    $values     Associative array holding the new values that have been assigned.
     * @return void
     */
    public static function onupdateStatus($self, $values) {
        if(isset($values['status']) && in_array($values['status'], ['released', 'archived', 'sent'])) {
            $self->read(['service_account_id']);
            foreach($self as $report) {
                try {
                    if($report['service_account_id']) {
                        ServiceAccount::id($report['service_account_id'])->do('refresh_balances');
                    }
                }
                catch(\Exception $e) {
                    trigger_error("ORM::error in onupdateStatus - ".$e->getMessage(), EQ_REPORT_ERROR);
                }
            }
        }
    }


    /**
     * Compute the value of the date_from` field: the day following the date_to of the previous report, or 01/01/2023 if none exists.
     * @var \equal\orm\Collection $self
     */
    public static function calcDateFrom($self) {
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

    public static function calcHasLines($self) {
        $result = [];
        $self->read(['service_account_entries_ids']);
        foreach($self as $id => $report) {
            $result[$id] = (bool) count($report['service_account_entries_ids']);
        }
        return $result;
        // return array_map(fn ($a) => (bool) count((array) $a['service_account_entries_ids']), $self->read(['service_account_entries_ids'])->get());
    }

    public static function calcTotalPoints($self) {
        $result = [];
        $self->read(['service_account_entries_ids' => ['points', 'receivable_id' => ['origin_object_class']]]);
        foreach($self as $id => $report) {
            $total_points = 0.0;
            foreach($report['service_account_entries_ids'] as $line) {
                // Time entries consume service account points, which are stored as positive values on entries.
                if(($line['receivable_id']['origin_object_class'] ?? null) === 'timetrack\TimeEntry') {
                    $total_points -= $line['points'];
                }
            }
            $result[$id] = $total_points;
        }
        return $result;
    }


    public static function calcTotalCredits($self) {
        $result = [];
        $self->read(['service_account_entries_ids' => ['points', 'receivable_id' => ['origin_object_class']]]);
        foreach($self as $id => $report) {
            $total_credits = 0.0;
            foreach($report['service_account_entries_ids'] as $line) {
                if(($line['receivable_id']['origin_object_class'] ?? null) !== 'timetrack\TimeEntry') {
                    // Credits and corrections increment the service account balance.
                    $total_credits += $line['points'];
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
    public static function calcBalanceOld($self) {
        $result = [];
        $self->read(['date', 'service_account_id']);
        foreach($self as $id => $report) {
            $previous = self::search([
                    ['service_account_id', '=', $report['service_account_id']],
                    ['status', '<>', 'pending'],
                    ['date', '<', $report['date']]
                ], [
                    'sort'  => ['date' => 'desc'],
                    'limit' => 1
                ])
                ->read(['balance_new'])
                ->first();
            // balance_old is the balance_new of the most recent previous report, if any
            $result[$id] = ($previous)?$previous['balance_new']:0.0;
        }
        return $result;
    }

    /**
     * Compute the Service Account Balance before invoicing the lines assigned to the Report.
     * The value of this field should be computed only once and fetched upon creation.
     * #memo - Calculation is triggered upon assignation of the service_account_id.
     */
    public static function calcBalanceNew($self) {
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
    public static function calcLink($self) {
        $result = [];
        foreach($self as $id => $report) {
            $result[$id] = '/?get=sale_serviceaccount_Report_render-pdf&id='.$id;
        }
        return $result;
    }

    public static function calcIsEmpty($self) {
        $result = [];
        $self->read(['service_account_entries_ids']);
        foreach($self as $id => $report) {
            $result[$id] = count($report['service_account_entries_ids']) === 0;
        }
        return $result;
    }

    public static function calcPdfData($self) {
        $result = [];

        foreach($self as $id => $report) {
            $result[$id] = \eQual::run('get', 'sale_serviceaccount_Report_render-pdf', ['id' => $id]);
        }

        return $result;
    }

    public static function calcIsSendable($self) {
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

    /**
     * Check wether an object can be updated, and perform some additional operations if necessary.
     * This method can be overridden to define a more precise set of tests.
     *
     * @var \equal\orm\Collection $self
     * @return array    Returns an associative array mapping fields with their error messages. An empty array means that object has been successfully processed and can be updated.
     */
    public static function cancreate($self, $values): array {
        if(($values['status'] ?? 'pending') !== 'pending') {
            return ['status' => ['invalid_status' => 'New reports must start in pending status.']];
        }
        if(!empty($values['service_account_id'])) {
            $pending_report = self::search([
                    ['service_account_id', '=', $values['service_account_id']],
                    ['status', '=', 'pending']
                ], [
                    'limit' => 1
                ])
                ->ids();
            if($pending_report) {
                return ['status' => ['second_pending_report' => 'Only one pending report is allowed per service account.']];
            }
        }
        return parent::cancreate($self, $values);
    }

    public static function canupdate($self, $values) {
        $self->read(['status', 'service_account_id', 'service_account_entries_ids' => ['date']]);
        $allowed_on_final = ['pdf_data', 'is_sendable'];
        foreach($self as $id => $report) {
            if($report['status'] !== 'pending' && count(array_diff(array_keys($values), $allowed_on_final)) > 0) {
                return ['status' => ['not_allowed' => 'Released Reports cannot be changed.']];
            }

            if(array_key_exists('status', $values) && !isset(self::$status_transition_ids[$id])) {
                return ['status' => ['non_editable' => 'Report status can only be changed by a workflow action.']];
            }

            if(
                array_key_exists('service_account_id', $values)
                && (int) $values['service_account_id'] !== (int) $report['service_account_id']
            ) {
                if(count($report['service_account_entries_ids'])) {
                    return ['service_account_id' => ['non_editable' => 'A report with entries cannot be moved to another account.']];
                }
                $pending_report = self::search([
                        ['service_account_id', '=', $values['service_account_id']],
                        ['status', '=', 'pending'],
                        ['id', '<>', $id]
                    ], [
                        'limit' => 1
                    ])
                    ->ids();
                if($pending_report) {
                    return ['status' => ['second_pending_report' => 'Only one pending report is allowed per service account.']];
                }
            }

            if(array_key_exists('date', $values)) {
                $next_day = strtotime('+1 day', (int) $values['date']);
                foreach($report['service_account_entries_ids'] as $entry) {
                    if(!empty($entry['date']) && (int) $entry['date'] >= $next_day) {
                        return ['date' => ['entry_after_period' => 'The report contains an entry after the requested period end.']];
                    }
                }
            }
        }
        return parent::canupdate($self, $values);
    }

    public static function candelete($self) {
        $self->read(['status']);
        foreach($self as $report) {
            if($report['status'] != 'pending') {
                return ['status' => ['not_allowed' => 'Only draft Reports can be deleted.']];
            }
        }
        return parent::candelete($self);
    }

    protected static function onbeforedelete($self) {
        $self->read(['service_account_id', 'service_account_entries_ids']);
        foreach($self as $id => $report) {
            self::$deleted_service_account_ids[$id] = $report['service_account_id'];
            $entries_ids = ($report['service_account_entries_ids'] instanceof \equal\orm\Collection)
                ? $report['service_account_entries_ids']->ids()
                : (array) $report['service_account_entries_ids'];
            if($entries_ids) {
                ServiceAccountEntry::ids($entries_ids)->update(['report_id' => null]);
            }
        }
    }

    protected static function onafterdelete($ids) {
        foreach($ids as $id) {
            $service_account_id = self::$deleted_service_account_ids[$id] ?? null;
            if($service_account_id) {
                ServiceAccount::id($service_account_id)->do('refresh_balances');
            }
            unset(self::$deleted_service_account_ids[$id]);
        }
    }

    private static function getComputedInvalidation(): array {
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
