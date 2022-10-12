<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace hr\holiday;

class Holiday extends \equal\orm\Model {

    public static function getName() {
        return 'Holiday';
    }

    public static function getDescription() {
        return "A holiday is a date at which employees are expected to benefit from a legal day of inactivity.";
    }

    public static function getColumns() {
        return [
            'name' => [
                'type'              => 'string',
                'description'       => 'Name of the holiday.',
                'multilang'         => true
            ],

            'date' => [
                'type'              => 'date',
                'description'       => "Date of the holiday.",
                'onupdate'          => 'onupdateDate'
            ],

            'year' => [
                'type'              => 'computed',
                'result_type'       => 'integer',
                'usage'             => 'date/year:4',
                'description'       => 'Year of the holiday.',
                'function'          => 'calcYear',
                'store'             => true,
                'description'       => "Year on which the holiday applies (based first date)."
            ]
        ];
    }

    public static function onupdateDate($orm, $oids, $values, $lang) {
        $orm->update(self::getType(), $oids, ['year' => null], $lang);
    }

    public static function calcYear($orm, $oids, $lang) {
        $result = [];
        $res = $orm->read(self::getType(), $oids, ['date'], $lang);
        foreach($res as $oid => $odata) {
            $result[$oid] = date('Y', $odata['date']);
        }
        return $result;
    }

}