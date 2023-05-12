<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace sale\season;
use equal\orm\Model;

class SeasonPeriod extends Model {

    public static function getName() {
        return "Season Period";
    }

    public static function getDescription() {
        return "Specific date range within a Season.";
    }

    public static function getColumns() {

        return [

            'date_from' => [
                'type'              => 'date',
                'description'       => "Date (included) at which the season starts.",
                'required'          => true,
                'default'           => time(),
                'dependencies'      => ['month', 'year', 'season_category_id']
            ],

            'date_to' => [
                'type'              => 'date',
                'description'       => "Date (included) at which the season ends.",
                'required'          => true,
                'default'           => time()
            ],

            'season_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\season\Season',
                'description'       => "The season the period belongs to.",
                'required'          => true,
                'dependencies'      => ['season_category_id']
            ],

            'season_type_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\season\SeasonType',
                'description'       => "The type of period the period relates to.",
                'required'          => true
            ],

            'month' => [
                'type'              => 'computed',
                'result_type'       => 'integer',
                'usage'             => 'date/month',
                'description'       => "The month during which the season starts.",
                'function'          => 'calcMonth',
                'store'             => true,
                'instant'           => true
            ],

            'year' => [
                'type'              => 'computed',
                'result_type'       => 'integer',
                'usage'             => 'date/year',
                'description'       => "The Year the season is part of.",
                'function'          => 'calcYear',
                'store'             => true,
                'instant'           => true
            ],

            'season_category_id' => [
                'type'              => 'computed',
                'result_type'       => 'integer',
                'description'       => "The category the season relates to.",
                'function'          => 'calcSeasonCategoryId',
                'store'             => true,
                'instant'           => true
            ]

        ];
    }

    public static function calcMonth($self) {
        $result = [];
        $self->read(['date_from']);
        foreach($self as $id => $period) {
            $result[$id] = date('n', $period['date_from']);
        }
        return $result;
    }

    public static function calcYear($self) {
        $result = [];
        $self->read(['date_from']);
        foreach($self as $id => $period) {
            $result[$id] = date('Y', $period['date_from']);
        }
        return $result;
    }

    public static function calcSeasonCategoryId($self) {
        $result = [];
        $self->read(['season_id' => ['season_category_id']]);
        foreach($self as $id => $period) {
            $result[$id] = $period['season_id']['season_category_id'];
        }
        return $result;
    }
}