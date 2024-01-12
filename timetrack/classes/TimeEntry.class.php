<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

namespace timetrack;

use equal\orm\Model;

class TimeEntry extends Model {

    const ORIGIN_BACKLOG = 1;
    const ORIGIN_EMAIL = 2;
    const ORIGIN_SUPPORT = 3;

    const ORIGIN_MAP = [
        self::ORIGIN_BACKLOG => 'Backlog',
        self::ORIGIN_EMAIL   => 'E-mail',
        self::ORIGIN_SUPPORT => 'Support ticket',
    ];

    public static function getColumns(): array {
        return [

            'name' => [
                'type'           => 'string',
                'description'    => 'Name of the time entry.',
                'required'       => true,
                'unique'         => true
            ],

            'description' => [
                'type'           => 'string',
                'description'    => 'Description of the time entry.'
            ],

            'time_start' => [
                'type'           => 'datetime',
                'description'    => 'Start date time of the entry.',
                'default'        => time(),
                'dependencies'   => ['duration']
            ],

            'time_end' => [
                'type'           => 'datetime',
                'description'    => 'End date time of the entry.',
                'default'        => strtotime('+1 hour'),
                'dependencies'   => ['duration']
            ],

            'duration' => [
                'type'           => 'computed',
                'result_type'    => 'string',
                'store'          => true,
                'instant'        => true,
                'function'       => 'calcDuration',
                'onupdate'       => 'onupdateDuration'
            ],

            'user_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'core\User',
                'description'       => 'User the time entry was realised by.'
            ],

            'origin' => [
                'type'           => 'integer',
                'selection'      => self::ORIGIN_MAP,
                'description'    => 'Origin of the this time entry creation.',
                'default'        => self::ORIGIN_EMAIL
            ],

            'project_id' => [
                'type'           => 'many2one',
                'foreign_object' => 'timetrack\Project',
                'description'    => 'Project for which this time entry has been created.',
                'dependencies'   => ['ticket_link'],
            ],

            'customer_id' => [
                'type'           => 'computed',
                'result_type'    => 'many2one',
                'foreign_object' => 'sale\customer\Customer',
                'description'    => 'Customer this time entry was created for.',
                'function'       => 'calcCustomerId',
                'store'          => true,
                'readonly'       => true
            ],

            'ticket_id' => [
                'type'           => 'integer',
                'description'    => 'Support ticket id from project Symbiose instance',
                'dependencies'   => ['ticket_link'],
                'visible'        => ['origin', '=', self::ORIGIN_SUPPORT]
            ],

            'ticket_link' => [
                'type'           => 'computed',
                'result_type'    => 'string',
                'usage'          => 'uri/url',
                'function'       => 'calcTicketLink',
                'store'          => true,
                'visible'        => ['origin', '=', self::ORIGIN_SUPPORT]
            ]

        ];
    }

    public static function calcDuration($self): array {
        $return = [];
        $self->read(['time_start', 'time_end']);
        foreach($self as $id => $time_entry) {
            $seconds = $time_entry['time_end'] - $time_entry['time_start'];
            $return[$id] = sprintf('%02d:%02d', ($seconds/3600), ($seconds/60%60));
        }

        return $return;
    }

    public static function onupdateDuration($self): void {
        $self->read(['time_start', 'time_end', 'duration']);
        foreach($self as $id => $time_entry) {
            $hh_mm_pattern = '/^([0-1]?[0-9]|2[0-3]):[0-5][0-9]$/';
            if(
                is_null($time_entry['duration'])
                || !preg_match($hh_mm_pattern, $time_entry['duration'])
            ) {
                continue;
            }

            $parsed_time = date_parse($time_entry['duration'].':00');
            $duration = $parsed_time['hour'] * 3600 + $parsed_time['minute'] * 60;
            if($duration === ($time_entry['time_end'] - $time_entry['time_start'])) {
                continue;
            }

            $new_time_end = $time_entry['time_start'] + $duration;

            TimeEntry::id($id)
                ->update(['time_end' => $new_time_end]);
        }
    }

    public static function calcCustomerId($self): array {
        $return = [];
        $self->read(['project_id' => ['customer_id']]);
        foreach($self as $id => $time_entry) {
            $return[$id] = $time_entry['project_id']['customer_id'];
        }

        return $return;
    }

    public static function calcTicketLink($self): array {
        $return = [];
        $self->read(['origin', 'ticket_id', 'project_id' => ['instance_id' => ['url']]]);
        foreach($self as $id => $time_entry) {
            if(
                $time_entry['origin'] !== self::ORIGIN_SUPPORT
                || is_null($time_entry['ticket_id'])
                || empty($time_entry['project_id']['instance_id']['url'])
            ) {
                $return[$id] = null;
                continue;
            }

            $instance_url = $time_entry['project_id']['instance_id']['url'];
            if(substr($instance_url, -1) !== '/') {
                $instance_url .= '/';
            }

            $return[$id] = $instance_url.'support/#/ticket/'.$time_entry['ticket_id'];
        }

        return $return;
    }
}
