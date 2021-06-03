<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace finance\accounting;
use equal\orm\Model;

class AccountingRule extends Model {
    
    public static function getName() {
        return "Accounting Rule";
    }

    public static function getDescription() {
        return "Accounting rules allow to specify on which account an operation is to be imputed.";
    }

    public static function getColumns() {

        return [
            'name' => [
                'type'              => 'string',
                'description'       => "Name of the accounting rule.",
                'required'          => true
            ],
            'description' => [
                'type'              => 'string',
                'description'       => "Short description of the rule to serve as memo."
            ],
            'type' => [
                'type'              => 'string',
                'description'       => "Kind of operation this rule relates to.",
                'selection'         => ['purchase', 'sale'],
                'required'          => true
            ],
            'accounting_rule_line_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'finance\accounting\AccountingRuleLine',
                'foreign_field'     => 'accounting_rule_id',
                'description'       => "Lines that are related to this rule."
            ]
        ];
    }

}