<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace finance\accounting;

use equal\orm\Model;

class FiscalPeriod extends Model {

    public static function getName() {
        return 'Fiscal Period';
    }

    public static function getDescription() {
        return 'A fiscal period is a contiguous accounting interval within a fiscal year.';
    }

    public static function getColumns() {
        return [
            'fiscal_year_id' => [
                'type'           => 'many2one',
                'foreign_object' => 'finance\accounting\FiscalYear',
                'description'    => 'Fiscal year the period belongs to.',
                'required'       => true,
                'ondelete'       => 'cascade',
                'dependents'     => ['organization_id', 'fiscal_year_status']
            ],

            'code' => [
                'type'        => 'integer',
                'description' => 'Sequential position of the period within its fiscal year.',
                'required'    => true,
                'readonly'    => true,
                'dependents'  => ['name']
            ],

            'date_from' => [
                'type'        => 'date',
                'usage'       => 'date/plain',
                'description' => 'First day of the fiscal period, included.',
                'required'    => true,
                'dependents'  => ['name']
            ],

            'date_to' => [
                'type'        => 'date',
                'usage'       => 'date/plain',
                'description' => 'Last day of the fiscal period, included.',
                'required'    => true,
                'dependents'  => ['name']
            ],

            'status' => [
                'type'        => 'string',
                'selection'   => [
                    'open',
                    'preclosed',
                    'closed'
                ],
                'default'     => 'open',
                'description' => 'Current lifecycle status of the fiscal period.'
            ],

            'organization_id' => [
                'type'           => 'computed',
                'result_type'    => 'many2one',
                'foreign_object' => 'identity\Organization',
                'relation'       => ['fiscal_year_id' => 'organization_id'],
                'store'          => false,
                'description'    => 'Organization inherited from the fiscal year.'
            ],

            'fiscal_year_status' => [
                'type'        => 'computed',
                'result_type' => 'string',
                'relation'    => ['fiscal_year_id' => 'status'],
                'store'       => false,
                'description' => 'Current status of the parent fiscal year.'
            ],

            'name' => [
                'type'        => 'computed',
                'result_type' => 'string',
                'function'    => 'calcName',
                'store'       => true,
                'description' => 'Human-readable fiscal period label.'
            ]
        ];
    }

    public function getUniques(): array {
        return [
            ['fiscal_year_id', 'code']
        ];
    }

    public static function getPolicies(): array {
        return array_merge(parent::getPolicies(), [
            'can_preclose' => [
                'description' => 'Check that the fiscal period can be pre-closed.',
                'function'    => 'policyCanPreclose'
            ],
            'can_close' => [
                'description' => 'Check that the fiscal period can be closed.',
                'function'    => 'policyCanClose'
            ],
            'can_reopen' => [
                'description' => 'Check that the fiscal period can be reopened in sequence.',
                'function'    => 'policyCanReopen'
            ],
            'is_editable' => [
                'description' => 'Check that the parent fiscal year is still a draft.',
                'function'    => 'policyIsEditable'
            ]
        ]);
    }

    public static function getOperationPolicies(): array {
        return array_replace_recursive(parent::getOperationPolicies(), [
            EQ_R_UPDATE => [
                'fiscal_year_id' => ['is_editable'],
                'code'           => ['is_editable'],
                'date_from'      => ['is_editable'],
                'date_to'        => ['is_editable']
            ],
            EQ_R_DELETE => [
                '*' => ['is_editable']
            ]
        ]);
    }

    public static function getWorkflow() {
        return [
            'open' => [
                'description' => 'Fiscal period open for accounting.',
                'icon'        => 'lock_open',
                'transitions' => [
                    'preclose' => [
                        'description' => 'Pre-close the fiscal period.',
                        'policies'    => ['can_preclose'],
                        'status'      => 'preclosed'
                    ],
                    'close' => [
                        'description' => 'Close the fiscal period.',
                        'policies'    => ['can_close'],
                        'status'      => 'closed'
                    ]
                ]
            ],
            'preclosed' => [
                'description' => 'Fiscal period awaiting final closure.',
                'icon'        => 'hourglass_top',
                'transitions' => [
                    'reopen' => [
                        'description' => 'Return the fiscal period to open status.',
                        'policies'    => ['can_reopen'],
                        'status'      => 'open'
                    ],
                    'close' => [
                        'description' => 'Close the fiscal period.',
                        'policies'    => ['can_close'],
                        'status'      => 'closed'
                    ]
                ]
            ],
            'closed' => [
                'description' => 'Closed fiscal period.',
                'icon'        => 'lock',
                'transitions' => [
                    'repreclose' => [
                        'description' => 'Return the fiscal period to pre-closed status.',
                        'policies'    => ['can_reopen'],
                        'status'      => 'preclosed'
                    ]
                ]
            ]
        ];
    }

    protected static function calcName($self): array {
        $result = [];
        $self->read(['code', 'date_from', 'date_to']);

        foreach($self as $id => $fiscalPeriod) {
            if(!$fiscalPeriod['date_from'] || !$fiscalPeriod['date_to']) {
                continue;
            }

            $result[$id] = $fiscalPeriod['code']
                . ' - '
                . date('Y-m-d', $fiscalPeriod['date_from'])
                . ' - '
                . date('Y-m-d', $fiscalPeriod['date_to']);
        }

        return $result;
    }

    protected static function policyCanPreclose($self): array {
        $result = [];
        $self->read(['status', 'date_from', 'fiscal_year_id' => ['status']]);

        foreach($self as $id => $fiscalPeriod) {
            $errors = [];
            if($fiscalPeriod['status'] !== 'open') {
                $errors['invalid_status'] = 'Only an open fiscal period can be pre-closed.';
            }
            if(!$fiscalPeriod['fiscal_year_id'] || $fiscalPeriod['fiscal_year_id']['status'] !== 'open') {
                $errors['invalid_fiscal_year_status'] = 'The parent fiscal year must be open.';
            }
            if(self::hasNonClosedPreviousPeriod($id, $fiscalPeriod)) {
                $errors['non_closed_previous_period'] = 'All previous fiscal periods must be closed first.';
            }
            if($errors) {
                $result[$id] = $errors;
            }
        }

        return $result;
    }

    protected static function policyIsEditable($self): array {
        $result = [];
        $self->read(['fiscal_year_id' => ['status']]);

        foreach($self as $id => $fiscalPeriod) {
            if(!$fiscalPeriod['fiscal_year_id'] || $fiscalPeriod['fiscal_year_id']['status'] !== 'draft') {
                $result[$id] = [
                    'fiscal_period_not_editable' => 'A fiscal period can only be changed or deleted while its fiscal year is a draft.'
                ];
            }
        }

        return $result;
    }

    protected static function policyCanClose($self): array {
        $result = [];
        $self->read(['status', 'date_from', 'fiscal_year_id' => ['status']]);

        foreach($self as $id => $fiscalPeriod) {
            $errors = [];
            if(!in_array($fiscalPeriod['status'], ['open', 'preclosed'], true)) {
                $errors['invalid_status'] = 'Only an open or pre-closed fiscal period can be closed.';
            }
            if(
                !$fiscalPeriod['fiscal_year_id']
                || !in_array($fiscalPeriod['fiscal_year_id']['status'], ['open', 'preclosed'], true)
            ) {
                $errors['invalid_fiscal_year_status'] = 'The parent fiscal year must be open or pre-closed.';
            }
            if(self::hasNonClosedPreviousPeriod($id, $fiscalPeriod)) {
                $errors['non_closed_previous_period'] = 'All previous fiscal periods must be closed first.';
            }
            if($errors) {
                $result[$id] = $errors;
            }
        }

        return $result;
    }

    protected static function policyCanReopen($self): array {
        $result = [];
        $self->read(['status', 'date_to', 'fiscal_year_id' => ['status']]);

        foreach($self as $id => $fiscalPeriod) {
            $errors = [];
            if(!in_array($fiscalPeriod['status'], ['preclosed', 'closed'], true)) {
                $errors['invalid_status'] = 'Only a pre-closed or closed fiscal period can be reopened.';
            }
            if(
                !$fiscalPeriod['fiscal_year_id']
                || !in_array($fiscalPeriod['fiscal_year_id']['status'], ['open', 'preclosed'], true)
            ) {
                $errors['invalid_fiscal_year_status'] = 'The parent fiscal year must be open or pre-closed.';
            }
            if($fiscalPeriod['fiscal_year_id'] && self::search([
                    ['fiscal_year_id', '=', $fiscalPeriod['fiscal_year_id']['id']],
                    ['date_from', '>', $fiscalPeriod['date_to']],
                    ['status', '<>', 'open'],
                    ['id', '<>', $id]
                ])->count() > 0
            ) {
                $errors['non_open_next_period'] = 'All following fiscal periods must be open first.';
            }
            if($errors) {
                $result[$id] = $errors;
            }
        }

        return $result;
    }

    private static function hasNonClosedPreviousPeriod($id, $fiscalPeriod): bool {
        if(!$fiscalPeriod['fiscal_year_id']) {
            return false;
        }

        return self::search([
                ['fiscal_year_id', '=', $fiscalPeriod['fiscal_year_id']['id']],
                ['date_to', '<', $fiscalPeriod['date_from']],
                ['status', '<>', 'closed'],
                ['id', '<>', $id]
            ])->count() > 0;
    }
}
