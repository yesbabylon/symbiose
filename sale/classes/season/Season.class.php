<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace sale\season;
use equal\orm\Model;

class Season extends Model {
    public static function getColumns() {
        /**
         */

        return [
            'name' => [
                'type'              => 'string',
                'description'       => "Short mnemo of the season.",
                'required'          => true
            ],

            'date_from' => [
                'type'              => 'date',
                'description'       => "Date (included) at which the season starts.",
                'required'          => true
            ],

            'date_to' => [
                'type'              => 'date',
                'description'       => "Date (excluded) at which the season ends.",
                'required'          => true
            ],

            'season_year_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\season\SeasonYear',
                'description'       => "The Season the list relates to.",
                'required'          => true
            ],

            'season_category_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\season\SeasonCategory',
                'description'       => "The category the season relates to.",
                'required'          => true
            ],

            'rate_class_id' => [
                'type'              => 'many2one',                
                'foreign_object'    => 'sale\customer\RateClass',
                'description'       => "The rate class that applies to this class of discount.",
                'required'          => true
            ],
            
        ];
    }

}