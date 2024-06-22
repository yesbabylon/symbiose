<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace finance\accounting;
use equal\orm\Model;

class AccountingJournal extends Model {

    public static function getName() {
        return "Accounting journal";
    }

    public static function getDescription() {
        return "An accounting journal is a list of accounting entries grouped by their nature.";
    }

    public static function getColumns() {
        return [

            'name' => [
                'type'              => 'computed',
                'result_type'       => 'string',
                'description'       => 'Label for identifying the journal.',
                'function'          => 'calcName',
                'store'             => true
            ],

            'description' => [
                'type'              => 'string',
                'description'       => 'Verbose detail of the role of the journal.',
                'multilang'         => true
            ],

            'code' => [
                'type'              => 'string',
                'description'       => 'Unique code (optional).',
                'help'              => 'Additional code to match journal in an external tool.',
                'unique'            => true
            ],

            'journal_type' => [
                'type'              => 'string',
                'selection'         => [
                    'LEDG'      => 'General Ledger',
                    'SALE'      => 'Sales',
                    'PURC'      => 'Purchases',
                    'CASH'      => 'Bank & Cash',
                    'PAYR'      => 'Payroll',
                    'ASST'      => 'Fixed Assets',
                    'MISC'      => 'General (miscellaneous)'
                ],
                "required"          => true,
                'description'       => "Type of journal or ledger."
            ],

            'organisation_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'identity\Organisation',
                'description'       => "The organisation the journal belongs to.",
                'default'           => 1
            ],

            'accounting_entries_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'finance\accounting\AccountingEntry',
                'foreign_field'     => 'journal_id',
                'description'       => 'Accounting entries relating to the journal.',
                'ondetach'          => 'null',
                'order'             => 'created',
                'sort'              => 'desc'
            ]

        ];
    }

    public static function calcName($self) {
        $result = [];
        $self->read(['code', 'description']);
        foreach($self as $id => $journal) {
            $result[$id] = $journal['description'].' ('.$journal['code'].')';
        }
        return $result;
    }

}