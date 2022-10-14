<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace lodging\sale\booking;

class Repair extends \lodging\sale\booking\Consumption {

    public static function getName() {
        return 'Repair';
    }

    public static function getDescription() {
        return "A repair is an event that relates to a scheduled repairing. Repairs and Consumptions are handled the same way in the Planning.";
    }

    public static function getColumns() {
        return [
            'repairing_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'lodging\sale\booking\Repairing',
                'description'       => 'The repairing task the repair relates to.',
                'readonly'          => true,
                'ondelete'          => 'cascade'        // delete repair when parent repairing is deleted
            ],

            'type' => [
                'type'              => 'string',
                'description'       => "The reason the unit is unavailable (always 'out-of-order').",
                'selection'         => [
                    'ooo'                              // out-of-order (repair & maintenance)
                ],
                'readonly'          => true,
                'default'           => 'ooo'
            ],

            'is_rental_unit' => [
                'type'              => 'boolean',
                'description'       => 'Does the consumption relate to a rental unit?',
                'default'           => true
            ],

            'rental_unit_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'realestate\RentalUnit',
                'description'       => "The rental unit the consumption is assigned to."
            ],

            'qty' => [
                'type'              => 'integer',
                'description'       => "How many times the consumption is booked for.",
                'default'           => 1,
                'readonly'          => true
            ],

            /* override schedule_from and schedule_to with specific onupdate events */

            'schedule_from' => [
                'type'              => 'time',
                'description'       => 'Moment of the day at which the events starts.',
                'default'           => 0,
                'onupdate'          => 'onupdateScheduleFrom'
            ],

            'schedule_to' => [
                'type'              => 'time',
                'description'       => 'Moment of the day at which the event stops, if applicable.',
                'default'           => 24 * 3600,
                'onupdate'          => 'onupdateScheduleTo'
            ]
        ];
    }


    public static function onupdateScheduleFrom($om, $oids, $values, $lang) {
        $repairs = $om->read(self::getType(), $oids, ['repairing_id'], $lang);
        if($repairs > 0) {
            $repairings_ids = array_map(function($a) {return (int) $a['repairing_id'];}, $repairs);
            $om->update(Repairing::getType(), $repairings_ids, ['time_from' => null]);
        }
    }

    public static function onupdateScheduleTo($om, $oids, $values, $lang) {
        $repairs = $om->read(self::getType(), $oids, ['repairing_id'], $lang);
        if($repairs > 0) {
            $repairings_ids = array_map(function($a) {return (int) $a['repairing_id'];}, $repairs);
            $om->update(Repairing::getType(), $repairings_ids, ['time_to' => null]);
        }
    }

}