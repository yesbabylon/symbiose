<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace hr\absence;

class Absence extends \equal\orm\Model {

    public static function getName() {
        return 'Absence';
    }

    public static function getDescription() {
        return "An absence code allows to identify the reason of an absence.";
    }

    public static function getColumns() {
        return [

            'status' => [
                'type'              => 'string',
                'selection'         => [
                    'requested',
                    'planned',
                    'approved',
                    'refused'
                ],
                'default'           => 'requested'
            ],

            'organisation_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'identity\Identity',
                'description'       => 'The organisation which the targeted identity is a partner of.',
                'default'           => 1
            ],

            'employee_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'hr\employee\Employee',
                'description'       => 'The employee the absence relates to.',
                'required'          => true
            ],

            'code_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'hr\absence\AbsenceCode',
                'description'       => 'Absence code (reason).',
                'required'          => true
            ],

            'date' => [
                'type'              => 'date',
                'description'       => 'Date at which the absence is planned.',
                'required'          => true
            ],

            'day_part' => [
                'type'              => 'string',
                'selection'         => [
                    'forenoon',
                    'afternoon',
                    'fullday'
                ],
                'required'          => true
            ],

            'measure_unit' => [
                'type'              => 'string',
                'selection'         => [
                    'fullday',
                    'hours'
                ],
                'description'       => 'The units in which the quantity is expressed.',
                'default'           => 'hours'
            ],

            'qty' => [
                'type'              => 'float',
                'usage'             => 'numeric/real:5.2',
                'description'       => 'Amount of units expressed in `measure_unit`.',
                'required'          => true
            ],

            'duration' => [
                'type'              => 'computed',
                'result_type'       => 'float',
                'function'          => 'calcDuration',
                'usage'             => 'numeric/real:5.2',
                'description'       => 'Duration in hours (computed).'
            ]

        ];
    }


    public static function calcDuration($orm, $oids, $lang) {
        $result = [];
        $res = $orm->read(self::getType(), $oids, ['measure_unit', 'qty'], $lang);
        foreach($res as $oid => $odata) {
            // #todo - settings / HR : number of working hours within a day
            $hours_per_day = 7.6;
            $result[$oid] =  ($odata['measure_unit'] == 'fullday')?$odata['qty']*$hours_per_day:$odata['qty'];
        }
        return $result;
    }

    public function getUnique() {
        return [
            ['employee_id', 'date', 'day_part', 'code_id']
        ];
    }
}