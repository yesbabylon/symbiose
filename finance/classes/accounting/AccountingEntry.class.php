<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace finance\accounting;

use equal\orm\Model;
use finance\accounting\operation\AccountingOperation;
use symbiose\setting\Setting;

class AccountingEntry extends Model {

    public static function getName() {
        return "Journal accounting entry";
    }

    public static function getDescription() {
        return "Accounting entries convert invoice lines into records of financial transactions in the accounting books.";
    }

    public static function getColumns() {
        return [

            'journal_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'finance\accounting\Journal',
                'description'       => "Accounting journal the entry relates to.",
                'required'          => true
            ],

            'entry_number' => [
                'type'              => 'computed',
                'result_type'       => 'string',
                'function'          => 'calcEntryNumber',
                'store'             => true
            ],

            'origin_object_class' => [
                'type'              => 'string',
                'description'       => 'Entity class that the entry originates from.',
                'help'              => 'Accounting operation class, stored with its full namespace.',
            ],

            'origin_object_id' => [
                'type'              => 'integer',
                'description'       => 'Identifier of the object from which the entry originates.',
                'help'              => 'Together with origin_object_class, this identifies the accounting operation linked to the entry.'
            ],

            'entry_reference' => [
                'type'              => 'computed',
                'result_type'       => 'string',
                'function'          => 'calcEntryReference',
                'description'       => 'Number of the accounting operation linked to the entry.',
                'readonly'          => true
            ],

            'debit' => [
                'type'              => 'computed',
                'result_type'       => 'float',
                'usage'             => 'amount/money:4',
                'description'       => 'Total debited amount from all lines.',
                'function'          => 'calcDebit',
                'store'             => true
            ],

            'credit' => [
                'type'              => 'computed',
                'result_type'       => 'float',
                'usage'             => 'amount/money:4',
                'description'       => 'Total credited amount from all lines.',
                'function'          => 'calcCredit',
                'store'             => true
            ],

            'is_balanced' => [
                'type'              => 'computed',
                'result_type'       => 'boolean',
                'description'       => 'Whether total debits equal total credits.',
                'function'          => 'calcIsBalanced'
            ],

            'entry_lines_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'finance\accounting\AccountingEntryLine',
                'foreign_field'     => 'accounting_entry_id',
                'description'       => "Lines of the accounting entry.",
                'dependents'        => ['debit', 'credit']
            ],

            'status' => [
                'type'              => 'string',
                'selection'         => [
                    'pending',
                    'posted',
                    'reversed'
                ],
                'default'           => 'pending',
                'description'       => 'Status of the accounting entry.',
                'dependents'        => ['entry_reference']
            ]

        ];
    }

    public static function getPolicies(): array {
        return array_merge(parent::getPolicies(), [
            'can_cancel' => [
                'description' => 'Check whether the originating accounting operation can be cancelled.',
                'function'    => 'policyCanCancel'
            ]
        ]);
    }

    public static function getActions() {
        return array_merge(parent::getActions(), [
            'cancel' => [
                'description' => 'Cancel the originating accounting operation and reverse its accounting entries.',
                'policies'    => ['can_cancel'],
                'function'    => 'doCancel'
            ]
        ]);
    }

    protected static function calcEntryReference($self): array {
        $result = [];
        $operations = [];

        $self->read([
            'status',
            'origin_object_class',
            'origin_object_id'
        ]);

        foreach($self as $id => $entry) {

            if(!in_array($entry['status'], ['posted', 'reversed'], true)) {
                continue;
            }

            $origin_class = $entry['origin_object_class'] ?? null;
            $origin_id = (int) ($entry['origin_object_id'] ?? 0);

            if($origin_class && $origin_id > 0) {
                $operations[(string) $origin_class][$origin_id][] = $id;
            }
        }

        foreach($operations as $origin_class => $operation_entries) {
            if(!class_exists($origin_class) || !is_a($origin_class, AccountingOperation::class, true)) {
                continue;
            }


            $origin_operations = $origin_class::ids(array_keys($operation_entries))
                ->read(['operation_number']);

            foreach($origin_operations as $operation_id => $operation) {
                foreach($operation_entries[$operation_id] as $entry_id) {
                    $result[$entry_id] = $operation['operation_number'];
                }
            }
        }

        return $result;
    }

    protected static function policyCanCancel($self, $orm): array {
        $result = [];
        $operations = [];

        $self->read([
            'status',
            'origin_object_class',
            'origin_object_id'
        ]);

        foreach($self as $id => $entry) {
            if($entry['status'] !== 'posted') {
                $result[$id]['invalid_status'] = 'Only a posted accounting entry can be cancelled.';
                continue;
            }

            $origin_class = $entry['origin_object_class'] ?? null;
            $origin_id = (int) ($entry['origin_object_id'] ?? 0);

            if(!$origin_class || $origin_id <= 0) {
                $result[$id]['missing_origin_operation'] = 'The accounting entry is not linked to an accounting operation.';
                continue;
            }

            if(!class_exists($origin_class) || !is_a($origin_class, AccountingOperation::class, true)) {
                $result[$id]['invalid_origin_operation'] = 'The accounting entry origin is not an accounting operation.';
                continue;
            }

            $operations[(string) $origin_class][$origin_id][] = $id;
        }

        foreach($operations as $origin_class => $operation_entries) {
            $transition_errors = $orm->canTransition(
                $origin_class,
                array_keys($operation_entries),
                'cancel'
            );

            if(is_array($transition_errors) && count($transition_errors) === 0) {
                continue;
            }

            foreach($operation_entries as $entry_ids) {
                foreach($entry_ids as $entry_id) {
                    $result[$entry_id]['origin_operation_not_cancellable'] = 'The originating accounting operation cannot be cancelled.';
                }
            }
        }

        return $result;
    }

    protected static function doCancel($self) {
        $operations = [];

        $self->read([
            'origin_object_class',
            'origin_object_id'
        ]);

        foreach($self as $entry) {
            if(!$entry['origin_object_class'] || $entry['origin_object_id']) {
                continue;
            }
            $operations[$entry['origin_object_class']][$entry['origin_object_id']] = true;
        }

        foreach($operations as $origin_class => $operation_ids) {
            $origin_class::ids(array_keys($operation_ids))
                ->transition('cancel');
        }
    }

    protected static function calcIsBalanced($self) {
        $result = [];
        $self->read(['credit', 'debit']);
        foreach($self as $id => $entry) {
            $result[$id] = ($entry['credit'] === $entry['debit']);
        }
        return $result;
    }

    protected static function calcDebit($self) {
        $result = [];
        $self->read(['entry_lines_ids' => ['debit']]);
        foreach($self as $id => $entry) {
            $result[$id] = 0.0;
            foreach($entry['entry_lines_ids'] as $line) {
                $result[$id] += $line['debit'];
            }
        }
        return $result;
    }

    protected static function calcCredit($self) {
        $result = [];
        $self->read(['entry_lines_ids' => ['credit']]);
        foreach($self as $id => $entry) {
            $result[$id] = 0.0;
            foreach($entry['entry_lines_ids'] as $line) {
                $result[$id] += $line['credit'];
            }
        }
        return $result;
    }

    protected static function calcEntryNumber($self) {
        $result = [];
        $self->read(['journal_id' => ['code', 'organization_id']]);

        foreach($self as $id => $entry) {
            if(!isset($entry['journal_id'], $entry['journal_id']['code'], $entry['journal_id']['organization_id'])) {
                continue;
            }

            $format = Setting::get_value('finance', 'accounting', 'entry.number_format', '%s{journal}/%02d{year}/%05d{sequence}', ['organization_id' => $entry['journal_id']['organization_id']]);
            $year = Setting::get_value('finance', 'accounting', 'fiscal_year', date('Y'), ['organization_id' => $entry['journal_id']['organization_id']]);
            $sequence = Setting::fetch_and_add('finance', 'accounting', 'entry.sequence', 1, ['organization_id' => $entry['journal_id']['organization_id']]);

            if($sequence) {
                $result[$id] = Setting::parse_format($format, [
                        'year'      => $year,
                        'journal'   => $entry['journal_id']['code'],
                        'org'       => $entry['journal_id']['organization_id'],
                        'sequence'  => $sequence
                    ]);
            }

        }
        return $result;
    }
}
