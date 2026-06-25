<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2024
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace sale\contract;
use core\setting\Setting;
use hr\holiday\Holiday;

class ServiceAccountEntry extends \equal\orm\Model {

    public static function getColumns() {

        return [

            'name' => [
                'type'              => 'string'
            ],

            'origin_object_class' => [
                'type'              => 'string',
                'description'       => 'Entity class that the service account entry originates from.',
                'default'           => 'timetrack\TimeEntry',
                'selection'         => [
                    'sale\SaleEntry',
                    'timetrack\TimeEntry'
                ]
            ],

            'origin_object_id' => [
                'type'              => 'integer',
                'description'       => 'Object identifier, as a complement to origin_object_class.',
                'dependents'        => ['time_entry_id', 'date', 'start', 'end', 'pause_time', 'delta_time', 'duration', 'points']
            ],

            'service_account_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\contract\ServiceAccount',
                'description'       => 'The service account the line belongs to.',
                'onupdate'          => 'onupdateServiceAccountId',
                'dependents'        => ['customer_id', 'points']
            ],

            'customer_id' => [
                'type'              => 'computed',
                'result_type'       => 'many2one',
                'foreign_object'    => 'sale\customer\Customer',
                'description'       => 'The customer the contract relates to.',
                'relation'          => ['service_account_id' => 'customer_id'],
                'store'             => true
            ],

            'description' => [
                'type'              => 'string',
                'usage'             => 'text/plain'
            ],

            'date' => [
                'type'              => 'computed',
                'result_type'       => 'datetime',
                'relation'          => ['time_entry_id' => 'date'],
                'description'       => 'Date of the time entry (at which the service was performed).',
                'store'             => true,
                'dependents'        => ['points']
            ],

            'start' => [
                'type'              => 'computed',
                'result_type'       => 'datetime',
                'function'          => 'calcStart',
                'description'       => 'Start time of the time entry (should be the same as date).',
                'store'             => true,
                'dependents'        => ['delta_time', 'duration', 'points']
            ],

            'end' => [
                'type'              => 'computed',
                'result_type'       => 'datetime',
                'function'          => 'calcEnd',
                'description'       => 'End time of the time entry.',
                'store'             => true,
                'dependents'        => ['delta_time', 'duration', 'points']
            ],

            'pause' => [
                'type'              => 'float',
                'description'       => 'Pause or time offset expressed in hours. Negative values subtract time, positive values add time.',
                'default'           => 0.0,
                'dependents'        => ['pause_time', 'duration', 'points']
            ],

            'pause_time' => [
                'type'              => 'computed',
                'result_type'       => 'time',
                'description'       => 'Pause or offset converted to seconds.',
                'function'          => 'calcPauseTime',
                'store'             => true,
                'dependents'        => ['duration', 'points']
            ],

            'delta_time' => [
                'type'              => 'computed',
                'result_type'       => 'time',
                'description'       => 'Raw duration between end and start, before pause handling.',
                'function'          => 'calcDeltaTime',
                'store'             => true
            ],

            'duration' => [
                'type'              => 'computed',
                'result_type'       => 'time',
                'description'       => 'Duration rounded up to the next quarter hour.',
                'function'          => 'calcDuration',
                'store'             => true,
                'dependents'        => ['points']
            ],

            'travel_time' => [
                'type'              => 'time',
                'description'       => 'Travel time to add for on-site work.',
                'default'           => 0,
                'dependents'        => ['points']
            ],

            'on_site' => [
                'type'              => 'boolean',
                'description'       => 'Flag telling if the job was performed on site.',
                'default'           => false,
                'dependents'        => ['points']
            ],

            'helpdesk' => [
                'type'              => 'boolean',
                'description'       => 'Flag telling if the job relates to helpdesk work.',
                'default'           => false,
                'dependents'        => ['points']
            ],

            'standby' => [
                'type'              => 'boolean',
                'description'       => 'Flag telling if the job relates to standby work.',
                'default'           => false,
                'dependents'        => ['points']
            ],

            'priority' => [
                'type'              => 'integer',
                'selection'         => [
                    1       => 'Low',
                    2       => 'Normal',
                    3       => 'High',
                    4       => 'Critical'
                ],
                'default'           => 2,
                'description'       => 'Priority level retrieved from the related ticket.',
                'dependents'        => ['points']
            ],

            'role_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'hr\employee\Role',
                'description'       => 'Role used to adapt points calculation, if configured.',
                'dependents'        => ['points']
            ],

            'role_hourly_factor' => [
                'type'              => 'float',
                'description'       => 'Role multiplier to apply during points calculation.',
                'default'           => 1.0,
                'dependents'        => ['points']
            ],

            'employee_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'hr\employee\Employee',
                'description'       => 'Employee who performed the work.'
            ],


            'contact' => [
                'type'              => 'string',
                'description'       => 'Contact name retrieved from the source system.'
            ],

            'posting_date' => [
                'type'              => 'datetime',
                'description'       => 'Date at which the line as been approved.'
            ],

            'is_posted' => [
                'deprecated'        => true,
                'type'              => 'boolean',
                'description'       => 'Flag marking the line as posted (has been approved).',
                'default'           => false,
            ],

            'has_report' => [
                'type'              => 'boolean',
                'description'       => 'Flag marking the line as attached to a report.',
                'default'           => false
            ],

            'report_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\contract\Report',
                'ondelete'          => 'null',
                'onupdate'          => 'onupdateReportId',
                'description'       => 'Report to which the line is assigned, if any.',
                'visible'           => ['has_report', '=', true]
            ],

            'is_locked' => [
                'type'              => 'boolean',
                'description'       => 'Marks the line as locked/invoiced (equivalent to has_report with a report in `released` status).',
                'default'           => false,
                'onupdate'          => 'onupdateIsLocked'
            ],

            'locked_date' => [
                'type'              => 'datetime',
                'description'       => 'Date-time at which the line has been locked / marked as invoiced.'
            ],

            'is_orphan' => [
                'type'              => 'boolean',
                'description'       => 'Flag raised if the line is present in CT but has been deleted in AT.',
                'help'              => "Deleting a posted entry is supposed to be forbidden. This flag is used to prevent recurring error messages.",
                'default'           => false
            ],

            'time_entry_id' => [
                'type'              => 'computed',
                'result_type'       => 'many2one',
                'foreign_object'    => 'timetrack\TimeEntry',
                'relation'          => ['origin_object_id'],
                'readonly'          => true,
                'visible'           => ['origin_object_class', '=', 'timetrack\TimeEntry']
            ],

            'points' => [
                'type'              => 'computed',
                'result_type'       => 'float',
                'description'       => 'Number of points the line corresponds to (can be computed or set manually).',
                'help'              => 'Points count is always a positive number. Points from Ticket and Task lines are used to decrement the related Report balance, and Credit and Correction lines are used to increment it.',
                'store'             => true,
                'function'          => 'calcPoints',
                'onupdate'          => 'onupdatePoints'
            ],

            'calculation_log' => [
                'type'              => 'string',
                'usage'             => 'text/plain',
                'description'       => 'Detailed log of the points calculations.'
            ],

            'calculation_time' => [
                'type'              => 'datetime',
                'description'       => 'Moment at which points were computed.'
            ]

        ];
    }

    /**
     * Synchronize customer_id with the customer_id from parent Service Account.
     * SALine CC are created from Service Account, so we need to sync with related customer.
     */
    public static function onupdateServiceAccountId($self, $values) {
        $self->read(['service_account_id']);
        foreach($self as $id => $line) {
            if($line['service_account_id']) {
                ServiceAccount::id($line['service_account_id'])
                    ->update(['balance_current' => null, 'has_balance_changed' => true]);
            }
        }
    }

    /**
     * Changes the locked_date value according to the is_locked status.
     */
    public static function onupdateIsLocked($self, $values) {
        if(isset($values['is_locked']) && $values['is_locked']) {
            $self->update(['locked_date' => time()]);
        }
        elseif(isset($values['is_locked']) && !$values['is_locked']) {
            $self->update(['locked_date' => null]);
        }
    }

    public static function onupdateReportId($self, $values) {
        if(isset($values['report_id']) && $values['report_id'] > 0) {
            $self->update(['has_report' => true]);
        }
        else {
            $self->update(['has_report' => false]);
        }
    }

    public static function calcStart($self) {
        $result = [];
        $self->read(['date', 'time_entry_id' => ['time_start']]);
        foreach($self as $id => $line) {
            if(!$line['date'] || !isset($line['time_entry_id']['time_start'])) {
                continue;
            }
            $result[$id] = strtotime('midnight', $line['date']) + (int) $line['time_entry_id']['time_start'];
        }
        return $result;
    }

    public static function calcEnd($self) {
        $result = [];
        $self->read(['date', 'time_entry_id' => ['time_start', 'time_end']]);
        foreach($self as $id => $line) {
            if(!$line['date'] || !isset($line['time_entry_id']['time_end'])) {
                continue;
            }

            $start = (int) ($line['time_entry_id']['time_start'] ?? 0);
            $end = (int) $line['time_entry_id']['time_end'];
            $result[$id] = strtotime('midnight', $line['date']) + $end;

            if($end < $start) {
                $result[$id] = strtotime('+1 day', $result[$id]);
            }
        }
        return $result;
    }


    /**
     * Converts pause to an amount of seconds.
     * Sign is inverted (original offset is negative). By default, pause is subtracted from time entry (but the other way around is possible).
     */
    public static function calcPauseTime($om, $oids, $lang) {
        $result = [];
        $lines = self::ids($oids)->read(['pause', 'time_entry_id' => ['pause_time']]);
        foreach($lines as $oid => $line) {
            if(isset($line['pause']) && (float) $line['pause'] != 0.0) {
                $result[$oid] = round(abs((float) $line['pause']) * 60 * 60);
            }
            else {
                $result[$oid] = (int) ($line['time_entry_id']['pause_time'] ?? 0);
            }
        }
        return $result;
    }

    /**
     * Computes the difference between end time and start time.
     */
    public static function calcDeltaTime($om, $oids, $lang) {
        $result = [];
        $lines = self::ids($oids)->read(['start', 'end']);
        foreach($lines as $oid => $line) {
            if(!isset($line['start'], $line['end'])) {
                continue;
            }
            $result[$oid] = max(0, $line['end'] - $line['start']);
        }
        return $result;
    }

    /**
     * Computes the duration of the time entry.
     * Duration is the time spent working, minus the pause, rounded to the quarter and expressed in seconds.
     */
    public static function calcDuration($om, $oids, $lang) {
        $result = [];
        $lines = self::ids($oids)->read(['start', 'end', 'pause', 'pause_time']);
        foreach($lines as $oid => $line) {
            if(!isset($line['start'], $line['end'])) {
                continue;
            }
            // #memo - pause_time is always positive; pause is negative if time has to be subtracted, and positive if time has to be added
            $sign = ($line['pause'] > 0)?-1:1;
            $duration = $line['end'] - $line['start'] - ($sign * (int) $line['pause_time']);
            $result[$oid] = max(0, ceil($duration /  (15 * 60)) * (15 * 60));
        }
        return $result;
    }


    /**
     * Compute the points of the line, according to configuration and line specifics.
     *
     */
    public static function calcPoints($om, $oids, $lang) {
        $result = [];

        $lines = self::ids($oids)->read([
                'is_locked',
                'date',
                'start',
                'end',
                'pause',
                'pause_time',
                'travel_time',
                'duration',
                'on_site',
                'helpdesk',
                'standby',
                'priority',
                'role_hourly_factor',
                'role_id'               => ['name'],
                'service_account_id'    => [
                    'id',
                    'customer_id' => [
                        'id'
                    ]
                ]
            ], $lang);

        foreach($lines as $oid => $line) {

            // prevent processing invoiced lines
            if($line['is_locked']) {
                continue;
            }

            /**
             * 0) retrieve default coefficients from global settings
             *
             * #memo - some values might be reassigned by customer specific values, so we need to reload them at each loop
             */

            // cap limit coefficient
            $coef_limit = (float) Setting::get_value('sale', 'default', 'coefficient.c_limit', 99.0);
            // priority coefficients
            $priority_normal = (float) Setting::get_value('sale', 'default', 'coefficient.c_priority_normal', 1.0);
            $priority_low = (float) Setting::get_value('sale', 'default', 'coefficient.c_priority_low', 1.0);
            $priority_high = (float) Setting::get_value('sale', 'default', 'coefficient.c_priority_high', 1.0);
            $priority_critical = (float) Setting::get_value('sale', 'default', 'coefficient.c_priority_critical', 1.0);
            // type coefficients
            $type_helpdesk = (float) Setting::get_value('sale', 'default', 'coefficient.c_helpdesk', 1.0);
            $type_standby = (float) Setting::get_value('sale', 'default', 'coefficient.c_standby', 1.0);
            // halday/fullday coefficients
            $hfd_discount = (bool) Setting::get_value('sale', 'default', 'option.f_hfd_discount', false);
            $hfd_halfday = (float) Setting::get_value('sale', 'default', 'coefficient.c_halfday', 1.0);
            $hfd_fullday = (float) Setting::get_value('sale', 'default', 'coefficient.c_fullday', 1.0);
            // time in minutes (convert to seconds)
            $hfd_halfday_min = self::computeTimeFromString(Setting::get_value('sale', 'default', 'duration.d_halfday_min', '03:00')) * 60;
            $hfd_halfday_max = self::computeTimeFromString(Setting::get_value('sale', 'default', 'duration.d_halfday_max', '05:00')) * 60;
            $hfd_fullday_min = self::computeTimeFromString(Setting::get_value('sale', 'default', 'duration.d_fullday_min', '06:00')) * 60;
            // time in hours (convert to seconds)
            $hfd_morning_stop = Setting::get_value('sale', 'default', 'time.t_morning_stop', 14) * 3600;
            $hfd_afternoon_start = Setting::get_value('sale', 'default', 'time.t_afternoon_start', 12) * 3600;
            // specific days coefficients
            $day_saturday = (float) Setting::get_value('sale', 'default', 'coefficient.c_saturday', 1.0);
            $day_sunday = (float) Setting::get_value('sale', 'defaults', 'coefficient.c_sunday', 1.0);
            $day_dayoff = (float) Setting::get_value('sale', 'default', 'coefficient.c_dayoff', 1.0);

            // keep track of the operations (will be stored in the `calculation_log` field)
            $logs = [];

            if(!$line['date'] || !$line['start'] || !$line['end'] || is_null($line['duration'])) {
                continue;
            }

            // retrieve offset between local timezone and UTC (times in settings use local time)
            $tz = new \DateTimeZone("Europe/Brussels");

            // timezone offset in seconds to apply, depending on the date of the time entry
            $tz_offset = $tz->getOffset(new \DateTime('@'.$line['date']));

            /**
             * 1) retrieve the coefficients for global settings or customer specific parameters
             */

            // override settings with customer specifics, if any
            $customer = $line['service_account_id']['customer_id'] ?? null;
            // #memo - we don't know if the flag is disabled or set to false (so we override only if global setting is set to false)
            if($customer) {
                if(isset($customer['f_hfd_discount']) && !$hfd_discount && $customer['f_hfd_discount']) {
                    $hfd_discount = $customer['f_hfd_discount'];
                    $logs[] = "Retrieved customer specific half-day flag (".intval($hfd_discount).")";
                }
                if(isset($customer['c_halfday']) && $customer['c_halfday']  > 0) {
                    $hfd_halfday = $customer['c_halfday'];
                    $logs[] = "Retrieved customer specific half-day coefficient (".self::computeStringFromCoeff($hfd_halfday).")";
                }
                if(isset($customer['c_fullday']) && $customer['c_fullday'] > 0) {
                    $hfd_fullday = $customer['c_fullday'];
                    $logs[] = "Retrieved customer specific full-day coefficient (".self::computeStringFromCoeff($hfd_fullday).")";
                }
                if(isset($customer['c_saturday']) && $customer['c_saturday'] > 0) {
                    $day_saturday = $customer['c_saturday'];
                    $logs[] = "Retrieved customer specific saturday coefficient (".self::computeStringFromCoeff($day_saturday).")";
                }
                if(isset($customer['c_sunday']) && $customer['c_sunday'] > 0) {
                    $day_sunday = $customer['c_sunday'];
                    $logs[] = "Retrieved customer specific sunday coefficient (".self::computeStringFromCoeff($day_sunday).")";
                }
                if(isset($customer['c_dayoff']) && $customer['c_dayoff'] > 0) {
                    $day_dayoff = $customer['c_dayoff'];
                    $logs[] = "Retrieved customer specific dayoff coefficient (".self::computeStringFromCoeff($day_dayoff).")";
                }
                if(isset($customer['c_helpdesk']) && $customer['c_helpdesk'] > 0) {
                    $type_helpdesk = $customer['c_helpdesk'];
                    $logs[] = "Retrieved customer specific helpdesk coefficient (".self::computeStringFromCoeff($type_helpdesk).")";
                }
                if(isset($customer['c_priority_low']) && $customer['c_priority_low'] > 0) {
                    $priority_low = $customer['c_priority_low'];
                    $logs[] = "Retrieved customer specific priority_low coefficient (".self::computeStringFromCoeff($priority_low).")";
                }
                if(isset($customer['c_priority_normal']) && $customer['c_priority_normal'] > 0) {
                    $priority_normal = $customer['c_priority_normal'];
                    $logs[] = "Retrieved customer specific priority_normal coefficient (".self::computeStringFromCoeff($priority_normal).")";
                }
                if(isset($customer['c_priority_high']) && $customer['c_priority_high'] > 0) {
                    $priority_high = $customer['c_priority_high'];
                    $logs[] = "Retrieved customer specific priority_high coefficient (".self::computeStringFromCoeff($priority_high).")";
                }
                if(isset($customer['c_priority_critical']) && $customer['c_priority_critical'] > 0) {
                    $priority_critical = $customer['c_priority_critical'];
                    $logs[] = "Retrieved customer specific priority_critical coefficient (".self::computeStringFromCoeff($priority_critical).")";
                }
                if(isset($customer['c_limit']) && $customer['c_limit'] > 0) {
                    $coef_limit = $customer['c_limit'];
                    $logs[] = "Retrieved customer specific limit coefficient (".self::computeStringFromCoeff($coef_limit).")";
                }
            }

            // start and end are datetimes : convert to seconds (remove the date part of the timestamp)
            $start = $line['start'] - strtotime('midnight', $line['start']);
            $end = $line['end'] - strtotime('midnight', $line['end']);

            $logs[] = "Retrieved start time: ".self::computeStringFromTime($start + $tz_offset);
            $logs[] = "Retrieved end time: ".self::computeStringFromTime($end + $tz_offset);

            // get the pause as a positive amount of seconds
            $pause = $line['pause_time'];
            // #memo - pause time can be negative (positive offset)
            if($line['pause'] > 0) {
                $pause = -$pause;
            }
            if($pause != 0) {
                if($pause < 0) {
                    $logs[] = "Positive offset: added extra time (".self::computeStringFromTime(-$pause).")";
                }
                else {
                    $logs[] = "Pause included: removed time off (".self::computeStringFromTime($pause).")";
                }
            }

            // duration is the time spent working (end-start), minus the pause, rounded to the quarter (@see calcDuration), given in seconds
            $duration = $line['duration'];
            $logs[] = "Retrieved rounded duration: ".self::computeStringFromTime($duration);

            // retrieve the day of the week (ISO 8601)
            $weekday = date('N', $line['date']);

            $coef = 1.0;

            /**
             * 2) Half / Full day reduction
             */

            // if half/full day is applicable and time entry is during working days
            if($hfd_discount && $weekday < 6) {
                $logs[] = "Qualified for Halfday-Fullday discount";
                if($duration > $hfd_fullday_min) {
                    $coef = $hfd_fullday;
                    $logs[] = "Assigned full-day coefficient (".self::computeStringFromCoeff($hfd_fullday).")";
                }
                elseif($duration > $hfd_halfday_min) {
                    // discard entries than span over morning and afternoon (by default morning strop is 2PM and afternoon start is 12AM)
                    if($end <= $hfd_morning_stop || $start >= $hfd_afternoon_start) {
                        $coef = $hfd_halfday;
                        $logs[] = "Assigned half-day coefficient (".self::computeStringFromCoeff($hfd_halfday).")";
                    }
                    else {
                        $logs[] = "(off-limit : no hdfd coefficient applied)";
                    }
                }
                else {
                    $logs[] = "(off-limit : no hdfd coefficient applied)";
                }
            }

            /**
             * 3) Hours coefficients (adapt the coefficient based on entry specifics)
             */

            // build the map (we slice a day into 6 parts holding UTC times)
            $map = [
                [
                    'from'  => self::computeTimeFromString('00:00'),
                    'to'    => self::computeTimeFromString(Setting::get_value('sale', 'default', 'time.t_morning', '07:00')) - $tz_offset,
                    'coeff' => (float) Setting::get_value('sale', 'default', 'coefficient.c_night', 1.0)
                ],
                [
                    'from'  => self::computeTimeFromString(Setting::get_value('sale', 'default', 'time.t_morning', '07:00')) - $tz_offset,
                    'to'    => self::computeTimeFromString(Setting::get_value('sale', 'default', 'time.t_workinghours_start', '09:00')) - $tz_offset,
                    'coeff' => (float) Setting::get_value('sale', 'default', 'coefficient.c_morning', 1.0)
                ],
                [
                    'from'  => self::computeTimeFromString(Setting::get_value('sale', 'default', 'time.t_workinghours_start', '09:00')) - $tz_offset,
                    'to'    => self::computeTimeFromString(Setting::get_value('sale', 'default', 'time.t_workinghours_end', '18:00')) - $tz_offset,
                    'coeff' => 1.0
                ],
                [
                    'from'  => self::computeTimeFromString(Setting::get_value('sale', 'default', 'time.t_workinghours_end', '18:00')) - $tz_offset,
                    'to'    => self::computeTimeFromString(Setting::get_value('sale', 'default', 'time.t_evening_1', '20:00')) - $tz_offset,
                    'coeff' => (float) Setting::get_value('sale', 'default', 'coefficient.c_evening_1', 1.0)
                ],
                [
                    'from'  => self::computeTimeFromString(Setting::get_value('sale', 'default', 'time.t_evening_1', '20:00')) - $tz_offset,
                    'to'    => self::computeTimeFromString(Setting::get_value('sale', 'default', 'time.t_evening_2', '22:00')) - $tz_offset,
                    'coeff' => (float) Setting::get_value('sale', 'default', 'coefficient.c_evening_2', 1.0)
                ],
                [
                    'from'  => self::computeTimeFromString(Setting::get_value('sale', 'default', 'time.t_evening_2', '22:00')) - $tz_offset,
                    'to'    => self::computeTimeFromString(24),
                    'coeff' => (float) Setting::get_value('sale', 'default', 'coefficient.c_night', 1.0)
                ]
            ];

            // compute virtual duration (in seconds)

            /**
             * @var float $time Holds the total of time slices, in seconds (= end-start-pause)
             * @var float $q    Holds the sum of the times with their related coefficient applied
             * #
             */
            list($time, $q) = [0.0, 0.0];

            // by default, there is one period : from start time to end time
            $periods = [
                [
                    'start' => $start,
                    'end'   => $end
                ]
            ];

            // in case a pause is present and positive, split the entry in 2 periods, with the pause in the middle
            if($pause > 0) {
                $periods = [
                    [
                        'start'   => $start,
                        'end'     => $start + ( ($end-$start)/2 ) - ceil($pause/2),
                    ],
                    [
                        'start'   => $start + ( ($end-$start)/2 ) + floor($pause/2),
                        'end'     => $end
                    ]
                ];
            }
            elseif($pause < 0) {
                // no change - offset has already been added to computed duration (@see `calcDuration()`)
            }

            for($i = 0, $n = count($periods); $i < $n; ++$i) {
                $period = $periods[$i];
                foreach($map as $set) {
                    $applicable_time = self::_getApplicableTime($period['start'], $period['end'], $set['from'], $set['to']);
                    // #memo - we do not round the times within periods
                    if($applicable_time) {
                        $time += $applicable_time;
                        $q += $set['coeff'] * $applicable_time;
                        $logs[] = "Counting ".self::computeStringFromTime($applicable_time)." applied on range [".self::computeStringFromTime($set['from'] + $tz_offset)." - ".self::computeStringFromTime($set['to'] + $tz_offset)."] (".self::computeStringFromCoeff($set['coeff']).")";
                    }
                }
            }

            // discard fractions of seconds, if any, and round to the upper second
            $q = ceil($q);

            $coef_hours = ($time > 0)?($q / $time):1.0;
            // apply the resulting Hours coefficient
            $coef *= $coef_hours;
            $logs[] = "Retrieved base coefficient: ".round($coef, 4);

            // #memo - points are counted in quarters
            $logs[] = "Retrieved base quarters: ".round($duration / (15*60), 2);


            /**
             * 4) Weekday coefficient
             */

            if($weekday == 6) {
                $coef *= $day_saturday;
                $logs[] = "Job performed on Saturday: applying c_saturday (".self::computeStringFromCoeff($day_saturday).")";
            }
            elseif($weekday == 7) {
                $coef *= $day_sunday;
                $logs[] = "Job performed on Sunday: applying c_sunday (".self::computeStringFromCoeff($day_sunday).")";
            }
            else {
                // check if date matches a day-off entry
                $holiday = Holiday::search(['date', '=', strtotime('midnight', $line['date'])])->read(['id', 'name'])->first();
                if(!is_null($holiday)) {
                    $coef *= $day_dayoff;
                    $logs[] = "Day-off ({$holiday['name']}): applying c_dayoff (".self::computeStringFromCoeff($day_dayoff).")";
                }
            }


            /**
             * 5) Worktype coefficient (helpdesk)
             */

            // helpdesk
            if($line['helpdesk']) {
                $coef *= $type_helpdesk;
                $logs[] = "Helpdesk: applying c_helpdesk (".self::computeStringFromCoeff($type_helpdesk).")";
            }


            /**
             * 6) Category coefficient
             */

            // standby
            if($line['standby']) {
                $coef *= $type_standby;
                $logs[] = "Standby: applying c_standby (".self::computeStringFromCoeff($type_standby).")";
            }


            /**
             * 7) Priority coefficient
             */

            // apply the Priority coefficient
            switch($line['priority']) {
                // low
                case 1:
                    $coef *= $priority_low;
                    $logs[] = "Priority: applying 'low' (".self::computeStringFromCoeff($priority_low).")";
                    break;
                // normal (medium)
                case 2:
                    $coef *= $priority_normal;
                    $logs[] = "Priority: applying 'normal' (".self::computeStringFromCoeff($priority_normal).")";
                    break;
                // high
                case 3:
                    $coef *= $priority_high;
                    $logs[] = "Priority: applying 'high' (".self::computeStringFromCoeff($priority_high).")";
                    break;
                // critical
                case 4:
                    $coef *= $priority_critical;
                    $logs[] = "Priority: applying 'critical' (".self::computeStringFromCoeff($priority_critical).")";
                    break;
                default:
                    break;
            }


            /**
             * 8) Coefficient limit
             */

            if($coef > $coef_limit) {
                // cap limit
                $coef = $coef_limit;
                $logs[] = "Max reached: caping to c_limit (".self::computeStringFromCoeff($coef_limit).")";
            }


            /**
             * 9) Coefficient application
             */

            $time = $duration * $coef;
            $logs[] = "Resulting final coefficient: ".self::computeStringFromCoeff($coef);

            /**
             * 10) Travel increment
             */

            if($line['on_site']) {
                // #memo - travel_time is in seconds
                $travel_time = $line['travel_time'];
                $time += $travel_time;
                $logs[] = "On-site job: adding travel time (".self::computeStringFromTime($travel_time).")";
            }

            /**
             * 11) Role coefficient
             */

            if(isset($line['role_hourly_factor']) && (float) $line['role_hourly_factor'] != 1.0) {
                $coef_role = (float) $line['role_hourly_factor'];
                $time = $time * $coef_role;
                $role_name = $line['role_id']['name'] ?? 'specific role factor';
                $logs[] = "Role: applying {$role_name} (".self::computeStringFromCoeff($coef_role).")";
            }

            /**
             * 12) Points calculation
             */

            // compute final result
            $points = round($time / (15 * 60), 2);

            if(!is_numeric($points) || is_nan($points)) {
                // should not occur
                $logs[] = "ERROR - result is not a number";
            }
            else {
                $result[$oid] = $points;
                $logs[] = "Resulting final points: ".$result[$oid];
            }

            // store logs
            $om->update(self::getType(), $oid, ['calculation_time' => time(),'calculation_log' => implode('<br />', $logs)]);
            // reset current balance of parent Service Account
            if(isset($line['service_account_id']['id']) && $line['service_account_id']['id']) {
                $om->update(ServiceAccount::getType(), $line['service_account_id']['id'], ['balance_current' => null, 'has_balance_changed' => true]);
            }
        }
        return $result;
    }

    /**
     *
     */
    public static function onupdatePoints($self) {
        $self->read(['service_account_id']);
        foreach($self as $id => $line) {


            // if line is a credit, reset the `alert_sent` flag of the related service account

            // ServiceAccount::id($line['service_account_id'])->update(['has_renew_alert_sent' => false]);


            if($line['service_account_id']) {
                ServiceAccount::id($line['service_account_id'])
                    ->update(['balance_current' => null, 'has_balance_changed' => true]);
            }
        }
    }

    /**
     * Check wether an object can be updated, and perform some additional operations if necessary.
     * This method can be overridden to define a more precise set of tests.
     *
     * @param  \equal\orm\Collection    $self   Collection of objects of current class.
     * @return array    Returns an associative array mapping fields with their error messages. An empty array means that object has been successfully processed and can be updated.
     */
    public static function canupdate($self, $values) {
        $providers = \eQual::inject(['dispatch']);
        /** @var \equal\dispatch\Dispatcher $dispatch */
        $dispatch = $providers['dispatch'];

        $self->read(['is_locked', 'has_report', 'report_id' => ['id', 'status']]);
        foreach($self as $id => $line) {
            if($line['is_locked']) {
                $allowed = ['is_locked', 'locked_date', 'posting_date'];
                if(count(array_diff(array_keys($values), $allowed)) > 0) {
                    return ['is_locked' => ['non_editable' => "Locked SA line [$id] cannot be updated (linked to released Report)."]];
                }
            }
            else {
                // #memo - allow arbitrary change of report-related fields for non locked lines
                $allowed = ['report_id', 'has_report', 'posting_date', 'is_locked', 'locked_date'];
                // #memo - at this stage a linked pending report might have been removed resulting in a NULL report_id
                if($line['report_id']) {
                    $current_report_id = $line['report_id']['id'] ?? null;
                    if(isset($values['report_id']) && $values['report_id'] > 0 && $current_report_id != $values['report_id']) {
                        // prevent change unless made only on is_locked
                        if(count($values) > 1 || array_keys($values)[0] != 'is_locked') {
                            $dispatch->dispatch('contractika.sa_line.already_sent', self::getType(), $id, 'warning');
                            return ['has_report' => ['non_editable' => "SA line [$id] cannot be linked to a new Report while already linked to a Report."]];
                        }
                    }
                    elseif(count(array_diff(array_keys($values), $allowed)) > 0 ) {
                        return ['has_report' => ['non_editable' => "SA line [$id] is linked to a pending Report and cannot be updated."]];
                    }
                }
            }
        }
        return parent::canupdate($self, $values);
    }

    public static function candelete($self) {
        $self->read(['is_locked']);
        foreach($self as $id => $line) {
            if($line['is_locked']) {
                return ['is_locked' => ['not_allowed' => "Locked SA line [$id] cannot be deleted (linked to released Report)."]];
            }
        }
        return parent::candelete($self);
    }

    /**
     * Returns the value of a moment (time as a string or as an integer) expressed as an integer amount of seconds.
     *
     * @return int  The amount of seconds elapsed since 00:00 (from 0 to 86400).
     */
    private static function computeTimeFromString($value) {
        $value = (string) $value;
        list($hour, $minute, $second) = [0,0,0];
        $count = substr_count($value, ':');
        if($count == 2) {
            list($hour, $minute, $second) = sscanf($value, "%d:%d:%d");
        }
        else if($count == 1) {
            list($hour, $minute) = sscanf($value, "%d:%d");
        }
        else if($count == 0) {
            if(intval($value) > 24) {
                // time in minutes
                $hour = intval($value) / 60;
            }
            else {
                $hour = intval($value);
            }
        }
        return ($hour * 3600) + ($minute * 60) + $second;
    }

    private static function computeStringFromCoeff($value) {
        return number_format((float) round($value, 2), 2, '.', '');
    }

    private static function computeStringFromTime($value) {
        $hours = floor($value / 3600);
        $minutes = floor(($value % 3600) / 60);
        return sprintf("%02d:%02d", $hours, $minutes);
    }

    /**
     * Returns the time, in seconds, that must be accounted for a given range, within a specific set of limits.
     * Meant to receive and return values in seconds.
     * We compare a range (A, B) to a pair of limits (from, to).
     * This method is meant to be called in loop with an invariable range and a series of limit.
     *
     *
     *        There are 4 possible matches: 2, 3, 4, 6
     *
     *                   from             to
     *                   |----------------|
     *           1        2       3       4        5
     *        |-----|  |-----|  |---|  |-----|  |-----|
     *                            6
     *               |---------------------------|
     *
     * @param int $a        Left edge of the range as a time relative to 00:00.
     * @param int $b        Right edge of the range as a time relative to 00:00.
     * @param int $from     Left limit to compare the range with.
     * @param int $to       Right limit to compare the range with.
     * @return int          Returns the time, in seconds, that the limits cover within the range.
     */
    private static function _getApplicableTime($a, $b, $from, $to) {
        $qty = 0;
        if($a >= $from && $a <= $to) {
            if($b >= $from && $b <= $to) {
                $qty = ($b-$a);
            }
            else {
                $qty = ($to-$a);
            }
        }
        else {
            if($b >= $from && $b <= $to) {
                $qty = ($b-$from);
            }
            else {
                if($a < $from && $b > $to) {
                    $qty = ($to-$from);
                }
            }
        }
        return $qty;
    }


}
