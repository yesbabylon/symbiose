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
        return "Booking line group";
    }

    public static function getDescription() {
        return "Booking line groups are related to a booking and describe one or more sojourns and their related consumptions.";
    }

    public static function getColumns() {

        return [

            'date_from' => [
                'type'              => 'date',
                'description'       => "Date (included) at which the season starts.",
                'required'          => true,
                'default'           => time()
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
                'onchange'          => 'sale\season\SeasonPeriod::onchangeSeason'
            ],

            'season_type_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\season\SeasonType',
                'description'       => "The type of period the period relates to.",
                'required'          => true
            ],

            'year' => [
                'type'              => 'computed',
                'result_type'       => 'integer',
                'description'       => "The Year the season is part of.",
                'function'          => 'sale\season\SeasonPeriod::getYear',
                'store'             => true
            ],

            'season_category_id' => [
                'type'              => 'computed',
                'result_type'       => 'integer',
                'description'       => "The category the season relates to.",
                'function'          => 'sale\season\SeasonPeriod::getSeasonCategoryId',
                'store'             => true
            ]

        ];
    }

    public static function onchangeSeason($om, $oids, $lang) {
        $om->write(__CLASS__, $oids, ['year' => null, 'season_category_id' => null]);
        // force immediate re-computing
        $om->read(__CLASS__, $oids, ['year', 'season_category_id']);
    }

    public static function getYear($om, $oids, $lang) {
        $result = [];
        $periods = $om->read(__CLASS__, $oids, ['season_id.year']);
        foreach($periods as $oid => $odata) {
            $result[$oid] = $odata['season_id.year'];
        }
        return $result;
    }

    public static function getSeasonCategoryId($om, $oids, $lang) {
        $result = [];
        $periods = $om->read(__CLASS__, $oids, ['season_id.season_category_id']);
        foreach($periods as $oid => $odata) {
            $result[$oid] = $odata['season_id.season_category_id'];
        }
        return $result;
    }
}