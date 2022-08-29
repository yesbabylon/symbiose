<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace sale\booking;

class BankStatement extends \sale\pay\BankStatement {

    public static function getColumns() {

        return [

            'statement_lines_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'sale\booking\BankStatementLine',
                'foreign_field'     => 'bank_statement_id',
                'description'       => 'The lines that are assigned to the statement.'
            ]

        ];
    }

}