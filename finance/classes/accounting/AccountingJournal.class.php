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
                'type'              => 'string',
                'description'       => 'Label for identifying the journal.',
            ],

            'description' => [
                'type'              => 'string',
                'description'       => 'Verbose detail of the role of the journale.',
            ],

            'code' => [
                'type'              => 'string',
                'description'       => 'Unique code (optional).',
                'unique'            => true
            ],

            'accounting_entries_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => AccountingEntry::getType(),
                'foreign_field'     => 'journal_id',
                'description'       => 'Accounting entries relating to the journal.',
                'ondetach'          => 'null'
            ]

        ];
    }

}