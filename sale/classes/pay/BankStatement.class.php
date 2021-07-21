<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace sale\pay;
use equal\orm\Model;

class BankStatement extends Model {

    public static function getColumns() {

        return [
            'name' => [
                'type'              => 'alias',
                'alias'             => 'reference_name'
            ],
        
            'reference_name' => [
                'type'              => 'string',
                'description'       => 'Reference of the statement.',
                'required'          => true
            ],

            'date' => [
                'type'              => 'date',
                'description'       => 'Date the statement was received.',
                'required'          => true
            ],

            'statement_lines_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'sale\pay\BankStatementLine',
                'foreign_field'     => 'bank_statement_id',
                'description'       => 'The lines that are assigned to the statement.'
            ],

        ];
    }

}