<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace finance\accounting;
use equal\orm\Model;

class Journal extends Model {

    public static function getName() {
        return "Accounting journal";
    }

    public static function getDescription() {
        return "An accounting journal groups accounting entries by their nature and routes them to the appropriate accounting category.";
    }

    public static function getColumns() {
        return [

            'name' => [
                'type'              => 'computed',
                'result_type'       => 'string',
                'description'       => 'Label for identifying the journal.',
                'function'          => 'calcName',
                'store'             => true,
                'multilang'         => true
            ],

            'description' => [
                'type'              => 'string',
                'description'       => 'Verbose detail of the role of the journal.',
                'multilang'         => true,
                'dependents'        => ['name']
            ],

            'mnemo' => [
                'type'              => 'string',
                'description'       => 'Short mnemonic used as a prefix in the journal name.',
                'multilang'         => true,
                'dependents'        => ['name']
            ],

            'code' => [
                'type'              => 'string',
                'usage'             => 'text/plain:10',
                'description'       => 'Unique code.',
                'help'              => 'This code identifies the journal within its organization and can be used to match it in an external tool.',
                'required'          => true,
                'dependents'        => ['name']
            ],

            'journal_type' => [
                'type'              => 'string',
                'selection'         => [
                    'LEDG'      => 'General Ledger',
                    'SALE'      => 'Sales',
                    'PURC'      => 'Purchases',
                    'BANK'      => 'Bank',
                    'CASH'      => 'Cash',
                    'PAYR'      => 'Payroll',
                    'ASST'      => 'Fixed Assets',
                    'OPEN'      => 'Opening Balances/Carryforward',
                    'MISC'      => 'Miscellaneous Operations'
                ],
                "required"          => true,
                'description'       => "Type of journal or ledger.",
                'dependents'        => ['name']
            ],

            'organization_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'identity\Organization',
                'description'       => "The organization the journal belongs to.",
                'required'          => true,
                'default'           => 1
            ],

            'is_visible' => [
                'type'              => 'boolean',
                'description'       => 'Whether the journal is visible to users.',
                'default'           => true
            ],

            'accounting_entries_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'finance\accounting\AccountingEntry',
                'foreign_field'     => 'journal_id',
                'description'       => 'Accounting entries relating to the journal.',
                'ondetach'          => 'null',
                'order'             => 'created',
                'sort'              => 'desc'
            ],

            'default_account_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'finance\accounting\Account',
                'description'       => 'Default accounting account used by the journal.',
                'domain'            => [
                    ['organization_id', '=', 'object.organization_id'],
                    ['is_control_account', '=', false]
                ]
            ],

            'has_reversal_sequence' => [
                'type'              => 'boolean',
                'default'           => false,
                'description'       => 'Distinct sequence for operation reversals.'
            ],

            'has_parent' => [
                'type'              => 'computed',
                'result_type'       => 'boolean',
                'description'       => 'Whether the journal is a sub-journal.',
                'function'          => 'calcHasParent',
                'store'             => true
            ],

            'parent_journal_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'finance\accounting\Journal',
                'description'       => 'Parent journal of this sub-journal.',
                'dependents'        => ['has_parent'],
                'domain'            => [
                    ['id', '<>', 'object.id'],
                    ['organization_id', '=', 'object.organization_id'],
                    ['journal_type', '=', 'object.journal_type'],
                    ['has_parent', '=', false]
                ]
            ],

            'sub_journals_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'finance\accounting\Journal',
                'foreign_field'     => 'parent_journal_id',
                'description'       => 'Sub-journals linked to this journal.',
                'ondetach'          => 'null',
                'order'             => 'code',
                'sort'              => 'asc'
            ]

        ];
    }

    protected static function calcHasParent($self) {
        $result = [];
        $self->read(['parent_journal_id']);
        foreach($self as $id => $journal) {
            $result[$id] = !is_null($journal['parent_journal_id']);
        }
        return $result;
    }

    protected static function calcName($self) {
        $result = [];
        $self->read(['mnemo', 'code', 'journal_type', 'description']);
        foreach($self as $id => $journal) {
            $parts = [];
            if($journal['mnemo'] && strlen($journal['mnemo'])) {
                $parts[] = $journal['mnemo'];
            }
            $parts[] = $journal['code'];
            if($journal['description'] && strlen($journal['description'])) {
                $parts[] = $journal['description'];
            }
            $parts[] = $journal['journal_type'];
            $result[$id] = implode(' - ', $parts);
        }
        return $result;
    }

    public function getUniques(): array {
        return [
            ['code', 'organization_id']
        ];
    }

}
