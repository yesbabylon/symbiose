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

            'has_pack' => [
                'type'              => 'boolean',
                'description'       => 'Does the group relates to a pack?',
                'default'           => false
            ],

            'pack_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'lodging\sale\catalog\Product',
                'description'       => 'Pack (product) the group relates to, if any.',
                'visible'           => ['has_pack', '=', true],
                'onchange'          => 'sale\booking\BookingLineGroup::onchangePackId'
            ],

            'is_locked' => [
                'type'              => 'boolean',
                'description'       => 'Are modifications disabled for the group?',
                'default'           => false
            ],

            'date_from' => [
                'type'              => 'datetime',
                'description'       => "Time of arrival.",
                'onchange'          => 'sale\booking\BookingLineGroup::onchangeDateFrom'
            ],

            'date_to' => [
                'type'              => 'datetime',
                'description'       => "Time of departure."
            ],

            'sojourn_type' => [
                'type'              => 'string',
                'selection'         => ['GA', 'GG'],
                'default'           => 'GG',
                'description'       => 'The kind of sojourn the group is about.',
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
                'required'          => true,
                'onchange'          => 'sale\booking\BookingLineGroup::onchangeRateClassId'
            ],

            'booking_lines_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'sale\booking\BookingLine',
                'foreign_field'     => 'booking_line_group_id',
                'description'       => 'Booking lines that belong to the group.'
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
                'required'          => true
            ]

        ];
    }

    /**
     * Update is_locked field according to selected pack (pack_id).
     * This is done when pack_id is changed, but can be manually set by the user afterward.
     */
    public static function onchangePackId($om, $oids, $lang) {
        $res = $om->read(__CLASS__, $oids, ['pack_id.is_locked']);
        foreach($res as $oid => $odata) {
            $om->write(__CLASS__, $oid, [ 'is_locked' => $odata['pack_id.is_locked'] ], $lang);
        }
    }

// #todo - _updatePriceAdapters should be called upon customer change

    public static function onchangeRateClassId($om, $oids, $lang) {
        self::_updatePriceAdapters($om, $oids, $lang);
    }

    public static function onchangeDateFrom($om, $oids, $lang) {
        self::_updatePriceAdapters($om, $oids, $lang);
        $booking_lines_ids = $om->read(__CLASS__, $oids, ['booking_lines_ids']);
        BookingLine::_updatePriceId($om, $booking_lines_ids, $lang);
    }

    /**
     * Create Price adapters according to group settings.
     */
    public static function _updatePriceAdapters($om, $oids, $lang) {
        /*
            Remove all previous price adapters that were automatically created
        */
        $price_adapters_ids = $om->search('sale\booking\BookingPriceAdapter', [ ['booking_line_group_id', 'in', $oids], ['is_manual_discount','=', false]]);
        $orm->remove('sale\booking\BookingPriceAdapter', $price_adapters_ids, true);

        $line_groups = $om->read(__CLASS__, $oids, ['rate_class_id.name', 'date_from', 'date_to', 'nb_pers',
                                                    'booking_id.customer_id.count_booking_24',
                                                    'booking_id.center_id.season_category_id',
                                                    'booking_id.center_id.discount_list_category_id']);
        foreach($line_groups as $group) {
            /*
                Find the first Discount List that matches the booking dates
            */
            $discount_lists_ids = $om->search('sale\discount\DiscountList', [
                ['discount_list_category_id', '=', $group['booking_id.center_id.discount_list_category_id']],
                ['valid_from', '<=', $group['date_from']],
                ['valid_until', '>=', $group['date_from']]
            ]);

            $discount_lists = $om->read('sale\discount\DiscountList', $discount_lists_ids, ['id', 'discounts_ids']);
            $discount_list_id = 0;
            if($discount_lists > 0 && count($discount_lists)) {
                $discount_list_id = array_shift(array_keys($discount_lists));
            }
            /*
                Search for matching Discounts within the found Discount List
            */
            if($discount_list_id) {
                $operands = [];
                $operands['count_booking_24'] = $group['booking_id.customer_id.count_booking_24'];
                $operands['rate_class'] = $group['rate_class_id.name'];                         // rate class name (T1, T2, ...)
                $operands['duration'] = ($group['date_to']-$group['date_from'])/(60*60*24);     // duration in nights
                $operands['nb_pers'] = ($group['date_to']-$group['date_from'])/(60*60*24);     // duration in nights

                $season_category = $group['booking_id.center_id.season_category_id'];
                $date = $group['date_from'];
                // pick up the first season that matches the year and the season category of the center
                $year = date('Y', $date);
                $seasons_ids = $om->search('sale\season\SeasonPeriod', [
                    ['season_category_id', '=', $group['booking_id.center_id.season_category_id']],
                    ['date_from', '<=', $group['date_from']],
                    ['date_to', '>=', $group['date_from']],
                    ['year', '=', $year]
                ]);

                $periods = $om->read('sale\season\SeasonPeriod', $seasons_ids, ['id', 'season_type_id']);
                if($periods > 0 && count($periods)){
                    $period = array_shift($periods);
                    $operands['season'] = $period['season_type_id'];
                }


                $discounts_ids = $om->search('sale\discount\DiscountList', [
                    ['discount_list_category_id', '=', $group['booking_id.center_id.discount_list_category_id']],
                    ['valid_from', '<=', $group['date_from']],
                    ['valid_until', '>=', $group['date_from']]
                ]);

                $discounts = $om->read('sale\discount\Discount', $discount_lists[$discount_list_id]['discounts_ids'], ['value', 'type', 'conditions_ids']);

                foreach($discounts as $d_id => $discount) {
                    $conditions = $om->read('sale\discount\Condition', $discount['conditions_ids'], ['operand', 'operator', 'value']);
                    $valid = true;
                    foreach($conditions as $c_id => $condition) {
                        if(!isset($operands[$condition['operator']])) {
                            $valid = false;
                            break;
                        }
                        $operand = $operands[$condition['operator']];
                        $valid = $valid & eval("return ( {$condition['operator']} {$condition['operator']} {$condition['value']});");
                    }
                    if($valid) {
                        // create corresponding Price Adapter
                    }
                }
            }
            else {
                $date = date('Y-m-d', $line['date_from']);
                trigger_error("QN_DEBUG_ORM::no matching discount list found for date {$date}", QN_REPORT_DEBUG);
            }
        }
    }
}