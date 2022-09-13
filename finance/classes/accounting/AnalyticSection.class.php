<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace finance\accounting;
use equal\orm\Model;

class AnalyticSection extends Model {

    public static function getName() {
        return "Analytic Section";
    }

    public static function getDescription() {
        return "Analytic sections allow to group spendings and revenues independently from the chart of accounts.";
    }

    public static function getColumns() {

        return [
            'name' => [
                'type'              => 'alias',
                'alias'             => 'code'
            ],

            'code' => [
                'type'              => 'string',
                'description'       => "Unique code identifying the section.",
                'required'          => true
            ],

            'description' => [
                'type'              => 'string',
                'description'       => "Short description of the section.",
            ],

            'is_locked' => [
                'type'              => 'boolean',
                'description'       => "Can the section be updated.",
                'default'           => false
            ],

            'label' => [
                'type'              => 'string',
                'description'       => "Short description of the section."
            ],

            /* parent chart of accounts */
            'analytic_chart_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'finance\accounting\AnalyticChart',
                'description'       => "The chart of accounts the line belongs to.",
                'required'          => true
            ]

        ];
    }

}