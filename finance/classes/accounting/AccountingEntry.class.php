<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace finance\accounting;

use equal\orm\Model;
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
                'foreign_object'    => 'finance\accounting\AccountingJournal',
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
            ],

            'origin_object_id' => [
                'type'              => 'integer',
                'description'       => 'Object identifier, of `origin_object_class`, he entry originates from.'
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
                'description'       => 'An entry is balanced if the total debited amount equals the total credited amount.',
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
                    'validated',
                    'cancelled'
                ],
                'default'           => 'pending',
                'description'       => 'Status of the accounting entry.',
            ]

        ];
    }

    public static function calcIsBalanced($self) {
        $result = [];
        $self->read(['credit', 'debit']);
        foreach($self as $id => $entry) {
            $result[$id] = ($entry['credit'] === $entry['debit']);
        }
        return $result;
    }

    public static function calcDebit($self) {
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

    public static function calcCredit($self) {
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

    public static function calcEntryNumber($self) {
        $result = [];
        $self->read(['journal_id' => ['code', 'organisation_id']]);

        foreach($self as $id => $entry) {
            if(!isset($entry['journal_id'], $entry['journal_id']['code'], $entry['journal_id']['organisation_id'])) {
                continue;
            }

            $format = Setting::get_value('finance', 'accounting', 'accounting_entry.number_format', '%s{journal}/%02d{year}/%05d{sequence}', ['organisation_id' => $entry['journal_id']['organisation_id']]);
            $year = Setting::get_value('finance', 'accounting', 'fiscal_year', date('Y'), ['organisation_id' => $entry['journal_id']['organisation_id']]);
            $sequence = Setting::fetch_and_add('finance', 'accounting', 'accounting_entry.sequence', 1, ['organisation_id' => $entry['journal_id']['organisation_id']]);

            if($sequence) {
                $result[$id] = Setting::parse_format($format, [
                        'year'      => $year,
                        'journal'   => $entry['journal_id']['code'],
                        'org'       => $entry['journal_id']['organisation_id'],
                        'sequence'  => $sequence
                    ]);
            }

        }
        return $result;
    }
}