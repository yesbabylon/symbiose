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
                'default'           => time(),
                'onupdate'          => 'onupdateDateFrom'
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
                'onupdate'          => 'onupdateSeasonId'
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
                'store'             => true
            ],

            'year' => [
                'type'              => 'computed',
                'result_type'       => 'integer',
                'usage'             => 'date/year',
                'description'       => "The Year the season is part of.",
                'function'          => 'calcYear',
                'store'             => true
            ],

            'season_category_id' => [
                'type'              => 'computed',
                'result_type'       => 'integer',
                'description'       => "The category the season relates to.",
                'function'          => 'calcSeasonCategoryId',
                'store'             => true
            ]

        ];
    }

    public static function onupdateDateFrom($om, $oids, $values, $lang) {
        $om->write(__CLASS__, $oids, ['month' => null, 'year' => null]);
        // force immediate re-computing
        $om->read(__CLASS__, $oids, ['month', 'year']);
    }

    public static function onupdateSeasonId($om, $oids, $values, $lang) {
        $om->write(__CLASS__, $oids, ['season_category_id' => null]);
        // force immediate re-computing
        $om->read(__CLASS__, $oids, ['season_category_id']);
    }

    public static function calcMonth($om, $oids, $lang) {
        $result = [];
        $periods = $om->read(__CLASS__, $oids, ['date_from']);
        foreach($periods as $oid => $odata) {
            $result[$oid] = date('n', $odata['date_from']);
        }
        return $result;
    }

    public static function calcYear($om, $oids, $lang) {
        $result = [];
        $periods = $om->read(__CLASS__, $oids, ['date_from']);
        foreach($periods as $oid => $odata) {
            $result[$oid] = date('Y', $odata['date_from']);
        }
        return $result;
    }

    public static function calcSeasonCategoryId($om, $oids, $lang) {
        $result = [];
        $periods = $om->read(__CLASS__, $oids, ['season_id.season_category_id']);
        foreach($periods as $oid => $odata) {
            $result[$oid] = $odata['season_id.season_category_id'];
        }
        return $result;
    }

}