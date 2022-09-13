<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace finance\tax;
use equal\orm\Model;

class VatRule extends Model {

    public static function getName() {
        return "VAT Rule";
    }

    public static function getDescription() {
        return "VAT rules allow to specify which VAT rate applies for a given kind of operation.";
    }

    public static function getColumns() {

        return [
            'name' => [
                'type'              => 'string',
                'description'       => "Name of the VAT rule.",
                'multilang'         => true,
                'required'          => true
            ],

            'rate' => [
                'type'              => 'float',
                'usage'             => 'amount/percent',
                'description'       => "Name of the VAT rule.",
                'required'          => true
            ],

            'type' => [
                'type'              => 'string',
                'description'       => "Kind of operation this rule relates to.",
                'selection'         => ['purchase', 'sale'],
                'required'          => true
            ],

            'account_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'finance\accounting\AccountChartLine',
                'description'       => "Account which the tax amount relates to.",
            ],

            // #todo - if several accounting accounts are involded (distinct shares), we need to use a VatRuleLine class holding `account_id` and `share` fields

        ];
    }

}