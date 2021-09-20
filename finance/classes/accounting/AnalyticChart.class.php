<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace finance\accounting;
use equal\orm\Model;

class AnalyticChart extends Model {
    
    public static function getName() {
        return "Analytical Chart of Accounts";
    }

    public static function getDescription() {
        return "The analytical chart of accounts allow to ventilate incomes and expenses independently from the chart of accounts.";
    }

    public static function getColumns() {

        return [
            'name' => [
                'type'              => 'string',
                'description'       => "Name of the analytical chart of accounts."
            ],

            /* owner organisation */
            'organisation_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'identity\Identity',
                'description'       => "The organisation the chart belongs to.",
                'required'          => true
            ],

            'analytic_sections_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'finance\accounting\AnalyticSection',
                'foreign_field'     => 'analytic_chart_id',
                'description'       => "Sections that are related to this analytic chart."
            ]

        ];
    }

}