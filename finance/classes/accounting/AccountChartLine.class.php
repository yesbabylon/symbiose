<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace finance\accounting;
use equal\orm\Model;

class AccountChartLine extends Model {

    public static function getName() {
        return "Chart of Accounts";
    }

    public static function getDescription() {
        return "A chart of accounts line holds information related to a specific account.";
    }

    public static function getColumns() {

        return [

            'name' => [
                'type'              => 'alias',
                'alias'             => 'code',
                'description'       => "Name of the account."
            ],

            'code' => [
                'type'              => 'string',
                'description'       => "A variable length string representing the number of the account.",
                'required'          => true
            ],

            'description' => [
                'type'              => 'string',
                'description'       => "Short description of the account.",
                'multilang'         => true
            ],

            /* parent chart of accounts */
            'account_chart_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'finance\accounting\AccountChart',
                'description'       => "The chart of accounts the line belongs to.",
                'required'          => true
            ],

            'analytic_section_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'finance\accounting\AnalyticSection',
                'description'       => "Related analytic section, if any."
            ],

            'nature' => [
                'type'      => 'string',
                'selection' => [
                    'B' => 'Business balance',
                    'M' => 'Management'
                ],
                'default'   => 'M'
            ],

            'type' => [
                'type'      => 'string',
                'selection' => [
                    'debt'              => 'Balance sheet>Fixed assets>Debtor',
                    'bank'              => 'Balance sheet>Fixed assets>Bank and liquidity',
                    'current_asset'     => 'Balance sheet>Fixed assets>Current assets',
                    'fixed_asset'       => 'Balance sheet>Fixed assets>Fixed asset',
                    'prepayment'        => 'Balance sheet>Fixed assets>Prepayments',
                    'fixed_assets'      => 'Balance sheet>Fixed assets>Fixed assets',
                    'payable'           => 'Balance sheet>Liabilities>Payable',
                    'credit_card'       => 'Balance sheet>Liabilities>Credit card',
                    'short_term_debt'   => 'Balance sheet>Liabilities>Short term debts',
                    'fixed_liability'   => 'Balance sheet>Liabilities>Fixed liabilities',
                    'equity'            => 'Balance sheet>Equity>Equity',
                    'profits_yearly'    => 'Balance sheet>Equity>Profits for the current year',
                    'income'            => 'Losses and Profits>Income>Income',
                    'other_income'      => 'Losses and Profits>Income>Other income',
                    'expenses'          => 'Losses and Profits>Spent>Expenses',
                    'amortization'      => 'Losses and Profits>Spent>Amortization',
                    'cost_of_sale'      => 'Losses and Profits>Spent>Cost of sales',
                    'off_balance'       => 'Other>Off balance sheet'
                ]
            ],

            'is_visible' => [
                'type'              => 'boolean',
                'default'           => false,
                'description'       => "Flag to switch visibility of the account.",
            ]

        ];
    }

    public function getUnique() {
        return [
            ['code']
        ];
    }

}