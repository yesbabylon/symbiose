<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace sale\booking;

class BankStatementLine extends \sale\pay\BankStatementLine {

    public static function getColumns() {

        return [

            'bank_statement_id' => [
                'type'              => 'many2one',
                'foreign_object'    => BankStatement::getType(),
                'description'       => 'The bank statement the line relates to.'
            ],

            'payments_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => Payment::getType(),
                'foreign_field'     => 'statement_line_id',
                'description'       => 'The list of payments this line relates to .',
                'onupdate'          => 'sale\pay\BankStatementLine::onupdatePaymentsIds',
                'ondetach'          => 'delete'
            ]

        ];
    }

}