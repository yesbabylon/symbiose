<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace finance\accounting;
use equal\orm\Model;

class AccountingRuleLine extends Model {
    
    public static function getName() {
        return "Accounting Rule Line";
    }

    public static function getDescription() {
        return "Accounting rules have one or more lines associating them with an account and a VAT rule.";
    }

    public static function getColumns() {

        return [
            'name' => [
                'type'              => 'string',
                'description'       => "Short string to serve as memo.",
                'required'          => true
            ],

            'account' => [
                'type'              => 'string',
                'description'       => "Code of the related account.",
                'required'          => true
            ],
            
            'share' => [
                'type'              => 'float',
                'usage'             => 'amount/percent',
                'description'       => "Share of the line, in percent (lines sum must be 100%).",
                'default'           => 1.0
            ],

            'accounting_rule_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'finance\accounting\AccountingRule',
                'description'       => "Accounting rule this line is related to."
            ]

        ];
    }

}