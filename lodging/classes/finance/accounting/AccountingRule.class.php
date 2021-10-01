<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace lodging\finance\accounting;


class AccountingRule extends \finance\accounting\AccountingRule {
    
    public static function getColumns() {

        return [       

            'code_legacy' => [
                'type'              => 'string',
                'description'       => "Old name of the accounting rule."
            ],

            'center_category_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'lodging\identity\CenterCategory',
                'description'       => "Center category targeted by the rule.",
                'required'          => true
            ],

            'product_models_ids' => [ 
                'type'              => 'many2many', 
                'foreign_object'    => 'lodging\sale\catalog\ProductModel', 
                'foreign_field'     => 'accounting_rules_ids', 
                'rel_table'         => 'lodging_catalog_product_rel_productmodel_accountingrule', 
                'rel_foreign_key'   => 'product_model_id',
                'rel_local_key'     => 'accounting_rule_id'
            ]

        ];
    }

}