<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace sale\booking;
use equal\orm\Model;

class BookingLineGroup extends Model {

    public static function getName() {
        return "Booking line group";
    }

    public static function getDescription() {
        return "Booking line groups are related to a booking and describe one or more sojourns and their related consumptions.";
    }

    public static function getColumns() {
        return [
            'name' => [
                'type'              => 'string',
                'description'       => 'Mnemo for the group.',
                'default'           => ''
            ],

            'order' => [
                'type'              => 'integer',
                'description'       => 'Order of the group in the list.',
                'default'           => 1
            ],

            'date_from' => [
                'type'              => 'date',
                'description'       => "Day of arrival.",
                'default'           => time()
            ],

            'date_to' => [
                'type'              => 'date',
                'description'       => "Day of departure.",
                'default'           => time()
            ],

            'time_from' => [
                'type'              => 'time',
                'description'       => "Checkin time on the day of arrival.",
                'default'           => 14 * 3600
            ],

            'time_to' => [
                'type'              => 'time',
                'description'       => "Checkout time on the day of departure.",
                'default'           => 10 * 3600
            ],

            'is_sojourn' => [
                'type'              => 'boolean',
                'description'       => 'Does the group spans over several nights and relate to accomodations?',
                'default'           => false
            ],

            'is_event' => [
                'type'              => 'boolean',
                'description'       => 'Does the group relate to an event occuring on a single day?',
                'default'           => false
            ],

            'is_extra' => [
                'type'              => 'boolean',
                'description'       => 'Group relates to sales made off-contract. (ex. point of sale)',
                'default'           => false
            ],

            'has_schedulable_services' => [
                'type'              => 'computed',
                'result_type'       => 'boolean',
                'description'       => 'Flag marking the group as holding at least one schedulable service.',
                'function'          => 'calcHasSchedulableServices'
            ],

            'nb_nights' => [
                'type'              => 'computed',
                'result_type'       => 'integer',
                'description'       => 'Amount of nights of the sojourn.',
                'function'          => 'calcNbNights',
                'store'             => true
            ],

            'nb_pers' => [
                'type'              => 'integer',
                'description'       => 'Amount of persons this group is about.',
                'default'           => 1
            ],

            /* a booking can be split into several groups on which distinct rate classes apply, by default the rate_class of the customer is used */
            'rate_class_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\customer\RateClass',
                'description'       => "The rate class that applies to the group.",
                'default'           => 1
            ],

            'booking_lines_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'sale\booking\BookingLine',
                'foreign_field'     => 'booking_line_group_id',
                'description'       => 'Booking lines that belong to the group.',
                'ondetach'          => 'delete',
                'onupdate'          => 'onupdateBookingLinesIds'
            ],

            'consumptions_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'sale\booking\Consumption',
                'foreign_field'     => 'booking_line_group_id',
                'description'       => 'Consumptions related to the group.',
                'ondetach'          => 'delete'
            ],

            'price_adapters_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'sale\booking\BookingPriceAdapter',
                'foreign_field'     => 'booking_line_group_id',
                'description'       => 'Price adapters that apply to all lines of the group (based on group settings).'
            ],

            'booking_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\booking\Booking',
                'description'       => 'Booking the line relates to (for consistency, lines should be accessed using the group they belong to).',
                'required'          => true,
                'ondelete'          => 'cascade'        // delete group when parent booking is deleted
            ],

            'meal_preferences_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'sale\booking\MealPreference',
                'foreign_field'     => 'booking_line_group_id',
                'description'       => 'Meal preferences relating to the group.',
                'ondetach'          => 'delete'
            ],

            'total' => [
                'type'              => 'computed',
                'result_type'       => 'float',
                'usage'             => 'amount/money:4',
                'description'       => 'Total tax-excluded price for all lines (computed).',
                'function'          => 'calcTotal',
                'store'             => true
            ],

            'price' => [
                'type'              => 'computed',
                'result_type'       => 'float',
                'usage'             => 'amount/money:2',
                'description'       => 'Final tax-included price for all lines (computed).',
                'function'          => 'calcPrice',
                'store'             => true
            ],

            'fare_benefit' => [
                'type'              => 'computed',
                'result_type'       => 'float',
                'usage'             => 'amount/money:2',
                'description'       => 'Total amount of the fare banefit VAT incl.',
                'function'          => 'calcFareBenefit',
                'store'             => true
            ]

        ];
    }

    public static function onupdateBookingLinesIds($om, $oids, $values, $lang) {
        $om->callonce(__CLASS__, '_resetPrices', $oids, [], $lang);
    }

    /**
     * In case prices of a group are impacted, we need to resett parent booking and children lines as well.
     */
    public static function _resetPrices($om, $oids, $values, $lang) {
        // reset computed fields related to price
        $om->update(__CLASS__, $oids, ['total' => null, 'price' => null, 'fare_benefit' => null]);
        $groups = $om->read(__CLASS__, $oids, ['booking_id', 'booking_lines_ids', 'is_extra'], $lang);
        if($groups > 0) {
            $bookings_ids = array_map(function ($a) { return $a['booking_id']; }, $groups);
            // reset fields in parent bookings
            $om->callonce('sale\booking\Booking', '_resetPrices', $bookings_ids, [], $lang);
            // reset fields in children lines
            foreach($groups as $gid => $group) {
                // do not reset lines for extra-consumptions groups
                if(!$group['is_extra']) {
                    $om->callonce('sale\booking\BookingLine', '_resetPrices', $group['booking_lines_ids'], [], $lang);
                }
            }
        }
    }

    public static function calcHasSchedulableServices($om, $oids, $lang) {
        $result = [];
        $groups = $om->read(self::gettype(), $oids, ['booking_lines_ids']);
        foreach($groups as $gid => $group) {
            $result[$gid] = false;
            $lines = $om->read(BookingLine::gettype(), $group['booking_lines_ids'], ['product_id.product_model_id.type', 'product_id.product_model_id.service_type']);
            foreach($lines as $lid => $line) {
                if($line['product_id.product_model_id.type'] == 'service' && $line['product_id.product_model_id.service_type'] == 'schedulable') {
                    $result[$gid] = true;
                    break;
                }
            }
        }
        return $result;
    }

    public static function calcNbNights($om, $oids, $lang) {
        $result = [];
        $groups = $om->read(self::gettype(), $oids, ['date_from', 'date_to']);
        foreach($groups as $gid => $group) {
            $result[$gid] = round( ($group['date_to'] - $group['date_from']) / (60*60*24) );
        }
        return $result;
    }


    /**
     * Get total tax-excluded price of the group, with discount applied.
     *
     */
    public static function calcTotal($om, $oids, $lang) {
        $result = [];

        $groups = $om->read(get_called_class(), $oids, ['booking_id', 'booking_lines_ids.total']);

        $bookings_ids = [];

        foreach($groups as $oid => $group) {
            $bookings_ids[] = $group['booking_id'];
            $result[$oid] = array_reduce($group['booking_lines_ids.total'], function ($c, $a) {
                return $c + round($a['total'], 2);
            }, 0.0);
        }

        // reset parent booking total price
        $om->write('sale\booking\Booking', array_unique($bookings_ids), ['total' => null, 'price' => null]);

        return $result;
    }

    /**
     * Get final tax-included price of the group.
     *
     */
    public static function calcPrice($om, $oids, $lang) {
        $result = [];

        $groups = $om->read(get_called_class(), $oids, ['booking_lines_ids.price']);

        foreach($groups as $oid => $group) {
            $result[$oid] = array_reduce($group['booking_lines_ids.price'], function ($c, $a) {
                return $c + $a['price'];
            }, 0.0);
        }

        return $result;
    }

    /**
     * Retrieve sum of fare benefits granted on booking lines.
     *
     */
    public static function calcFareBenefit($om, $oids, $lang) {
        $result = [];

        $groups = $om->read(get_called_class(), $oids, ['booking_lines_ids.fare_benefit']);

        foreach($groups as $oid => $group) {
            $result[$oid] = array_reduce($group['booking_lines_ids.fare_benefit'], function ($c, $a) {
                return $c + $a['fare_benefit'];
            }, 0.0);
        }

        return $result;
    }


    /**
     * Check wether an object can be updated, and perform some additional operations if necessary.
     * This method can be overriden to define a more precise set of tests.
     *
     * @param  object   $om         ObjectManager instance.
     * @param  array    $oids       List of objects identifiers.
     * @param  array    $values     Associative array holding the new values to be assigned.
     * @param  string   $lang       Language in which multilang fields are being updated.
     * @return array    Returns an associative array mapping fields with their error messages. An empty array means that object has been successfully processed and can be updated.
     */
    public static function canupdate($om, $oids, $values, $lang='en') {

        $res = $om->read(get_called_class(), $oids, [ 'date_from', 'date_to' ]);

        if($res > 0) {
            foreach($res as $oids => $odata) {
                if($odata['date_from'] > $odata['date_to']) {
                    return ['date_from' => ['invalid_daterange' => 'End date must be greater or equal to Start date.']];
                }
            }
        }

        return parent::canupdate($om, $oids, $values, $lang);
    }

    public static function candelete($om, $oids) {
        $groups = $om->read(get_called_class(), $oids, ['booking_id']);

        if($groups) {
            foreach($groups as $gid => $group) {
                $om->update('sale\booking\Booking', $group['booking_id'], ['price' => null, 'total' => null]);
            }
        }

        return parent::candelete($om, $oids);
    }

}