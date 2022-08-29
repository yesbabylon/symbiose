<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace lodging\sale\booking;

class BankStatement extends \sale\booking\BankStatement {

    public static function getColumns() {

        return [
            'name' => [
                'type'              => 'computed',
                'result_type'       => 'string',
                'function'          => 'calcName',
                'store'             => true
            ],

            'statement_lines_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'lodging\sale\booking\BankStatementLine',
                'foreign_field'     => 'bank_statement_id',
                'description'       => 'The lines that are assigned to the statement.'
            ],

            'center_office_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'lodging\identity\CenterOffice',
                'description'       => 'Center office related to the satement (bassed on account number).'
            ]

        ];
    }

    public static function calcName($om, $oids, $lang) {
        $result = [];
        $statements = $om->read(get_called_class(), $oids, ['center_office_id.name', 'date', 'old_balance', 'new_balance']);
        foreach($statements as $oid => $statement) {
            $result[$oid] = sprintf("%s - %s - %s - %s", $statement['center_office_id.name'], date('Ymd', $statement['date']), $statement['old_balance'], $statement['new_balance']);
        }
        return $result;
    }

}