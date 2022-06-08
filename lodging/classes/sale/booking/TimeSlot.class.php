<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace lodging\sale\booking;
use equal\orm\Model;

class TimeSlot extends Model {

    public static function getName() {
        return 'Time Slot';
    }

    public static function getDescription() {
        return 'Time slots are used for planning purpose in order to slice a day into several moments.';
    }

    public static function getColumns() {

        return [

            'name' => [
                'type'              => 'string',
                'description'       => 'Time slot name.',
                'multilang'         => true,
                'required'          => true
            ],

            'description' => [
                'type'              => 'string',
                'description'       => 'Short description detailing the usage of the slot.',
                'multilang'         => true
            ],

            'order' => [
                'type'              => 'integer',
                'default'           => 1,
                'description'       => 'For sorting the moments within a day.'
            ],

            'schedule_from' => [
                'type'              => 'time',
                'required'          => true,
                'description'       => 'Time at which the slot starts (included).'
            ],

            'schedule_to' => [
                'type'              => 'time',
                'required'          => true,
                'description'       => 'Time at which the slots ends (excluded).'
            ]

        ];
    }
}