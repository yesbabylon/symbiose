<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace calendar;
use equal\orm\Model;

class HolidayYear extends Model {

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
                'description'       => "Year of the ephemeris list.",
                "multilang"         => true
            ],

            'year' => [
                'type'              => 'integer',
                'description'       => "Year of the ephemeris list.",
                'required'          => true
            ],

            'holidays_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'calendar\Holiday',
                'foreign_field'     => 'holiday_year_id',
                'description'       => 'List of holidays occuring during the year.'
            ]
        ];
    }

}