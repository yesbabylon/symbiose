<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace finance\stats;
use equal\orm\Model;

class StatSection extends Model {
    
    public static function getName() {
        return "Statistics Section";
    }

    public static function getDescription() {
        return "Stat sections allow to generate view by grouping sales in an arbitray manner (independent from chart of accounts and analytical chart of accounts).";
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
                'description'       => "Short description of the section."
            ],

            'label' => [
                'type'              => 'string',
                'description'       => "Short description of the section."
            ],

            /* parent chart of accounts */
            'stat_chart_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'finance\stats\StatChart',
                'description'       => "The stats chart the line belongs to.",
                'required'          => true
            ]

        ];
    }


    public function getUnique() {
        return [
            ['code']
        ];
    }


}