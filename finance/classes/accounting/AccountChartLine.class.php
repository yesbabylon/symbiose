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
                'type'              => 'computed',
                'result_type'       => 'string',
                'function'          => 'calcName',
                'description'       => "Name of the account.",
                'store'             => true
            ],

            'code' => [
                'type'              => 'string',
                'description'       => "A variable length string representing the number of the account.",
                'required'          => true,
                'dependents'        => ['level']
            ],

            'line_class' => [
                'type'              => 'integer',
                'usage'             => 'number/natural',
                'description'       => "The accounting class of the account.",
                'selection'         => [
                    0 => 'Linking and closing accounts',
                    1 => 'Equity',
                    2 => 'Investments',
                    3 => 'Inventories and work-in-progress',
                    4 => 'Short-term receivables and payables',
                    5 => 'Deferred income and expenses',
                    6 => 'Expenses',
                    7 => 'Revenues',
                ],
                'required'          => true
            ],

            'level' => [
                'type'              => 'computed',
                'result_type'       => 'integer',
                'description'       => "Depth of the account in the chart.",
                'function'          => 'calcLevel',
                'store'             => true
            ],

            'description' => [
                'type'              => 'string',
                'description'       => "Short description of the account.",
                'multilang'         => true
            ],

            'parent_account_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'finance\accounting\AccountChartLine',
                'description'       => "The parent account (line) the account is part of."
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
                    'B' => 'Balance Sheet',
                    'I' => 'Income Statement'
                ],
                'required'   => true
            ],

            'line_type' => [
                'type'      => 'string',
                'selection' => [
                    'debt'              => 'Balance Sheet>Fixed assets>Debtor',
                    'bank'              => 'Balance Sheet>Fixed assets>Bank and liquidity',
                    'current_asset'     => 'Balance Sheet>Fixed assets>Current assets',
                    'fixed_asset'       => 'Balance Sheet>Fixed assets>Fixed asset',
                    'prepayment'        => 'Balance Sheet>Fixed assets>Prepayments',
                    'fixed_assets'      => 'Balance Sheet>Fixed assets>Fixed assets',
                    'payable'           => 'Balance Sheet>Liabilities>Payable',
                    'credit_card'       => 'Balance Sheet>Liabilities>Credit card',
                    'short_term_debt'   => 'Balance Sheet>Liabilities>Short term debts',
                    'fixed_liability'   => 'Balance Sheet>Liabilities>Fixed liabilities',
                    'equity'            => 'Balance Sheet>Equity>Equity',
                    'profits_yearly'    => 'Balance Sheet>Equity>Profits for the current year',
                    'income'            => 'Income Statement>Income>Income',
                    'other_income'      => 'Income Statement>Income>Other income',
                    'expenses'          => 'Income Statement>Spent>Expenses',
                    'amortization'      => 'Income Statement>Spent>Amortization',
                    'cost_of_sale'      => 'Income Statement>Spent>Cost of sales',
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

    public static function calcLevel($self) {
        $result = [];
        $self->read(['code']);
        foreach($self as $id => $line) {
            $result[$id] = strlen($line['code']);
        }
        return $result;
    }

    public static function calcName($self) {
        $result = [];
        $self->read(['code']);
        foreach($self as $id => $line) {
            if(strlen($line['code']) < 6) {
                $result[$id] = str_pad($line['code'], 6, '0');
            }
            else {
                $result[$id] = $line['code'];
            }
        }
        return $result;
    }

    public function getUnique() {
        return [
            ['code']
        ];
    }

}