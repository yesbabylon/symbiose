<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace calendar;
use equal\orm\Model;

class Holiday extends Model {
    
    public static function getName() {
        return "Ephemeris entry";
    }

    public static function getDescription() {
        return "Holidays allow to list the school vacations and public holidays within a given year.";
    }

    public static function getColumns() {

        return [
            'name' => [
                'type'              => 'string',
                'description'       => "Reason of the holiday (ephemeris)."
            ],

            'date_from' => [
                'type'              => 'date',
                'description'       => "Date/first day of the holiday.",
                'onchange'          => 'calendar\Holiday::onChangeDateFrom'
            ],

            'is_single_day' => [
                'type'              => 'boolean',
                'description'       => "Is the holiday a single date or does it span on several days?",
                'default'           => true
            ],

            'date_to' => [
                'type'              => 'date',
                'description'       => "Last date of the holiday.",
                'visible'           => ['is_single_day', '=', false]
            ],

            'year' => [
                'type'              => 'integer',
                'usage'             => 'date:year',
                'description'       => "Year on which the holiday applies (based first date)."
            ],

            'type' => [
                'type'              => 'string',
                'selection'         => [
                    'school_vacation', 
                    'public_holiday'
                ]
            ]
            
        ];
    }


    public static function onChangeDateFrom($orm, $oids, $lang) {
        $res = $orm->read(__CLASS__, $oids, ['date_from', 'is_single_day'], $lang);

        if($res > 0 && count($res)) {
            foreach($res as $oid => $odata) {
                $fields = [];
                if($odata['is_single_day']) {
                    $fields['date_to'] = $odata['date_from'];
                }
                $fields['year'] = date('Y', $odata['date_from']);
                $orm->write(__CLASS__, $oid, $fields);
            }
        }

    }

}