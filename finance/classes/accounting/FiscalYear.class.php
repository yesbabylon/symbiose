<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace finance\accounting;

use equal\orm\Model;

class FiscalYear extends Model {

    public static function getName() {
        return 'Fiscal Year';
    }

    public static function getDescription() {
        return 'A fiscal year defines an organization accounting interval and its fiscal periods.';
    }

    public static function getColumns() {
        return [
            'organization_id' => [
                'type'           => 'many2one',
                'foreign_object' => 'identity\Organization',
                'description'    => 'Organization the fiscal year belongs to.',
                'required'       => true,
                'default'        => 1,
                'dependents'     => ['previous_fiscal_year_id']
            ],

            'date_from' => [
                'type'        => 'date',
                'usage'       => 'date/plain',
                'description' => 'First day of the fiscal year, included.',
                'required'    => true,
                'dependents'  => ['name', 'code', 'previous_fiscal_year_id']
            ],

            'date_to' => [
                'type'        => 'date',
                'usage'       => 'date/plain',
                'description' => 'Last day of the fiscal year, included.',
                'required'    => true,
                'dependents'  => ['name', 'code']
            ],

            'fiscal_period_frequency' => [
                'type'        => 'string',
                'selection'   => [
                    'A' => 'Annually',
                    'S' => 'Semi-annually',
                    'T' => 'Every four months',
                    'Q' => 'Quarterly'
                ],
                'description' => 'Frequency used to split the fiscal year into periods.',
                'default'     => 'Q',
                'required'    => true
            ],

            'status' => [
                'type'        => 'string',
                'selection'   => [
                    'draft',
                    'preopen',
                    'open',
                    'preclosed',
                    'closed'
                ],
                'default'     => 'draft',
                'description' => 'Current lifecycle status of the fiscal year.'
            ],

            'fiscal_periods_ids' => [
                'type'           => 'one2many',
                'foreign_object' => 'finance\accounting\FiscalPeriod',
                'foreign_field'  => 'fiscal_year_id',
                'description'    => 'Fiscal periods belonging to the fiscal year.',
                'order'          => 'code',
                'sort'           => 'asc',
                'ondetach'       => 'delete'
            ],

            'previous_fiscal_year_id' => [
                'type'           => 'computed',
                'result_type'    => 'many2one',
                'foreign_object' => 'finance\accounting\FiscalYear',
                'function'       => 'calcPreviousFiscalYearId',
                'store'          => true,
                'description'    => 'Immediately preceding fiscal year for the same organization.'
            ],

            'name' => [
                'type'        => 'computed',
                'result_type' => 'string',
                'function'    => 'calcName',
                'store'       => true,
                'description' => 'Human-readable fiscal year label.'
            ],

            'code' => [
                'type'        => 'computed',
                'result_type' => 'string',
                'function'    => 'calcCode',
                'store'       => true,
                'description' => 'Stable fiscal year code derived from its start and end years.'
            ]
        ];
    }

    public function getUniques(): array {
        return [
            ['organization_id', 'date_from', 'date_to']
        ];
    }

    public static function getPolicies(): array {
        return array_merge(parent::getPolicies(), [
            'is_valid' => [
                'description' => 'Check that the fiscal year dates are valid and do not overlap another fiscal year.',
                'function'    => 'policyIsValid'
            ],
            'can_generate_periods' => [
                'description' => 'Check that periods can be regenerated.',
                'function'    => 'policyCanGeneratePeriods'
            ],
            'can_open' => [
                'description' => 'Check that the fiscal year is ready to be opened.',
                'function'    => 'policyCanOpen'
            ],
            'can_preclose' => [
                'description' => 'Check that the fiscal year can be pre-closed.',
                'function'    => 'policyCanPreclose'
            ],
            'can_close' => [
                'description' => 'Check that the fiscal year can be closed.',
                'function'    => 'policyCanClose'
            ],
            'can_repreclose' => [
                'description' => 'Check that the latest closed fiscal year can be returned to pre-closed status.',
                'function'    => 'policyCanRePreclose'
            ],
            'is_editable' => [
                'description' => 'Check that the fiscal year is still a draft.',
                'function'    => 'policyIsEditable'
            ]
        ]);
    }

    public static function getOperationPolicies(): array {
        return array_replace_recursive(parent::getOperationPolicies(), [
            EQ_R_UPDATE => [
                'organization_id'         => ['is_editable'],
                'date_from'               => ['is_editable'],
                'date_to'                 => ['is_editable'],
                'fiscal_period_frequency' => ['is_editable'],
                'fiscal_periods_ids'      => ['is_editable']
            ],
            EQ_R_DELETE => [
                '*' => ['is_editable']
            ]
        ]);
    }

    public static function getActions() {
        return [
            'generate_periods' => [
                'description' => 'Regenerate fiscal periods according to the fiscal year dates and frequency.',
                'policies'    => ['is_valid', 'can_generate_periods'],
                'function'    => 'doGeneratePeriods'
            ]
        ];
    }

    public static function getWorkflow() {
        return [
            'draft' => [
                'description' => 'Fiscal year being configured.',
                'icon'        => 'draw',
                'transitions' => [
                    'preopen' => [
                        'description' => 'Validate the calendar and generate the fiscal periods.',
                        'policies'    => ['is_valid'],
                        'onbefore'    => 'onbeforePreopen',
                        'status'      => 'preopen'
                    ]
                ]
            ],
            'preopen' => [
                'description' => 'Fiscal year ready to be opened.',
                'icon'        => 'pending',
                'transitions' => [
                    'open' => [
                        'description' => 'Open the fiscal year for accounting.',
                        'policies'    => ['is_valid', 'can_open'],
                        'status'      => 'open'
                    ],
                    'revert' => [
                        'description' => 'Return the fiscal year to draft for calendar changes.',
                        'status'      => 'draft'
                    ]
                ]
            ],
            'open' => [
                'description' => 'Fiscal year open for accounting.',
                'icon'        => 'lock_open',
                'transitions' => [
                    'preclose' => [
                        'description' => 'Pre-close the fiscal year after all periods have been pre-closed or closed.',
                        'policies'    => ['can_preclose'],
                        'status'      => 'preclosed'
                    ],
                    'close' => [
                        'description' => 'Close the fiscal year after all periods have been closed.',
                        'policies'    => ['can_close'],
                        'status'      => 'closed'
                    ]
                ]
            ],
            'preclosed' => [
                'description' => 'Fiscal year awaiting final closure.',
                'icon'        => 'hourglass_top',
                'transitions' => [
                    'reopen' => [
                        'description' => 'Return the fiscal year to open status.',
                        'status'      => 'open'
                    ],
                    'close' => [
                        'description' => 'Close the fiscal year after all periods have been closed.',
                        'policies'    => ['can_close'],
                        'status'      => 'closed'
                    ]
                ]
            ],
            'closed' => [
                'description' => 'Closed fiscal year.',
                'icon'        => 'lock',
                'transitions' => [
                    'repreclose' => [
                        'description' => 'Return the latest fiscal year to pre-closed status.',
                        'policies'    => ['can_repreclose'],
                        'status'      => 'preclosed'
                    ]
                ]
            ]
        ];
    }

    protected static function calcName($self): array {
        $result = [];
        $self->read(['date_from', 'date_to']);

        foreach($self as $id => $fiscalYear) {
            if(!$fiscalYear['date_from'] || !$fiscalYear['date_to']) {
                continue;
            }

            $year_from = date('Y', $fiscalYear['date_from']);
            $year_to = date('Y', $fiscalYear['date_to']);
            $result[$id] = $year_from === $year_to ? $year_from : "{$year_from}-{$year_to}";
        }

        return $result;
    }

    protected static function calcCode($self): array {
        $result = [];
        $self->read(['date_from', 'date_to']);

        foreach($self as $id => $fiscalYear) {
            if(!$fiscalYear['date_from'] || !$fiscalYear['date_to']) {
                continue;
            }

            $year_from = date('Y', $fiscalYear['date_from']);
            $year_to = date('Y', $fiscalYear['date_to']);
            $result[$id] = substr($year_from, -2)
                . ($year_from === $year_to ? '' : '-' . substr($year_to, -2));
        }

        return $result;
    }

    protected static function calcPreviousFiscalYearId($self): array {
        $result = [];
        $self->read(['organization_id', 'date_from']);

        foreach($self as $id => $fiscalYear) {
            if(!$fiscalYear['organization_id'] || !$fiscalYear['date_from']) {
                continue;
            }

            $previous = self::search([
                    ['organization_id', '=', $fiscalYear['organization_id']],
                    ['date_to', '=', strtotime('-1 day', $fiscalYear['date_from'])],
                    ['id', '<>', $id]
                ])
                ->first();

            if($previous) {
                $result[$id] = $previous['id'];
            }
        }

        return $result;
    }

    protected static function policyIsValid($self): array {
        $result = [];
        $self->read(['organization_id', 'date_from', 'date_to']);

        foreach($self as $id => $fiscalYear) {
            $errors = [];

            if(!$fiscalYear['organization_id']) {
                $errors['missing_organization'] = 'Organization is required.';
            }
            if(!$fiscalYear['date_from']) {
                $errors['missing_date_from'] = 'Start date is required.';
            }
            if(!$fiscalYear['date_to']) {
                $errors['missing_date_to'] = 'End date is required.';
            }
            if($fiscalYear['date_from'] && $fiscalYear['date_to'] && $fiscalYear['date_from'] > $fiscalYear['date_to']) {
                $errors['invalid_date_range'] = 'Start date must be before or equal to end date.';
            }

            if(!$errors && self::search([
                    ['organization_id', '=', $fiscalYear['organization_id']],
                    ['date_from', '<=', $fiscalYear['date_to']],
                    ['date_to', '>=', $fiscalYear['date_from']],
                    ['id', '<>', $id]
                ])->count() > 0
            ) {
                $errors['overlapping_fiscal_year'] = 'Fiscal year dates overlap another fiscal year for the organization.';
            }

            if($errors) {
                $result[$id] = $errors;
            }
        }

        return $result;
    }

    protected static function policyCanGeneratePeriods($self): array {
        $result = [];
        $self->read(['status', 'fiscal_periods_ids' => ['status']]);

        foreach($self as $id => $fiscalYear) {
            if($fiscalYear['status'] !== 'draft') {
                $result[$id] = [
                    'fiscal_year_not_draft' => 'Periods can only be regenerated for a draft fiscal year.'
                ];
                continue;
            }

            foreach($fiscalYear['fiscal_periods_ids'] as $fiscalPeriod) {
                if($fiscalPeriod['status'] !== 'open') {
                    $result[$id] = [
                        'non_open_fiscal_period' => 'A non-open fiscal period prevents period regeneration.'
                    ];
                    break;
                }
            }
        }

        return $result;
    }

    protected static function policyIsEditable($self): array {
        $result = [];
        $self->read(['status']);

        foreach($self as $id => $fiscalYear) {
            if($fiscalYear['status'] !== 'draft') {
                $result[$id] = [
                    'fiscal_year_not_editable' => 'Only a draft fiscal year can be changed or deleted.'
                ];
            }
        }

        return $result;
    }

    protected static function policyCanOpen($self): array {
        $result = [];
        $self->read([
            'status',
            'date_from',
            'date_to',
            'fiscal_periods_ids' => ['code', 'date_from', 'date_to']
        ]);

        foreach($self as $id => $fiscalYear) {
            $errors = self::validateFiscalPeriods($fiscalYear);
            if($fiscalYear['status'] !== 'preopen') {
                $errors['invalid_status'] = 'Fiscal year must be pre-opened before it can be opened.';
            }
            if($errors) {
                $result[$id] = $errors;
            }
        }

        return $result;
    }

    protected static function policyCanPreclose($self): array {
        $result = [];
        $self->read(['status', 'fiscal_periods_ids' => ['status']]);

        foreach($self as $id => $fiscalYear) {
            $errors = [];
            if($fiscalYear['status'] !== 'open') {
                $errors['invalid_status'] = 'Only an open fiscal year can be pre-closed.';
            }
            if($fiscalYear['fiscal_periods_ids']->count() === 0) {
                $errors['missing_fiscal_periods'] = 'At least one fiscal period is required.';
            }
            foreach($fiscalYear['fiscal_periods_ids'] as $fiscalPeriod) {
                if(!in_array($fiscalPeriod['status'], ['preclosed', 'closed'], true)) {
                    $errors['invalid_period_status'] = 'All fiscal periods must be pre-closed or closed.';
                    break;
                }
            }
            if($errors) {
                $result[$id] = $errors;
            }
        }

        return $result;
    }

    protected static function policyCanClose($self): array {
        $result = [];
        $self->read(['status', 'fiscal_periods_ids' => ['status']]);

        foreach($self as $id => $fiscalYear) {
            $errors = [];
            if(!in_array($fiscalYear['status'], ['open', 'preclosed'], true)) {
                $errors['invalid_status'] = 'Only an open or pre-closed fiscal year can be closed.';
            }
            if($fiscalYear['fiscal_periods_ids']->count() === 0) {
                $errors['missing_fiscal_periods'] = 'At least one fiscal period is required.';
            }
            foreach($fiscalYear['fiscal_periods_ids'] as $fiscalPeriod) {
                if($fiscalPeriod['status'] !== 'closed') {
                    $errors['invalid_period_status'] = 'All fiscal periods must be closed.';
                    break;
                }
            }
            if($errors) {
                $result[$id] = $errors;
            }
        }

        return $result;
    }

    protected static function policyCanRePreclose($self): array {
        $result = [];
        $self->read(['status', 'organization_id', 'date_to']);

        foreach($self as $id => $fiscalYear) {
            if($fiscalYear['status'] !== 'closed') {
                $result[$id] = [
                    'invalid_status' => 'Only a closed fiscal year can be returned to pre-closed status.'
                ];
                continue;
            }

            if(self::search([
                    ['organization_id', '=', $fiscalYear['organization_id']],
                    ['date_from', '>', $fiscalYear['date_to']],
                    ['status', '=', 'closed']
                ])->count() > 0
            ) {
                $result[$id] = [
                    'later_closed_fiscal_year' => 'A later closed fiscal year prevents reopening this fiscal year.'
                ];
            }
        }

        return $result;
    }

    protected static function onbeforePreopen($self) {
        $self->do('generate_periods');
    }

    protected static function doGeneratePeriods($self) {
        $self->read(['date_from', 'date_to', 'fiscal_period_frequency']);

        foreach($self as $id => $fiscalYear) {
            FiscalPeriod::search([['fiscal_year_id', '=', $id]])->delete(true);

            $periods = self::computeFiscalPeriods(
                $fiscalYear['date_from'],
                $fiscalYear['date_to'],
                $fiscalYear['fiscal_period_frequency']
            );

            foreach($periods as $index => $period) {
                FiscalPeriod::create([
                    'fiscal_year_id' => $id,
                    'code'           => $index + 1,
                    'date_from'      => $period['date_from'],
                    'date_to'        => $period['date_to']
                ]);
            }
        }
    }

    private static function computeFiscalPeriods($date_from, $date_to, $frequency): array {
        $months_by_frequency = [
            'Q' => 3,
            'T' => 4,
            'S' => 6,
            'A' => 12
        ];

        if(!isset($months_by_frequency[$frequency])) {
            return [];
        }

        $periods = [];
        $period_from = $date_from;
        $months = $months_by_frequency[$frequency];

        while($period_from <= $date_to) {
            $next_period_from = strtotime("+{$months} months", $period_from);
            $period_to = min(strtotime('-1 day', $next_period_from), $date_to);

            $periods[] = [
                'date_from' => $period_from,
                'date_to'   => $period_to
            ];

            $period_from = strtotime('+1 day', $period_to);
        }

        return $periods;
    }

    private static function validateFiscalPeriods($fiscalYear): array {
        $errors = [];

        if($fiscalYear['fiscal_periods_ids']->count() === 0) {
            return [
                'missing_fiscal_periods' => 'At least one fiscal period is required.'
            ];
        }

        $expected_date_from = $fiscalYear['date_from'];
        foreach($fiscalYear['fiscal_periods_ids'] as $fiscalPeriod) {
            if($fiscalPeriod['date_from'] !== $expected_date_from || $fiscalPeriod['date_to'] < $fiscalPeriod['date_from']) {
                $errors['invalid_fiscal_period_dates'] = 'Fiscal periods must be valid, contiguous and ordered.';
                break;
            }
            $expected_date_from = strtotime('+1 day', $fiscalPeriod['date_to']);
        }

        if(!$errors && $expected_date_from !== strtotime('+1 day', $fiscalYear['date_to'])) {
            $errors['incomplete_fiscal_period_coverage'] = 'Fiscal periods must cover the whole fiscal year.';
        }

        return $errors;
    }
}
