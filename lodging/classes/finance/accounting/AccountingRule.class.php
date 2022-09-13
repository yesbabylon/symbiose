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

            /*
            // les règles comptables devraient être relatives aux organisations et pas aux catégories de centre
            'center_category_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'lodging\identity\CenterCategory',
                'description'       => "Center category targeted by the rule.",
                'required'          => true
            ]
            */
        ];
    }

}