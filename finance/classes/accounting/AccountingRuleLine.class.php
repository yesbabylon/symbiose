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
                'type'              => 'alias',
                'alias'             => 'label'
            ],
            'label' => [
                'type'              => 'string',
                'description'       => "Code of the line to serve as memo.",
                'required'          => true                
            ],
            'account' => [
                'type'              => 'string',
                'description'       => "Code of the related account.",
                'required'          => true
            ],
            'section' => [
                'type'              => 'string',
                'description'       => "Code of the related analytical accounting section."
            ],
            'share' => [
                'type'              => 'float',
                'usage'             => 'amount/percentage',
                'description'       => "Share of the line, in percent (sum of lines must be 100%)."
            ],
            'accounting_rule_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'finance\accounting\AccountingRule',
                'description'       => "Accounting rule this line is related to."
            ],
            'vat_rule_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'finance\tax\VatRule',
                'description'       => "VAT rule this line is related to."
            ]

        ];
    }

}