<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace lodging\sale\booking;


class BookingLineGroup extends \sale\booking\BookingLineGroup {

    public static function getName() {
        return "Booking line group";
    }

    public static function getDescription() {
        return "Booking line groups are related to a booking and describe one or more sojourns and their related consumptions.";
    }

    public static function getColumns() {
        return [
            'is_sojourn' => [
                'type'              => 'boolean',
                'description'       => 'Does the group spans over several nights and relate to accomodations?',
                'default'           => false,
                'onupdate'          => 'onupdateIsSojourn'
            ],

            'has_locked_rental_units' => [
                'type'              => 'boolean',
                'description'       => 'Can the rental units assingments be changed?',
                'default'           => false
            ],

            'has_pack' => [
                'type'              => 'boolean',
                'description'       => 'Does the group relates to a pack?',
                'default'           => false,
                'onupdate'          => 'onupdateHasPack'
            ],

            'pack_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'lodging\sale\catalog\Product',
                'description'       => 'Pack (product) the group relates to, if any.',
                'visible'           => ['has_pack', '=', true],
                'onupdate'          => 'onupdatePackId'
            ],

            'price_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\price\Price',
                'description'       => 'The price (retrieved by price list) the pack relates to.',
                'visible'           => ['has_pack', '=', true],
                'onupdate'          => 'onupdatePriceId'
            ],

            'vat_rate' => [
                'type'              => 'computed',
                'result_type'       => 'float',
                'description'       => 'VAT rate that applies to this group, when relating to a pack_id.',
                'function'          => 'calcVatRate',
                'store'             => true,
                'visible'           => ['has_pack', '=', true],
            ],

            'unit_price' => [
                'type'              => 'computed',
                'result_type'       => 'float',
                'usage'             => 'amount/money:4',
                'description'       => 'Tax-excluded unit price (with automated discounts applied).',
                'function'          => 'calcUnitPrice',
                'store'             => true,
                'visible'           => ['has_pack', '=', true]
            ],

            'is_autosale' => [
                'type'              => 'boolean',
                'description'       => 'Does the group relate to autosale products?',
                'default'           => false
            ],

            'is_locked' => [
                'type'              => 'boolean',
                'description'       => 'Are modifications disabled for the group?',
                'default'           => false,
                'visible'           => ['has_pack', '=', true],
                'onupdate'          => 'onupdateIsLocked'
            ],

            'qty' => [
                'type'              => 'computed',
                'result_type'       => 'float',
                'description'       => 'Quantity of product items for the group (pack).',
                'function'          => 'calcQty',
                'visible'           => ['has_pack', '=', true]
            ],

            'date_from' => [
                'type'              => 'date',
                'description'       => "Day of arrival.",
                'onupdate'          => 'onupdateDateFrom',
                'default'           => time()
            ],

            'date_to' => [
                'type'              => 'date',
                'description'       => "Day of departure.",
                'default'           => time(),
                'onupdate'          => 'onupdateDateTo'
            ],

            'time_from' => [
                'type'              => 'time',
                'description'       => "Checkin time on the day of arrival.",
                'default'           => 14 * 3600,
                'onupdate'          => 'onupdateTimeFrom'
            ],

            'time_to' => [
                'type'              => 'time',
                'description'       => "Checkout time on the day of departure.",
                'default'           => 10 * 3600,
                'onupdate'          => 'onupdateTimeTo'
            ],

            'sojourn_type_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'lodging\sale\booking\SojournType',
                'description'       => 'The kind of sojourn the group is about.',
                'default'           => 1,       // 'GA'
                'onupdate'          => 'onupdateSojournTypeId',
                'visible'           => ['is_sojourn', '=', true]
            ],

            'nb_pers' => [
                'type'              => 'integer',
                'description'       => 'Amount of persons this group is about.',
                'default'           => 1,
                'onupdate'          => 'onupdateNbPers'
            ],

            /* a booking can be split into several groups on which distinct rate classes apply, by default the rate_class of the customer is used */
            'rate_class_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\customer\RateClass',
                'description'       => "The fare class that applies to the group.",
                'default'           => 4,                       // default to 'general public'
                'onupdate'          => 'onupdateRateClassId'
            ],

            'booking_lines_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'lodging\sale\booking\BookingLine',
                'foreign_field'     => 'booking_line_group_id',
                'description'       => 'Booking lines that belong to the group.',
                'ondetach'          => 'delete',
                'order'             => 'order',
                'onupdate'          => 'onupdateBookingLinesIds'
            ],

            'price_adapters_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'lodging\sale\booking\BookingPriceAdapter',
                'foreign_field'     => 'booking_line_group_id',
                'description'       => 'Price adapters that apply to all lines of the group (based on group settings).'
            ],

            'booking_id' => [
                'type'              => 'many2one',
                'foreign_object'    => Booking::getType(),
                'description'       => 'Booking the line relates to (for consistency, lines should be accessed using the group they belong to).',
                'required'          => true,
                'ondelete'          => 'cascade'         // delete group when parent booking is deleted
            ],

            // we mean rental_units_ids (for rental units assignment)
            // #todo - deprecate
            'accomodations_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => BookingLine::getType(),
                'foreign_field'     => 'booking_line_group_id',
                'description'       => 'Booking lines relating to accomodations.',
                'ondetach'          => 'delete',
                'domain'            => ['is_rental_unit', '=', true]
            ],

            'sojourn_product_models_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'lodging\sale\booking\SojournProductModel',
                'foreign_field'     => 'booking_line_group_id',
                'description'       => "The product models groups assigned to the sojourn (from lines).",
                'ondetach'          => 'delete'
            ],

            'rental_unit_assignments_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'lodging\sale\booking\SojournProductModelRentalUnitAssignement',
                'foreign_field'     => 'booking_line_group_id',
                'description'       => "The rental units assigned to the group (from lines).",
                'ondetach'          => 'delete'
            ],

            'age_range_assignments_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'lodging\sale\booking\BookingLineGroupAgeRangeAssignment',
                'foreign_field'     => 'booking_line_group_id',
                'description'       => 'Age range assignments defined for the group.',
                'ondetach'          => 'ondetachAgeRange'
            ],

            'total' => [
                'type'              => 'computed',
                'result_type'       => 'float',
                'description'       => 'Total tax-excluded price for all lines (computed).',
                'function'          => 'calcTotal',
                'store'             => true
            ],

            'price' => [
                'type'              => 'computed',
                'result_type'       => 'float',
                'description'       => 'Final tax-included price for all lines (computed).',
                'function'          => 'calcPrice',
                'store'             => true
            ]

        ];
    }

    /**
     *
     */
    public static function oncreate($om, $oids, $values, $lang) {

    }

    public static function calcVatRate($om, $oids, $lang) {
        $result = [];
        $lines = $om->read(self::getType(), $oids, ['price_id.accounting_rule_id.vat_rule_id.rate']);
        foreach($lines as $oid => $odata) {
            $result[$oid] = floatval($odata['price_id.accounting_rule_id.vat_rule_id.rate']);
        }
        return $result;
    }

    public static function calcQty($om, $oids, $lang) {
        $result = [];
        $groups = $om->read(self::getType(), $oids, ['has_pack', 'is_locked', 'pack_id.product_model_id.qty_accounting_method', 'nb_pers', 'nb_nights']);
        foreach($groups as $gid => $group) {
            $result[$gid] = 1;
            if($group['has_pack'] && $group['is_locked']) {
                // apply quantity (either nb_pers or nb_nights) and price adapters
                if($group['pack_id.product_model_id.qty_accounting_method'] == 'accomodation') {
                    $qty = $group['nb_nights'];
                }
                else if($group['pack_id.product_model_id.qty_accounting_method'] == 'person') {
                    $qty = $group['nb_pers'] * $group['nb_nights'];
                }
                else {
                    $qty = $group['nb_pers'];
                }
                $result[$gid] = floatval($qty);
            }
        }
        return $result;
    }

    /**
     * Compute the VAT excl. unit price of the group, with automated discounts applied.
     *
     */
    public static function calcUnitPrice($om, $oids, $lang) {
        $result = [];

        $groups = $om->read(self::getType(), $oids, ['price_id.price']);

        if($groups > 0 && count($groups)) {
            foreach($groups as $gid => $group) {

                $price_adapters_ids = $om->search(BookingPriceAdapter::getType(), [
                    ['booking_line_group_id', '=', $gid],
                    ['booking_line_id','=', 0],
                    ['is_manual_discount', '=', false]
                ]);

                $disc_value = 0.0;
                $disc_percent = 0.0;

                if($price_adapters_ids > 0) {
                    $adapters = $om->read(BookingPriceAdapter::getType(), $price_adapters_ids, ['type', 'value', 'discount_id.discount_list_id.rate_max']);

                    if($adapters > 0) {
                        foreach($adapters as $aid => $adata) {
                            if($adata['type'] == 'amount') {
                                $disc_value += $adata['value'];
                            }
                            else if($adata['type'] == 'percent') {
                                if($adata['discount_id.discount_list_id.rate_max'] && ($disc_percent + $adata['value']) > $adata['discount_id.discount_list_id.rate_max']) {
                                    $disc_percent = $adata['discount_id.discount_list_id.rate_max'];
                                }
                                else {
                                    $disc_percent += $adata['value'];
                                }
                            }
                        }
                    }
                }

                $result[$gid] = round(($group['price_id.price'] * (1-$disc_percent)) - $disc_value, 2);
            }
        }
        return $result;
    }

    /**
     * Compute the VAT incl. total price of the group (pack), with manual and automated discounts applied.
     *
     */
    public static function calcPrice($om, $oids, $lang) {
        $result = [];

        $groups = $om->read(self::getType(), $oids, ['booking_lines_ids', 'total', 'vat_rate', 'is_locked', 'has_pack']);

        if($groups > 0 && count($groups)) {
            foreach($groups as $gid => $group) {
                $result[$gid] = 0.0;

                // if the group relates to a pack and the product_model targeted by the pack has its own Price, then this is the one to return
                if($group['has_pack'] && $group['is_locked']) {
                    $result[$gid] = round($group['total'] * (1 + $group['vat_rate']), 2);
                }
                // otherwise, price is the sum of bookingLines prices
                else {
                    $lines = $om->read('lodging\sale\booking\BookingLine', $group['booking_lines_ids'], ['price']);
                    if($lines > 0 && count($lines)) {
                        foreach($lines as $line) {
                            $result[$gid] += $line['price'];
                        }
                        $result[$gid] = round($result[$gid], 2);
                    }
                }
            }
        }
        return $result;
    }

    public static function calcTotal($om, $oids, $lang) {
        $result = [];
        $groups = $om->read(self::getType(), $oids, ['booking_id', 'booking_lines_ids', 'is_locked', 'has_pack', 'unit_price', 'qty']);
        $bookings_ids = [];

        if($groups > 0 && count($groups)) {
            foreach($groups as $gid => $group) {
                $result[$gid] = 0.0;

                $bookings_ids[] = $group['booking_id'];
                // if the group relates to a pack and the product_model targeted by the pack has its own Price, then this is the one to return
                if($group['has_pack'] && $group['is_locked']) {
                    $result[$gid] = $group['unit_price'] * $group['qty'];
                }
                // otherwise, price is the sum of bookingLines totals
                else {
                    $lines = $om->read('lodging\sale\booking\BookingLine', $group['booking_lines_ids'], ['total']);
                    if($lines > 0 && count($lines)) {
                        foreach($lines as $line) {
                            $result[$gid] += $line['total'];
                        }
                        $result[$gid] = $result[$gid];
                    }
                }
            }
        }

        return $result;
    }

    /**
     * @param \equal\orm\ObjectManager  $om
     */
    public static function onupdateIsSojourn($om, $oids, $values, $lang) {
        $groups = $om->read(self::getType(), $oids, ['booking_id', 'nb_pers', 'is_sojourn', 'age_range_assignments_ids'], $lang);
        if($groups > 0) {
            foreach($groups as $gid => $group) {
                // remove any previously set assignments
                $om->delete(BookingLineGroupAgeRangeAssignment::getType(), $group['age_range_assignments_ids'], true);

                if($group['is_sojourn']) {
                    // create default age_range assignment
                    $assignment = [
                        'age_range_id'          => 1,                       // adults
                        'booking_line_group_id' => $gid,
                        'booking_id'            => $group['booking_id'],
                        'qty'                   => $group['nb_pers']
                    ];
                    $om->create(BookingLineGroupAgeRangeAssignment::getType(), $assignment, $lang);
                }
            }
        }
    }

    public static function onupdateHasPack($om, $oids, $values, $lang) {
        trigger_error("QN_DEBUG_ORM::calling lodging\sale\booking\BookingLineGroup:onchangeHasPack", QN_REPORT_DEBUG);

        $groups = $om->read(self::getType(), $oids, ['has_pack', 'booking_lines_ids']);
        if($groups > 0 && count($groups)) {
            foreach($groups as $gid => $group) {
                if(!$group['has_pack']) {
                    // remove existing booking_lines
                    $om->update(self::getType(), $gid, ['booking_lines_ids' => array_map(function($a) { return "-$a";}, $group['booking_lines_ids'])]);
                    // reset lock and pack_id
                    $om->update(self::getType(), $gid, ['is_locked' => false, 'pack_id' => null ]);
                }
            }
        }
    }

    public static function onupdateIsLocked($om, $oids, $values, $lang) {
        trigger_error("QN_DEBUG_ORM::calling lodging\sale\booking\BookingLineGroup:onchangeIsLocked", QN_REPORT_DEBUG);
        // invalidate prices
        $om->callonce('sale\booking\BookingLineGroup', '_resetPrices', $oids, [], $lang);
        $om->callonce(self::getType(), '_updatePriceId', $oids, [], $lang);
    }

    public static function onupdatePriceId($om, $oids, $values, $lang) {
        // invalidate prices
        $om->callonce('sale\booking\BookingLineGroup', '_resetPrices', $oids, [], $lang);
    }

    /**
     * Handler called after pack_id has changed.
     * Updates is_locked field according to selected pack (pack_id).
     * (is_locked can be manually set by the user afterward)
     *
     * Since this method is called, we assume that current group has 'has_pack' set to true,
     * and that pack_id relates to a product that is a pack.
     */
    public static function onupdatePackId($om, $oids, $values, $lang) {
        trigger_error("QN_DEBUG_ORM::calling lodging\sale\booking\BookingLineGroup:onchangePackId", QN_REPORT_DEBUG);

        $groups = $om->read(self::getType(), $oids, [
            'booking_id',
            'date_from',
            'nb_pers',
            'is_locked',
            'booking_lines_ids',
            'age_range_assignments_ids',
            'pack_id.has_age_range',
            'pack_id.age_range_id',
            'pack_id.product_model_id.qty_accounting_method',
            'pack_id.product_model_id.has_duration',
            'pack_id.product_model_id.duration',
            'pack_id.product_model_id.capacity',
            'pack_id.product_model_id.booking_type_id'
        ]);

        // pass-1 : update age ranges for packs with a specific age range
        foreach($groups as $gid => $group) {
            if($group['pack_id.has_age_range']) {
                // remove any previously set assignments
                $om->delete(BookingLineGroupAgeRangeAssignment::getType(), $group['age_range_assignments_ids'], true);
                // create default age_range assignment
                $assignment = [
                    'age_range_id'          => $group['pack_id.age_range_id'],
                    'booking_line_group_id' => $gid,
                    'booking_id'            => $group['booking_id'],
                    'qty'                   => $group['nb_pers']
                ];
                $om->create(BookingLineGroupAgeRangeAssignment::getType(), $assignment, $lang);
            }
        }

        // invalidate prices
        $om->callonce('sale\booking\BookingLineGroup', '_resetPrices', $oids, [], $lang);

        // (re)generate booking lines
        $om->callonce(self::getType(), '_updatePack', $oids, [], $lang);

        // pass-2 : update groups and related bookings, if necessary
        foreach($groups as $gid => $group) {
            // if model of chosen product has a non-generic booking type, update the booking of the group accordingly
            if(isset($group['pack_id.product_model_id.booking_type_id']) && $group['pack_id.product_model_id.booking_type_id'] != 1) {
                $om->update('lodging\sale\booking\Booking', $group['booking_id'], ['type_id' => $group['pack_id.product_model_id.booking_type_id']]);
            }

            $updated_fields = ['vat_rate' => null];

            // if targeted product model has its own duration, date_to is updated accordingly
            if($group['pack_id.product_model_id.has_duration']) {
                $updated_fields['date_to'] = $group['date_from'] + ($group['pack_id.product_model_id.duration'] * 60*60*24);
                // will update price_adapters, nb_nights
            }

            // always update nb_pers
            // to make sure to trigger self::updatePriceAdapters and BookingLine::_updateQty
            $updated_fields['nb_pers'] = $group['nb_pers'];
            if($group['pack_id.product_model_id.qty_accounting_method'] == 'accomodation' && $group['pack_id.product_model_id.capacity'] > 0) {
                $updated_fields['nb_pers'] = $group['pack_id.product_model_id.capacity'];
            }

            $om->update(self::getType(), $gid, $updated_fields, $lang);
        }
    }

    public static function onupdateDateFrom($om, $oids, $values, $lang) {
        trigger_error("QN_DEBUG_ORM::calling lodging\sale\booking\BookingLineGroup:onchangeDateFrom", QN_REPORT_DEBUG);
        // invalidate prices
        $om->callonce('sale\booking\BookingLineGroup', '_resetPrices', $oids, [], $lang);

        $om->update(self::getType(), $oids, ['nb_nights' => null ]);
        $om->callonce(self::getType(), 'updatePriceAdapters', $oids, [], $lang);
        $om->callonce(self::getType(), '_updateAutosaleProducts', $oids, [], $lang);

        // update bookinglines
        $groups = $om->read(self::getType(), $oids, ['booking_id', 'is_sojourn', 'has_pack', 'nb_nights', 'booking_lines_ids']);
        if($groups > 0 && count($groups)) {
            foreach($groups as $group) {
                // notify booking lines that price_id has to be updated
                $om->callonce('lodging\sale\booking\BookingLine', '_updatePriceId', $group['booking_lines_ids'], [], $lang);
                // recompute bookinglines quantities
                $om->callonce('lodging\sale\booking\BookingLine', '_updateQty', $group['booking_lines_ids'], [], $lang);
                if($group['is_sojourn']) {
                    // force parent booking to recompute date_from
                    $om->update('lodging\sale\booking\Booking', $group['booking_id'], ['date_from' => null]);
                }
            }
        }
    }

    public static function onupdateDateTo($om, $oids, $values, $lang) {
        trigger_error("QN_DEBUG_ORM::calling lodging\sale\booking\BookingLineGroup:onchangeDateTo", QN_REPORT_DEBUG);
        // invalidate prices
        $om->callonce('sale\booking\BookingLineGroup', '_resetPrices', $oids, [], $lang);

        $om->update(self::getType(), $oids, ['nb_nights' => null ]);
        $om->callonce(self::getType(), 'updatePriceAdapters', $oids, [], $lang);
        $om->callonce(self::getType(), '_updateAutosaleProducts', $oids, [], $lang);

        // update bookinglines
        $groups = $om->read(self::getType(), $oids, ['booking_id', 'is_sojourn', 'is_event', 'has_pack', 'nb_nights', 'nb_pers', 'booking_lines_ids']);
        if($groups > 0) {
            foreach($groups as $group) {
                // re-compute bookinglines quantities
                $om->callonce('lodging\sale\booking\BookingLine', '_updateQty', $group['booking_lines_ids'], [], $lang);
                if($group['is_sojourn'] || $group['is_event']) {
                    // force parent booking to recompute date_from
                    $om->update('lodging\sale\booking\Booking', $group['booking_id'], ['date_to' => null]);
                }
            }
        }
    }

    public static function onupdateTimeFrom($om, $oids, $values, $lang) {
        trigger_error("QN_DEBUG_ORM::calling lodging\sale\booking\BookingLineGroup:onupdateTimeTo", QN_REPORT_DEBUG);

        // update parent booking
        $groups = $om->read(self::getType(), $oids, ['booking_id', 'is_sojourn', 'is_event'], $lang);
        if($groups > 0) {
            foreach($groups as $group) {
                if($group['is_sojourn'] || $group['is_event']) {
                    // force parent booking to recompute time_from
                    $om->update('lodging\sale\booking\Booking', $group['booking_id'], ['time_from' => null]);
                }
            }
        }
    }

    public static function onupdateTimeTo($om, $oids, $values, $lang) {
        trigger_error("QN_DEBUG_ORM::calling lodging\sale\booking\BookingLineGroup:onupdateTimeTo", QN_REPORT_DEBUG);

        // update parent booking
        $groups = $om->read(self::getType(), $oids, ['booking_id', 'is_sojourn', 'is_event'], $lang);
        if($groups > 0) {
            foreach($groups as $group) {
                if($group['is_sojourn'] || $group['is_event']) {
                    // force parent booking to recompute time_to
                    $om->update('lodging\sale\booking\Booking', $group['booking_id'], ['time_to' => null]);
                }
            }
        }
    }

    public static function onupdateBookingLinesIds($om, $oids, $values, $lang) {
        // recompute sojourn prices
        $om->callonce('sale\booking\BookingLineGroup', '_resetPrices', $oids, [], $lang);
        // reset rental units assignments
        $om->callonce(self::getType(), 'createRentalUnitsAssignments', $oids, [], $lang);
        // force parent booking to recompute times and prices
        $groups = $om->read(self::getType(), $oids, ['booking_id'], $lang);
        if($groups > 0) {
            $bookings_ids = array_map(function($a) {return $a['booking_id'];}, $groups);
            $om->update('lodging\sale\booking\Booking', $bookings_ids, ['time_from' => null, 'time_to' => null, 'total' => null, 'price' => null]);
        }
    }

    public static function onupdateRateClassId($om, $oids, $values, $lang) {
        trigger_error("QN_DEBUG_ORM::calling lodging\sale\booking\BookingLineGroup:onchangeRateClassId", QN_REPORT_DEBUG);
        $groups = $om->read(self::getType(), $oids, ['booking_id', 'rate_class_id.name'], $lang);
        // #todo - add support for assigning an optional booking_type_id to each rate_class
        foreach($groups as $gid => $group) {
            // if model of chosen product has a non-generic booking type, update the booking of the group accordingly
            if($group['rate_class_id.name'] == 'T5' || $group['rate_class_id.name'] == 'T7') {
                $om->update('lodging\sale\booking\Booking', $group['booking_id'], ['type_id' => 4]);
            }
        }
        $om->callonce('sale\booking\BookingLineGroup', '_resetPrices', $oids, [], $lang);
        $om->callonce(self::getType(), 'updatePriceAdapters', $oids, [], $lang);
    }

    public static function onupdateSojournTypeId($om, $oids, $values, $lang) {
        trigger_error("QN_DEBUG_ORM::calling lodging\sale\booking\BookingLineGroup:onchangeSojournTypeId", QN_REPORT_DEBUG);
        $om->callonce('sale\booking\BookingLineGroup', '_resetPrices', $oids, [], $lang);
        $om->callonce(self::getType(), 'updatePriceAdapters', $oids, [], $lang);
    }

    public static function onupdateNbPers($om, $oids, $values, $lang) {
        trigger_error("QN_DEBUG_ORM::calling lodging\sale\booking\BookingLineGroup:onchangeNbPers", QN_REPORT_DEBUG);

        // 1) invalidate prices
        $om->callonce(self::getType(), '_resetPrices', $oids, [], $lang);

        $groups = $om->read(self::getType(), $oids, [
                'booking_id',
                'nb_pers',
                'booking_lines_ids',
                'is_sojourn',
                'age_range_assignments_ids'
            ]);

        // 2) reset parent bookings nb_pers
        if($groups > 0) {
            $bookings_ids = array_map(function($a) {return $a['booking_id'];}, $groups);
            $om->update(Booking::getType(), $bookings_ids, ['nb_pers' => null]);
        }

        // 3) update agerange assignments (for single assignment)
        if($groups > 0) {
            $booking_lines_ids = [];
            foreach($groups as $group) {
                if($group['is_sojourn'] && count($group['age_range_assignments_ids']) == 1) {
                    $age_range_assignment_id = reset($group['age_range_assignments_ids']);
                    $om->update(BookingLineGroupAgeRangeAssignment::getType(), $age_range_assignment_id, ['qty' => $group['nb_pers']]);
                }
                $booking_lines_ids = array_merge($group['booking_lines_ids']);
                // trigger sibling groups nb_pers update (this is necessary since the nb_pers is based on the booking total participants)
            }
            // re-compute bookinglines quantities
            $om->update(BookingLine::getType(), $booking_lines_ids, ['qty_vars' => null], $lang);
            $om->callonce(BookingLine::getType(), '_updateQty', $booking_lines_ids, [], $lang);
        }

        // 4) update dependencies
        $om->callonce(self::getType(), 'createRentalUnitsAssignments', $oids, [], $lang);
        $om->callonce(self::getType(), 'updatePriceAdapters', $oids, [], $lang);
        $om->callonce(self::getType(), '_updateAutosaleProducts', $oids, [], $lang);
        $om->callonce(self::getType(), '_updateMealPreferences', $oids, [], $lang);
    }



    /**
     * Check wether an object can be created, and optionally perform additional operations.
     * These tests come in addition to the unique constraints return by method `getUnique()`.
     * This method can be overriden to define a more precise set of tests.
     *
     * @param  \equal\orm\ObjectManager     $om         ObjectManager instance.
     * @param  array                        $values     Associative array holding the values to be assigned to the new instance (not all fields might be set).
     * @param  string                       $lang       Language in which multilang fields are being updated.
     * @return array    Returns an associative array mapping fields with their error messages. En empty array means that object has been successfully processed and can be created.
     */
    public static function cancreate($om, $values, $lang) {
        $bookings = $om->read(Booking::getType(), $values['booking_id'], ['status'], $lang);

        if($bookings) {
            $booking = reset($bookings);

            if( in_array($booking['status'], ['invoiced', 'debit_balance', 'credit_balance', 'balanced'])
                || ($booking['status'] != 'quote' && (!isset($values['is_extra']) ||!$values['is_extra'])) ) {
                return ['status' => ['non_editable' => 'Non-extra service lines cannot be changed for non-quote bookings.']];
            }
        }

        return parent::cancreate($om, $values, $lang);
    }

    /**
     * Check wether an object can be updated, and perform some additional operations if necessary.
     * This method can be overriden to define a more precise set of tests.
     *
     * @param  \equal\orm\ObjectManager     $om         ObjectManager instance.
     * @param  array                        $oids       List of objects identifiers.
     * @param  array                        $values     Associative array holding the new values to be assigned.
     * @param  string                       $lang       Language in which multilang fields are being updated.
     * @return array    Returns an associative array mapping fields with their error messages. An empty array means that object has been successfully processed and can be updated.
     */
    public static function canupdate($om, $oids, $values, $lang=DEFAULT_LANG) {
        $groups = $om->read(get_called_class(), $oids, ['booking_id.status', 'is_extra', 'age_range_assignments_ids', 'sojourn_product_models_ids'], $lang);

        if($groups > 0) {
            foreach($groups as $group) {
                if($group['is_extra']) {
                    if(!in_array($group['booking_id.status'], ['confirmed', 'validated', 'checkedin', 'checkedout'])) {
                        return ['status' => ['non_editable' => 'Extra services can only be changed after confirmation and before invoicing.']];
                    }
                }
                else {
                    if($group['booking_id.status'] != 'quote') {
                        return ['status' => ['non_editable' => 'Non-extra services can only be changed for quote bookings.']];
                    }
                }
                if(isset($values['nb_pers']) && count($group['age_range_assignments_ids']) > 1 ) {
                    $assignments = $om->read(BookingLineGroupAgeRangeAssignment::getType(), $group['age_range_assignments_ids'], ['qty'], $lang);
                    $qty = array_reduce($assignments, function($c, $a) { return $c+$a['qty']; }, 0);
                    if($values['nb_pers'] != $qty) {
                        return ['nb_pers' => ['count_mismatch' => 'Number of persons does not match the age ranges.']];
                    }
                }
                if(isset($values['has_locked_rental_units']) && $values['has_locked_rental_units']) {
                    if(!count($group['sojourn_product_models_ids'])) {
                        return ['has_locked_rental_units' => ['invalid_status' => 'Cannot lock an empty assignment.']];
                    }
                }

            }
        }

        return parent::canupdate($om, $oids, $values, $lang);
    }

    /**
     * Check wether an object can be deleted, and perform some additional operations if necessary.
     * This method can be overriden to define a more precise set of tests.
     *
     * @param  \equal\orm\ObjectManager     $om         ObjectManager instance.
     * @param  array                        $oids       List of objects identifiers.
     * @return boolean  Returns true if the object can be deleted, or false otherwise.
     */
    public static function candelete($om, $oids) {
        $groups = $om->read(self::getType(), $oids, ['booking_id.status', 'is_extra']);

        if($groups > 0) {
            foreach($groups as $group) {
                if($group['is_extra']) {
                    if(!in_array($group['booking_id.status'], ['confirmed', 'validated', 'checkedin', 'checkedout'])) {
                        return ['status' => ['non_editable' => 'Extra services can only be changed after confirmation and before invoicing.']];
                    }
                }
                else {
                    if($group['booking_id.status'] != 'quote') {
                        return ['status' => ['non_editable' => 'Non-extra services can only be changed for quote bookings.']];
                    }
                }
            }
        }

        return parent::candelete($om, $oids);
    }

    /**
     * Hook invoked before object deletion for performing object-specific additional operations.
     *
     * @param  \equal\orm\ObjectManager     $om         ObjectManager instance.
     * @param  array                        $oids       List of objects identifiers.
     * @return void
     */
    public static function ondelete($om, $oids) {
        // trigger an update of parent booking nb_pers + sibling groups prices adapters
        $om->update(self::getType(), $oids, ['nb_pers' => 0]);
        return parent::ondelete($om, $oids);
    }

    public static function ondetachAgeRange($om, $oids, $detached_ids, $lang) {

        // retrieve age ranges being removed
        $age_range_ids = [];
        $assignments = $om->read(BookingLineGroupAgeRangeAssignment::getType(), $detached_ids, ['age_range_id'], $lang);
        if($assignments > 0) {
            $age_range_ids = array_map(function($a) {return $a['age_range_id'];}, $assignments);
        }

        // remove lines with a product_id referring to the removed age ranges
        $groups = $om->read(self::getType(), $oids, ['booking_lines_ids'], $lang);
        if($groups > 0 && count($groups)) {
            foreach($groups as $gid => $group) {
                $lines = $om->read(BookingLine::getType(), $group['booking_lines_ids'], ['product_id.has_age_range', 'product_id.age_range_id'], $lang);
                $lines_ids_to_remove = [];
                if($lines > 0 && count($lines)) {
                    foreach($lines as $lid => $line) {
                        if($line['product_id.has_age_range']) {
                            if(in_array($line['product_id.age_range_id'], $age_range_ids)) {
                                $lines_ids_to_remove[] = -$lid;
                            }
                        }
                    }
                    // will trigger onupdateBookingLinesIds
                    $om->update(self::getType(), $gid, ['booking_lines_ids' => $lines_ids_to_remove], $lang);
                }
            }
        }

        // actually remove the age ranges
        $om->remove(BookingLineGroupAgeRangeAssignment::getType(), $detached_ids, true);
    }

    /**
     * Create Price adapters according to group settings.
     *
     * Price adapters are applied only on meal and accomodation products
     *
     * (This method is called upon booking_id.customer_id change)
     */
    public static function updatePriceAdapters($om, $oids, $values, $lang) {
        trigger_error("QN_DEBUG_ORM::calling lodging\sale\booking\BookingLineGroup:updatePriceAdapters (".implode(',', $oids).")", QN_REPORT_DEBUG);
        /*
            Remove all previous price adapters that were automatically created
        */
        $price_adapters_ids = $om->search('lodging\sale\booking\BookingPriceAdapter', [ ['booking_line_group_id', 'in', $oids], ['is_manual_discount','=', false]]);

        $om->remove('lodging\sale\booking\BookingPriceAdapter', $price_adapters_ids, true);

        $line_groups = $om->read(self::getType(), $oids, [
                'rate_class_id',
                'sojourn_type_id',
                'date_from',
                'date_to',
                'nb_pers',
                'nb_nights',
                'booking_id',
                'is_locked',
                'booking_lines_ids',
                'booking_id.nb_pers',
                'booking_id.customer_id.count_booking_24',
                'booking_id.center_id.season_category_id',
                'booking_id.center_id.discount_list_category_id'
            ]);

        foreach($line_groups as $group_id => $group) {
            /*
                Find the first Discount List that matches the booking dates
            */

            // the discount list category to use is the one defined for the center, unless it is ('GA' or 'GG') AND sojourn_type <> category.name
            $discount_category_id = $group['booking_id.center_id.discount_list_category_id'];

            if(in_array($discount_category_id, [1 /*GA*/, 2 /*GG*/]) && $discount_category_id != $group['sojourn_type_id']) {
                $discount_category_id = $group['sojourn_type_id'];
            }

            $discount_lists_ids = $om->search('sale\discount\DiscountList', [
                ['rate_class_id', '=', $group['rate_class_id']],
                ['discount_list_category_id', '=', $discount_category_id],
                ['valid_from', '<=', $group['date_from']],
                ['valid_until', '>=', $group['date_from']]
            ]);

            $discount_lists = $om->read('sale\discount\DiscountList', $discount_lists_ids, ['id', 'discounts_ids', 'rate_min', 'rate_max']);
            $discount_list_id = 0;
            $discount_list = null;
            if($discount_lists > 0 && count($discount_lists)) {
                // use first match (there should alwasy be only one or zero)
                $discount_list = array_pop($discount_lists);
                $discount_list_id = $discount_list['id'];
                trigger_error("QN_DEBUG_ORM:: match with discount List {$discount_list_id}", QN_REPORT_DEBUG);
            }
            else {
                trigger_error("QN_DEBUG_ORM:: no discount List found", QN_REPORT_DEBUG);
            }

            /*
                Search for matching Discounts within the found Discount List
            */
            if($discount_list_id) {
                $operands = [];
                $operands['count_booking_24'] = $group['booking_id.customer_id.count_booking_24'];
                // duration in nights
                $operands['duration'] = $group['nb_nights'];
                // number of participants
                $operands['nb_pers'] = $group['nb_pers'];

                $date = $group['date_from'];

                /*
                    Pick up the first season period that matches the year and the season category of the center
                */

                $year = date('Y', $date);
                $seasons_ids = $om->search('sale\season\SeasonPeriod', [
                    ['season_category_id', '=', $group['booking_id.center_id.season_category_id']],
                    ['date_from', '<=', $group['date_from']],
                    ['date_to', '>=', $group['date_from']],
                    ['year', '=', $year]
                ]);

                $periods = $om->read('sale\season\SeasonPeriod', $seasons_ids, ['id', 'season_type_id.name']);
                if($periods > 0 && count($periods)){
                    $period = array_shift($periods);
                    $operands['season'] = $period['season_type_id.name'];
                }

                $discounts = $om->read('sale\discount\Discount', $discount_list['discounts_ids'], ['value', 'type', 'conditions_ids']);

                // filter discounts based on related conditions
                $discounts_to_apply = [];
                // keep track of the final rate (for discounts with type 'percent')
                $rate_to_apply = 0;

                // filter discounts to be applied on booking lines
                foreach($discounts as $discount_id => $discount) {
                    $conditions = $om->read('sale\discount\Condition', $discount['conditions_ids'], ['operand', 'operator', 'value']);
                    $valid = true;
                    foreach($conditions as $c_id => $condition) {
                        if(!in_array($condition['operator'], ['>', '>=', '<', '<=', '='])) {
                            // unknown operator
                            continue;
                        }
                        $operator = $condition['operator'];
                        if($operator == '=') {
                            $operator = '==';
                        }
                        if(!isset($operands[$condition['operand']])) {
                            $valid = false;
                            break;
                        }
                        $operand = $operands[$condition['operand']];
                        $value = $condition['value'];
                        if(!is_numeric($operand)) {
                            $operand = "'$operand'";
                        }
                        if(!is_numeric($value)) {
                            $value = "'$value'";
                        }
                        trigger_error(" testing {$operand} {$operator} {$value}", QN_REPORT_DEBUG);
                        $valid = $valid && (bool) eval("return ( {$operand} {$operator} {$value});");
                        if(!$valid) break;
                    }
                    if($valid) {
                        trigger_error("QN_DEBUG_ORM:: all conditions fullfilled, applying {$discount['value']} {$discount['type']}", QN_REPORT_DEBUG);
                        $discounts_to_apply[$discount_id] = $discount;
                        if($discount['type'] == 'percent') {
                            $rate_to_apply += $discount['value'];
                        }
                    }
                }

                // guaranteed rate (rate_min) is always granted
                if($discount_list['rate_min'] > 0) {
                    $rate_to_apply += $discount_list['rate_min'];
                    $discounts_to_apply[0] = [
                        'type'      => 'percent',
                        'value'     => $discount_list['rate_min']
                    ];
                }

                // if max rate (rate_max) has been reached, use max instead
                if($rate_to_apply > $discount_list['rate_max'] ) {
                    // remove all 'percent' discounts
                    foreach($discounts_to_apply as $discount_id => $discount) {
                        if($discount['type'] == 'percent') {
                            unset($discounts_to_apply[$discount_id]);
                        }
                    }
                    // add a custom discount with maximal rate
                    $discounts_to_apply[0] = [
                        'type'      => 'percent',
                        'value'     => $discount_list['rate_max']
                    ];
                }

                // apply all applicable discounts
                foreach($discounts_to_apply as $discount_id => $discount) {
                    /*
                        create price adapter for group only, according to discount and group settings
                        (needed in case group targets a pack with own price)
                    */
                    $price_adapters_ids = $om->create('lodging\sale\booking\BookingPriceAdapter', [
                        'is_manual_discount'    => false,
                        'booking_id'            => $group['booking_id'],
                        'booking_line_group_id' => $group_id,
                        'booking_line_id'       => 0,
                        'discount_id'           => $discount_id,
                        'discount_list_id'      => $discount_list_id,
                        'type'                  => $discount['type'],
                        'value'                 => $discount['value']
                    ]);

                    /*
                        create related price adapter for all lines, according to discount and group settings
                    */

                    // read all lines from group
                    $lines = $om->read('lodging\sale\booking\BookingLine', $group['booking_lines_ids'], [
                        'product_id',
                        'product_id.product_model_id',
                        'product_id.product_model_id.has_duration',
                        'product_id.product_model_id.duration',
                        'is_meal',
                        'is_accomodation',
                        'qty_accounting_method'
                    ]);

                    foreach($lines as $line_id => $line) {
                        // do not apply discount on lines that cannot have a price
                        if($group['is_locked']) continue;
                        // do not apply freebies on accomodations for groups
                        if($discount['type'] == 'freebie' && $line['qty_accounting_method'] == 'accomodation') continue;
                        if(
                            // for GG: apply discounts only on accomodations
                            (
                                $group['sojourn_type_id'] == 2 /*'GG'*/ && $line['is_accomodation']
                            )
                            ||
                            // for GA: apply discounts on meals and accomodations
                            (
                                $group['sojourn_type_id'] == 1 /*'GA'*/
                                &&
                                (
                                    $line['is_accomodation'] || $line['is_meal']
                                )
                            )
                        ) {
                            trigger_error("QN_DEBUG_ORM:: creating price adapter", QN_REPORT_DEBUG);
                            $factor = $group['nb_nights'];

                            if($line['product_id.product_model_id.has_duration']) {
                                $factor = $line['product_id.product_model_id.duration'];
                            }

                            // current discount must be applied on the line: create a price adpter
                            $price_adapters_ids = $om->create('lodging\sale\booking\BookingPriceAdapter', [
                                'is_manual_discount'    => false,
                                'booking_id'            => $group['booking_id'],
                                'booking_line_group_id' => $group_id,
                                'booking_line_id'       => $line_id,
                                'discount_id'           => $discount_id,
                                'discount_list_id'      => $discount_list_id,
                                'type'                  => $discount['type'],
                                'value'                 => ($discount['type'] == 'freebie')?($discount['value']*$factor):$discount['value']
                            ]);
                        }
                    }
                }

            }
            else {
                $date = date('Y-m-d', $group['date_from']);
                trigger_error("QN_DEBUG_ORM::no matching discount list found for date {$date}", QN_REPORT_DEBUG);
            }
        }
    }


    public static function updatePriceAdaptersFromLines($om, $oids, $booking_lines_ids, $lang) {
        /*
            Remove all previous price adapters relating to given lines were automatically created
        */
        $price_adapters_ids = $om->search('lodging\sale\booking\BookingPriceAdapter', [ ['booking_line_id', 'in', $booking_lines_ids], ['is_manual_discount','=', false]]);
        $om->remove('lodging\sale\booking\BookingPriceAdapter', $price_adapters_ids, true);

        $line_groups = $om->read(self::getType(), $oids, [
                'rate_class_id',
                'sojourn_type_id',
                'date_from',
                'date_to',
                'nb_pers',
                'nb_nights',
                'booking_id',
                'is_locked',
                'booking_lines_ids',
                'booking_id.nb_pers',
                'booking_id.customer_id.count_booking_24',
                'booking_id.center_id.season_category_id',
                'booking_id.center_id.discount_list_category_id'
            ]);

        foreach($line_groups as $group_id => $group) {
            /*
                Find the first Discount List that matches the booking dates
            */

            // the discount list category to use is the one defined for the center, unless it is ('GA' or 'GG') AND sojourn_type <> category.name
            $discount_category_id = $group['booking_id.center_id.discount_list_category_id'];

            if(in_array($discount_category_id, [1 /*GA*/, 2 /*GG*/]) && $discount_category_id != $group['sojourn_type_id']) {
                $discount_category_id = $group['sojourn_type_id'];
            }

            $discount_lists_ids = $om->search('sale\discount\DiscountList', [
                ['rate_class_id', '=', $group['rate_class_id']],
                ['discount_list_category_id', '=', $discount_category_id],
                ['valid_from', '<=', $group['date_from']],
                ['valid_until', '>=', $group['date_from']]
            ]);

            $discount_lists = $om->read('sale\discount\DiscountList', $discount_lists_ids, ['id', 'discounts_ids', 'rate_min', 'rate_max']);
            $discount_list_id = 0;
            $discount_list = null;
            if($discount_lists > 0 && count($discount_lists)) {
                // use first match (there should alwasy be only one or zero)
                $discount_list = array_pop($discount_lists);
                $discount_list_id = $discount_list['id'];
                trigger_error("QN_DEBUG_ORM:: match with discount List {$discount_list_id}", QN_REPORT_DEBUG);
            }
            else {
                trigger_error("QN_DEBUG_ORM:: no discount List found", QN_REPORT_DEBUG);
            }

            /*
                Search for matching Discounts within the found Discount List
            */
            if($discount_list_id) {
                $operands = [];
                $operands['count_booking_24'] = $group['booking_id.customer_id.count_booking_24'];
                // duration in nights
                $operands['duration'] = $group['nb_nights'];
                // number of participants
                $operands['nb_pers'] = $group['nb_pers'];

                $date = $group['date_from'];

                /*
                    Pick up the first season period that matches the year and the season category of the center
                */

                $year = date('Y', $date);
                $seasons_ids = $om->search('sale\season\SeasonPeriod', [
                    ['season_category_id', '=', $group['booking_id.center_id.season_category_id']],
                    ['date_from', '<=', $group['date_from']],
                    ['date_to', '>=', $group['date_from']],
                    ['year', '=', $year]
                ]);

                $periods = $om->read('sale\season\SeasonPeriod', $seasons_ids, ['id', 'season_type_id.name']);
                if($periods > 0 && count($periods)){
                    $period = array_shift($periods);
                    $operands['season'] = $period['season_type_id.name'];
                }

                $discounts = $om->read('sale\discount\Discount', $discount_list['discounts_ids'], ['value', 'type', 'conditions_ids']);

                // filter discounts based on related conditions
                $discounts_to_apply = [];
                // keep track of the final rate (for discounts with type 'percent')
                $rate_to_apply = 0;

                // filter discounts to be applied on booking lines
                foreach($discounts as $discount_id => $discount) {
                    $conditions = $om->read('sale\discount\Condition', $discount['conditions_ids'], ['operand', 'operator', 'value']);
                    $valid = true;
                    foreach($conditions as $c_id => $condition) {
                        if(!in_array($condition['operator'], ['>', '>=', '<', '<=', '='])) {
                            // unknown operator
                            continue;
                        }
                        $operator = $condition['operator'];
                        if($operator == '=') {
                            $operator = '==';
                        }
                        if(!isset($operands[$condition['operand']])) {
                            $valid = false;
                            break;
                        }
                        $operand = $operands[$condition['operand']];
                        $value = $condition['value'];
                        if(!is_numeric($operand)) {
                            $operand = "'$operand'";
                        }
                        if(!is_numeric($value)) {
                            $value = "'$value'";
                        }
                        trigger_error(" testing {$operand} {$operator} {$value}", QN_REPORT_DEBUG);
                        $valid = $valid && (bool) eval("return ( {$operand} {$operator} {$value});");
                        if(!$valid) break;
                    }
                    if($valid) {
                        trigger_error("QN_DEBUG_ORM:: all conditions fullfilled, applying {$discount['value']} {$discount['type']}", QN_REPORT_DEBUG);
                        $discounts_to_apply[$discount_id] = $discount;
                        if($discount['type'] == 'percent') {
                            $rate_to_apply += $discount['value'];
                        }
                    }
                }

                // guaranteed rate (rate_min) is always granted
                if($discount_list['rate_min'] > 0) {
                    $rate_to_apply += $discount_list['rate_min'];
                    $discounts_to_apply[0] = [
                        'type'      => 'percent',
                        'value'     => $discount_list['rate_min']
                    ];
                }

                // if max rate (rate_max) has been reached, use max instead
                if($rate_to_apply > $discount_list['rate_max'] ) {
                    // remove all 'percent' discounts
                    foreach($discounts_to_apply as $discount_id => $discount) {
                        if($discount['type'] == 'percent') {
                            unset($discounts_to_apply[$discount_id]);
                        }
                    }
                    // add a custom discount with maximal rate
                    $discounts_to_apply[0] = [
                        'type'      => 'percent',
                        'value'     => $discount_list['rate_max']
                    ];
                }

                // apply all applicable discounts
                foreach($discounts_to_apply as $discount_id => $discount) {

                    /*
                        create related price adapter for all lines, according to discount and group settings
                    */

                    // read all lines from group
                    $lines = $om->read(BookingLine::getType(), $booking_lines_ids, [
                        'product_id',
                        'product_id.product_model_id',
                        'product_id.product_model_id.has_duration',
                        'product_id.product_model_id.duration',
                        'is_meal',
                        'is_accomodation',
                        'qty_accounting_method'
                    ]);

                    foreach($lines as $line_id => $line) {
                        // do not apply discount on lines that cannot have a price
                        if($group['is_locked']) {
                            continue;
                        }
                        // do not apply freebies on accomodations for groups
                        if($discount['type'] == 'freebie' && $line['qty_accounting_method'] == 'accomodation') {
                            continue;
                        }
                        if(
                            // for GG: apply discounts only on accomodations
                            (
                                $group['sojourn_type_id'] == 2 /*'GG'*/ && $line['is_accomodation']
                            )
                            ||
                            // for GA: apply discounts on meals and accomodations
                            (
                                $group['sojourn_type_id'] == 1 /*'GA'*/
                                &&
                                (
                                    $line['is_accomodation'] || $line['is_meal']
                                )
                            )
                        ) {
                            trigger_error("QN_DEBUG_ORM:: creating price adapter", QN_REPORT_DEBUG);
                            $factor = $group['nb_nights'];

                            if($line['product_id.product_model_id.has_duration']) {
                                $factor = $line['product_id.product_model_id.duration'];
                            }

                            // current discount must be applied on the line: create a price adpter
                            $price_adapters_ids = $om->create('lodging\sale\booking\BookingPriceAdapter', [
                                'is_manual_discount'    => false,
                                'booking_id'            => $group['booking_id'],
                                'booking_line_group_id' => $group_id,
                                'booking_line_id'       => $line_id,
                                'discount_id'           => $discount_id,
                                'discount_list_id'      => $discount_list_id,
                                'type'                  => $discount['type'],
                                'value'                 => ($discount['type'] == 'freebie')?($discount['value']*$factor):$discount['value']
                            ]);
                        }
                    }
                }

            }
            else {
                $date = date('Y-m-d', $group['date_from']);
                trigger_error("QN_DEBUG_ORM::no matching discount list found for date {$date}", QN_REPORT_DEBUG);
            }
        }
    }


    /**
     * Update pack_id and re-create booking lines accordingly.
     *
     */
    public static function _updatePack($om, $oids, $values, $lang) {
        trigger_error("QN_DEBUG_ORM::calling lodging\sale\booking\BookingLineGroup:_updatePack", QN_REPORT_DEBUG);

        $groups = $om->read(self::getType(), $oids, [
            'booking_id',
            'booking_lines_ids',
            'age_range_assignments_ids',
            'nb_pers',
            'pack_id.is_locked',
            'pack_id.pack_lines_ids',
            'pack_id.product_model_id.has_own_price'
        ]);

        foreach($groups as $gid => $group) {

            // 1) Update current group according to selected pack

            // might need to update price_id
            if($group['pack_id.product_model_id.has_own_price']) {
                $om->update(self::getType(), $gid, ['is_locked' => true], $lang);
            }
            else {
                $om->update(self::getType(), $gid, ['is_locked' => $group['pack_id.is_locked'] ], $lang);
            }

            // retrieve the composition of the pack
            $pack_lines = $om->read('lodging\sale\catalog\PackLine', $group['pack_id.pack_lines_ids'], [
                'child_product_model_id',
                'has_own_qty',
                'own_qty',
                'has_own_duration',
                'own_duration',
                'child_product_model_id.qty_accounting_method'
            ]);

            $pack_product_models_ids = array_map(function($a) {return $a['child_product_model_id'];}, $pack_lines);

            // remove booking lines that are part of the pack (others might have been added manually, we leave them untouched)
            $booking_lines = $om->read(BookingLine::getType(), $group['booking_lines_ids'], ['product_id.product_model_id'], $lang);
            if($booking_lines > 0) {
                $filtered_lines_ids = [];
                foreach($booking_lines as $lid => $line) {
                    if(in_array($line['product_id.product_model_id'], $pack_product_models_ids) ) {
                        $filtered_lines_ids[] = $lid;
                    }
                }
                // remove existing booking_lines (updating booking_lines_ids will trigger ondetach events)
                $om->update(self::getType(), $gid, ['booking_lines_ids' => array_map(function($a) { return "-$a";}, $filtered_lines_ids)]);
            }


            // 2) Create booking lines according to pack composition.

            $order = 1;

            // retrieve age_range assignements (there must be at least one)
            $age_assignements = $om->read(BookingLineGroupAgeRangeAssignment::getType(), $group['age_range_assignments_ids'], ['age_range_id']);

            foreach($pack_lines as $pid => $pack_line) {
                /*
                    retrieve the product(s) to add, based on child_product_model_id and group age_ranges, if set
                    if no specific product with age_range, use nb_pers
                    if no product for a specific age_range, use "all age" product
                 */
                // we expect any group to have at min. 1 age range (default)
                foreach($age_assignements as $age_assignement) {

                    $line = [
                        'order'                     => $order++,
                        'booking_id'                => $group['booking_id'],
                        'booking_line_group_id'     => $gid,
                        'qty_accounting_method'     => $pack_line['child_product_model_id.qty_accounting_method']
                    ];

                    $has_single_range = false;

                    // search for a product matching model and age_range (there should be 1 or 0)
                    $products_ids = $om->search('lodging\sale\catalog\Product', [ ['product_model_id', '=', $pack_line['child_product_model_id']], ['age_range_id', '=', $age_assignement['age_range_id']], ['can_sell', '=', true] ]);
                    // if no product for a specific age_range, use "all age" product and use range.qty
                    if($products_ids < 0 || !count($products_ids)) {
                        $products_ids = $om->search('lodging\sale\catalog\Product', [ ['product_model_id', '=', $pack_line['child_product_model_id']], ['has_age_range', '=', false], ['can_sell', '=', true] ]);
                        if($products_ids < 0 || !count($products_ids)) {
                            // issue a warning : no product match for line
                            trigger_error("QN_DEBUG_ORM::no match for age range {$age_assignement['age_range_id']} and no 'all ages' product found for model {$pack_line['child_product_model_id']}", QN_REPORT_WARNING);
                            // skip the line
                            continue 2;
                        }
                        else {
                            // create a single line for all ages
                            $has_single_range = true;
                        }
                    }
                    $product_id = reset($products_ids);

                    // create a booking line with found product
                    $line['product_id'] = $product_id;

                    if($pack_line['has_own_qty']) {
                        $line['has_own_qty'] = true;
                        $line['qty'] = $pack_line['own_qty'];
                    }
                    if($pack_line['has_own_duration']) {
                        $line['has_own_duration'] = true;
                        $line['own_duration'] = $pack_line['own_duration'];
                    }
                    // qty is auto assigned upon line assignation to a product
                    $lid = $om->create('lodging\sale\booking\BookingLine', $line, $lang);
                    if($lid > 0) {
                        $om->update(self::getType(), $gid, ['booking_lines_ids' => ["+$lid"] ]);
                    }

                    if($has_single_range) {
                        break;
                    }
                }
            }
        }

        // update dependencies
        $om->callonce(self::getType(), 'createRentalUnitsAssignments', $oids, [], $lang);
        $om->callonce(self::getType(), 'updatePriceAdapters', $oids, [], $lang);
        $om->callonce(self::getType(), '_updateAutosaleProducts', $oids, [], $lang);
        $om->callonce(self::getType(), '_updateMealPreferences', $oids, [], $lang);

    }

    /**
     * Resets all rental unit assignements and process each line for aut-assignement, if possible.
     *
     *   1) decrement nb_pers for lines accounted by 'accomodation' (capacity)
     *   2) create missing SPM
     *
     *  qty_accounting_method = 'accomodation'
     *    (we consider product and unit to have is_accomodation to true)
     *    1) find a free accomodation  (capacity >= product_model.capacity)
     *    2) create assignment @capacity
     *
     *  qty_accounting_method = 'person'
     *  if is_accomodation
     *      1) find a free accomodation
     *      2) create assignment @nb_pers
     *        (ignore next lines accounted by 'person')
     *  otherwise
     *       1) find a free rental unit
     *       2) create assignment @group.nb_pers
     *
     * qty_accounting_method = 'unit'
     *  1) find a free rental unit
     *  2) create assignment @group.nb_pers
     */
    public static function createRentalUnitsAssignments($om, $oids, $values, $lang) {
        /*
            Mise à jour des assignations des unités locatives


            ## lorsqu'on "ajoute" une booking line (onupdateProductId)
            * on crée des nouvelles assignations de rental unit en fonction du product_model de la ligne

            ## lorsqu'on supprime une booking line (onupdateBookingLinesIds)
            * on fait un reset des assignations rental unit


            ## lorsqu'on modifie le nb_pers (onupdateNbPers) ou les qty des tranches d'âge
            * on fait un reset des assignations rental unit


            ## lorsqu'on modifie le pack (onupdatePackId)

            * on fait un reset des assignations rental unit
            * on fait une assignation pour toutes les lignes en même temps (_createRentalUnitsAssignements)

            ## lorsqu'on supprime une tranche d'âge (ondelete)
            * on supprime toutes les lignes dont le product_id se rapporte à cette tranche d'âge
        */

        // remove all previous SPM and rental_unit assignements
        $groups = $om->read(self::getType(), $oids, [
            'has_locked_rental_units',
            'booking_lines_ids',
            'sojourn_product_models_ids'
        ]);

        foreach($groups as $gid => $group) {
            // ignore groups with explicitly locked rental unit assignments
            if($group['has_locked_rental_units']) {
                continue;
            }
            // remove all previous SPM and rental_unit assignements
            $om->update(self::getType(), $gid, ['sojourn_product_models_ids' => array_map(function($a) { return "-$a";}, $group['sojourn_product_models_ids'])]);
            // attempt to auto-assign rental units
            $om->callonce(self::getType(), 'createRentalUnitsAssignmentsFromLines', $gid, $group['booking_lines_ids'], $lang);
        }

    }


    /**
     * Updates rental unit assigments from a set of booking lines (called by BookingLine::onupdateProductId).
     * The references booking_lines_ids are expected to be identifiers of lines that have just been modified and to belong to a same sojourn (BookingLineGroup).
     */
    public static function createRentalUnitsAssignmentsFromLines($om, $oids, $booking_lines_ids, $lang) {

        // Attempt to auto-assign rental units.
        $groups = $om->read(self::getType(), $oids, [
            'booking_id',
            'nb_pers',
            'has_locked_rental_units',
            'booking_lines_ids',
            'date_from',
            'date_to',
            'time_from',
            'time_to',
            'sojourn_product_models_ids',
            'rental_unit_assignments_ids.rental_unit_id'
        ]);

        foreach($groups as $gid => $group) {

            if($group['has_locked_rental_units']) {
                continue;
            }

            // retrieve rental units that are already assigned by other groups, if any
            // (we need to withdraw those from available units)
            $booking_assigned_rental_units_ids = [];
            $bookings = $om->read(Booking::getType(), $group['booking_id'], ['rental_unit_assignments_ids'], $lang);
            if($bookings > 0 && count($bookings)) {
                $booking = reset($bookings);
                $assignments = $om->read(SojournProductModelRentalUnitAssignement::getType(), $booking['rental_unit_assignments_ids'], ['rental_unit_id', 'booking_line_group_id'], $lang);
                foreach($assignments as $oid => $assignment) {
                    // process rental units from other groups
                    if($assignment['booking_line_group_id'] != $gid) {
                        $booking_assigned_rental_units_ids[] = $assignment['rental_unit_id'];
                    }
                }
            }

            // create a map with all product_model_id within the group
            $group_product_models_ids = [];

            $sojourn_product_models = $om->read(SojournProductModel::getType(), $group['sojourn_product_models_ids'], ['product_model_id'], $lang);
            foreach($sojourn_product_models as $spid => $spm){
                $group_product_models_ids[$spm['product_model_id']] = $spid;
            }

            // read children booking lines
            $lines = $om->read(BookingLine::getType(), $group['booking_lines_ids'], [
                    'booking_id.center_id',
                    'product_id',
                    'product_id.product_model_id',
                    'qty_accounting_method',
                    'is_rental_unit'
                ],
                $lang);

            // drop lines that do not relate to rental units
            $lines = array_filter($lines, function($a) { return $a['is_rental_unit']; });

            if(count($lines)) {

                // read all related product models at once
                $product_models_ids = array_map(function($oid) use($lines) {return $lines[$oid]['product_id.product_model_id'];}, array_keys($lines));
                $product_models = $om->read('lodging\sale\catalog\ProductModel', $product_models_ids, ['is_accomodation', 'qty_accounting_method', 'rental_unit_assignement', 'capacity'], $lang);

                $nb_pers = $group['nb_pers'];
                $date_from = $group['date_from'] + $group['time_from'];
                $date_to = $group['date_to'] + $group['time_to'];

                // pass-1 : withdraw persons assigned to units accounted by 'accomodation' from nb_pers, and create SPMs
                foreach($lines as $lid => $line) {
                    $product_model_id = $line['product_id.product_model_id'];
                    if($product_models[$product_model_id]['qty_accounting_method'] == 'accomodation') {
                        $nb_pers -= $product_models[$product_model_id]['capacity'];
                    }
                    if(!isset($group_product_models_ids[$product_model_id])) {
                        $sojourn_product_model_id = $om->create(SojournProductModel::getType(), [
                            'booking_id'            => $group['booking_id'],
                            'booking_line_group_id' => $gid,
                            'product_model_id'      => $product_model_id
                        ]);
                        $group_product_models_ids[$product_model_id] = $sojourn_product_model_id;
                    }
                }
            }

            // read targeted booking lines (received as method param)
            $lines = $om->read(BookingLine::getType(), $booking_lines_ids, [
                    'booking_id.center_id',
                    'product_id',
                    'product_id.product_model_id',
                    'qty_accounting_method',
                    'is_rental_unit'
                ],
                $lang);

            // drop lines that do not relate to rental units
            $lines = array_filter($lines, function($a) { return $a['is_rental_unit']; });

            if(count($lines)) {
                // pass-2 : process lines
                $group_assigned_rental_units_ids = [];
                $has_processed_accomodation_by_person = false;
                foreach($lines as $lid => $line) {

                    $center_id = $line['booking_id.center_id'];

                    $is_accomodation = $product_models[$line['product_id.product_model_id']]['is_accomodation'];
                    // 'accomodation', 'person', 'unit'
                    $qty_accounting_method = $product_models[$line['product_id.product_model_id']]['qty_accounting_method'];

                    // 'category', 'capacity', 'auto'
                    // #memo - the assignment-based filtering is done in `Consumption::getAvailableRentalUnits`
                    $rental_unit_assignment = $product_models[$line['product_id.product_model_id']]['rental_unit_assignement'];

                    // all lines with same product_model are processed at the first line, remaining lines must be ignored
                    if($qty_accounting_method == 'person' && $is_accomodation && $has_processed_accomodation_by_person) {
                        continue;
                    }

                    $nb_pers_to_assign = $nb_pers;

                    if($qty_accounting_method == 'accomodation') {
                        $nb_pers_to_assign = min($product_models[$line['product_id.product_model_id']]['capacity'], $group['nb_pers']);
                    }
                    elseif($qty_accounting_method == 'unit') {
                        $nb_pers_to_assign = $group['nb_pers'];
                    }

                    // find available rental units (sorted by capacity, desc; filtered on product model category)
                    $rental_units_ids = Consumption::getAvailableRentalUnits($om, $center_id, $line['product_id.product_model_id'], $date_from, $date_to);

                    // #memo - we cannot append rental units from consumptions of own booking :this leads to an edge case
                    // (use case "come and go between 'quote' and 'option'" is handled with 'realease-rentalunits' action)

                    // remove rental units that are no longer unavailable
                    $rental_units_ids = array_diff($rental_units_ids,
                            $group_assigned_rental_units_ids,               // assigned to other lines (current loop)
                            $booking_assigned_rental_units_ids              // assigned within other groups
                        );

                    // retrieve rental units with matching capacities (best match first)
                    $rental_units = self::_getRentalUnitsMatches($om, $rental_units_ids, $nb_pers_to_assign);

                    $remaining = $nb_pers_to_assign;
                    $assigned_rental_units = [];

                    // min serie for available capacity starts from max(0, i-1)
                    for($j = 0, $n = count($rental_units) ;$j < $n; ++$j) {
                        $rental_unit = $rental_units[$j];
                        $assigned = min($rental_unit['capacity'], $remaining);
                        $rental_unit['assigned'] = $assigned;
                        $assigned_rental_units[] = $rental_unit;
                        $remaining -= $assigned;
                        if($remaining <= 0) break;
                    }

                    if($remaining > 0) {
                        // no availability !
                        trigger_error("QN_DEBUG_ORM::no availability", QN_REPORT_DEBUG);
                    }
                    else {
                        foreach($assigned_rental_units as $rental_unit) {
                            $assignement = [
                                'booking_id'                    => $group['booking_id'],
                                'booking_line_group_id'         => $gid,
                                'sojourn_product_model_id'      => $group_product_models_ids[$line['product_id.product_model_id']],
                                'qty'                           => $rental_unit['assigned'],
                                'rental_unit_id'                => $rental_unit['id']
                            ];
                            trigger_error("QN_DEBUG_ORM::assigning {$rental_unit['assigned']} p. to {$rental_unit['id']}", QN_REPORT_DEBUG);
                            $om->create(SojournProductModelRentalUnitAssignement::getType(), $assignement);
                            // remember assigned rental units (for next lines processing)
                            $group_assigned_rental_units_ids[]= $rental_unit['id'];
                        }

                        if($qty_accounting_method == 'person' && $is_accomodation) {
                            $has_processed_accomodation_by_person = true;
                        }

                    }
                }
            }

        }

    }



    /**
     * Find and set price according to group settings.
     * This only applies when group targets a Pack with own price.
     *
     * Should only be called when is_locked == true
     *
     * _updatePriceId is called upon change on: pack_id, is_locked, date_from, center_id
     */
    public static function _updatePriceId($om, $oids, $values, $lang) {
        trigger_error("QN_DEBUG_ORM::calling sale\booking\BookingLineGroup:_updatePriceId", QN_REPORT_DEBUG);

        $groups = $om->read(self::getType(), $oids, [
            'has_pack',
            'date_from',
            'pack_id',
            'booking_id.center_id.price_list_category_id'
        ]);

        foreach($groups as $gid => $group) {
            if(!$group['has_pack']) {
                continue;
            }
            /*
                Find the Price List that matches the criteria from the booking with the shortest duration
            */
            $price_lists_ids = $om->search(
                'sale\price\PriceList',
                [
                    ['price_list_category_id', '=', $group['booking_id.center_id.price_list_category_id']],
                    ['is_active', '=', true]
                ],
                ['duration' => 'asc']
            );

            $found = false;
            if($price_lists_ids > 0 && count($price_lists_ids)) {
                /*
                    Search for a matching Price within the found Price Lists
                */
                foreach($price_lists_ids as $price_list_id) {
                    // there should be exactly one matching price
                    $prices_ids = $om->search('sale\price\Price', [ ['price_list_id', '=', $price_list_id], ['product_id', '=', $group['pack_id']] ]);
                    if($prices_ids > 0 && count($prices_ids)) {
                        /*
                            Assign found Price to current group
                        */
                        $found = true;
                        $om->update(self::getType(), $gid, ['price_id' => $prices_ids[0]]);
                        break;
                    }
                }
            }
            if(!$found) {
                $om->update(self::getType(), $gid, ['price_id' => null, 'vat_rate' => 0, 'unit_price' => 0]);
                $date = date('Y-m-d', $group['date_from']);
                trigger_error("QN_DEBUG_ORM::no matching price list found for group at date {$date}", QN_REPORT_ERROR);
            }
        }
    }

    /**
     * Generate one or more groups for products saled automatically.
     * We generate services groups related to autosales when the  is updated
     * customer, date_from, date_to, center_id
     *
     */
    public static function _updateAutosaleProducts($om, $oids, $values, $lang) {
        trigger_error("QN_DEBUG_ORM::calling sale\booking\BookingLineGroup:_updateAutosaleProducts", QN_REPORT_DEBUG);

        /*
            remove groups related to autosales that already exist
        */
        $groups = $om->read(self::getType(), $oids, [
                'is_autosale',
                'nb_pers',
                'nb_nights',
                'date_from',
                'date_to',
                'booking_id',
                'booking_id.center_id.autosale_list_category_id',
                'booking_id.customer_id.count_booking_12',
                'booking_lines_ids'
            ], $lang);

        // loop through groups and create lines for autosale products, if any
        foreach($groups as $group_id => $group) {

            if($group['is_autosale']) continue;

            $lines_ids_to_delete = [];
            $booking_lines = $om->read('lodging\sale\booking\BookingLine', $group['booking_lines_ids'], ['is_autosale'], $lang);
            if($booking_lines > 0) {
                foreach($booking_lines as $lid => $line) {
                    if($line['is_autosale']) {
                        $lines_ids_to_delete[] = -$lid;
                    }
                }
                $om->update(self::getType(), $group_id, ['booking_lines_ids' => $lines_ids_to_delete], $lang);
            }

            /*
                Find the first Autosale List that matches the booking dates
            */

            $autosale_lists_ids = $om->search('sale\autosale\AutosaleList', [
                ['autosale_list_category_id', '=', $group['booking_id.center_id.autosale_list_category_id']],
                ['date_from', '<=', $group['date_from']],
                ['date_to', '>=', $group['date_from']]
            ]);

            // ob_start();
            // var_dump($autosale_lists_ids);
            // $buff = ob_get_clean();
            // trigger_error("QN_DEBUG_ORM::{$buff}", QN_REPORT_ERROR);
            $autosale_lists = $om->read('sale\autosale\AutosaleList', $autosale_lists_ids, ['id', 'autosale_lines_ids']);
            $autosale_list_id = 0;
            $autosale_list = null;
            if($autosale_lists > 0 && count($autosale_lists)) {
                // use first match (there should always be only one or zero)
                $autosale_list = array_pop($autosale_lists);
                $autosale_list_id = $autosale_list['id'];
                trigger_error("QN_DEBUG_ORM:: match with autosale List {$autosale_list_id}", QN_REPORT_DEBUG);
            }
            else {
                trigger_error("QN_DEBUG_ORM:: no autosale List found", QN_REPORT_DEBUG);
            }
            /*
                Search for matching Autosale products within the found List
            */
            if($autosale_list_id) {
                $operands = [];

                // for now, we only support member cards for customer that haven't booked a service for more thant 12 months

                $operands['count_booking_12'] = $group['booking_id.customer_id.count_booking_12'];
                $operands['nb_pers'] = $group['nb_pers'];
                $operands['nb_nights'] = $group['nb_nights'];

                $autosales = $om->read('sale\autosale\AutosaleLine', $autosale_list['autosale_lines_ids'], [
                    'product_id.id',
                    'product_id.name',
                    'has_own_qty',
                    'qty',
                    'scope',
                    'conditions_ids'
                ], $lang);

                // filter discounts based on related conditions
                $products_to_apply = [];

                // pass-1: filter discounts to be applied on booking lines
                foreach($autosales as $autosale_id => $autosale) {
                    if($autosale['scope'] != 'group') continue;
                    $conditions = $om->read('sale\autosale\Condition', $autosale['conditions_ids'], ['operand', 'operator', 'value']);
                    $valid = true;
                    foreach($conditions as $c_id => $condition) {
                        if(!in_array($condition['operator'], ['>', '>=', '<', '<=', '='])) {
                            // unknown operator
                            continue;
                        }
                        $operator = $condition['operator'];
                        if($operator == '=') {
                            $operator = '==';
                        }
                        if(!isset($operands[$condition['operand']])) {
                            $valid = false;
                            break;
                        }
                        $operand = $operands[$condition['operand']];
                        $value = $condition['value'];
                        if(!is_numeric($operand)) {
                            $operand = "'$operand'";
                        }
                        if(!is_numeric($value)) {
                            $value = "'$value'";
                        }
                        trigger_error(" testing {$operand} {$operator} {$value}", QN_REPORT_DEBUG);
                        $valid = $valid && (bool) eval("return ( {$operand} {$operator} {$value});");
                        if(!$valid) break;
                    }
                    if($valid) {
                        trigger_error("QN_DEBUG_ORM:: all conditions fullfilled", QN_REPORT_DEBUG);
                        $products_to_apply[$autosale_id] = [
                            'id'            => $autosale['product_id.id'],
                            'name'          => $autosale['product_id.name'],
                            'has_own_qty'   => $autosale['has_own_qty'],
                            'qty'           => $autosale['qty']
                        ];
                    }
                }

                // pass-2: apply all applicable products
                $count = count($products_to_apply);

                if($count) {

                    // add all applicable products at the end of the group
                    $order = 1000;
                    foreach($products_to_apply as $autosale_id => $product) {
                        $qty = $product['qty'];
                        if(!$product['has_own_qty']) {
                            $qty = $group['nb_pers'] * $group['nb_nights'];
                        }
                        // create a line relating to the product
                        $line = [
                            'order'                     => $order++,
                            'booking_id'                => $group['booking_id'],
                            'booking_line_group_id'     => $group_id,
                            'product_id'                => $product['id'],
                            'qty'                       => $qty,
                            'has_own_qty'               => true,
                            'is_autosale'               => true
                        ];
                        $om->create('lodging\sale\booking\BookingLine', $line, $lang);
                    }
                }
            }
            else {
                $date = date('Y-m-d', $group['date_from']);
                trigger_error("QN_DEBUG_ORM::no matching autosale list found for date {$date}", QN_REPORT_DEBUG);
            }
        }
    }



    public static function _updateMealPreferences($om, $oids, $values, $lang) {

        $groups = $om->read(self::getType(), $oids, [
                'is_sojourn',
                'nb_pers',
                'meal_preferences_ids'
            ], $lang);

        if($groups > 0) {
            foreach($groups as $gid => $group) {
                if($group['is_sojourn']) {
                    if(count($group['meal_preferences_ids']) == 0)  {
                        // create a meal preference
                        $pref = [
                            'booking_line_group_id'     => $gid,
                            'qty'                       => $group['nb_pers'],
                            'type'                      => '2_courses',
                            'pref'                      => 'regular'
                        ];
                        $om->create('sale\booking\MealPreference', $pref, $lang);
                    }
                    else if(count($group['meal_preferences_ids']) == 1)  {
                        $om->update('sale\booking\MealPreference', $group['meal_preferences_ids'], ['qty' => $group['nb_pers']], $lang);
                    }
                }
            }
        }
    }

    protected static function _getRentalUnitsCombinations($list, $target, $start, $sum, $collect) {
        $result = [];

        // current sum matches target
        if($sum == $target) {
            return [$collect];
        }

        // try sub-combinations
        for($i = $start, $n = count($list); $i < $n; ++$i) {

            // check if the sum exceeds target
            if( ($sum + $list[$i]['capacity']) > $target ) {
                continue;
            }

            // check if it is repeated or not
            if( ($i > $start) && ($list[$i]['capacity'] == $list[$i-1]['capacity']) ) {
                continue;
            }

            // take the element into the combination
            $collect[] = $list[$i];

            // recursive call
            $res = self::_getRentalUnitsCombinations($list, $target, $i + 1, $sum + $list[$i]['capacity'], $collect);

            if(count($res)) {
                foreach($res as $r) {
                    $result[] = $r;
                }
            }

            // Remove element from the combination
            array_pop($collect);
        }

        return $result;
    }


    protected static function _getRentalUnitsMatches($om, $rental_units_ids, $nb_pers_to_assign) {
        // retrieve rental units capacities
        $rental_units = [];

        if($rental_units_ids > 0 && count($rental_units_ids)) {
            $rental_units = array_values($om->read('lodging\realestate\RentalUnit', $rental_units_ids, ['id', 'capacity']));
        }

        $found = false;
        // pass-1 - search for an exact capacity match
        for($i = 0, $n = count($rental_units); $i < $n; ++$i) {
            if($rental_units[$i]['capacity'] == $nb_pers_to_assign) {
                $rental_units = [$rental_units[$i]];
                $found = true;
                break;
            }
        }
        // pass-2 - no exact match: choose between min matching capacity and spreading pers across units
        if(!$found && count($rental_units)) {
            // handle special case : smallest rental unit has bigger capacity than nb_pers
            if($nb_pers_to_assign < $rental_units[$n-1]['capacity']) {
                $rental_units = [$rental_units[$n-1]];
            }
            else {
                $i = 0;
                while($rental_units[$i]['capacity'] > $nb_pers_to_assign) {
                    // we should reach $n-2 at maximum
                    ++$i;
                }
                $alternate_index = $i-1;
                $alternate = 0;
                if($alternate_index >= 0) {
                    $rental_unit = $rental_units[$alternate_index];
                    $alternate = $rental_unit['capacity'];
                }

                $collect = [];
                $list = array_slice($rental_units, $i);

                $combinations = self::_getRentalUnitsCombinations($list, $nb_pers_to_assign, 0, 0, $collect);

                if(count($combinations)) {
                    $min_index = -1;
                    // $D = abs($alternate - $nb_pers);
                    // favour a single accomodation
                    $D = abs($alternate - $nb_pers_to_assign) / 2;

                    foreach($combinations as $index => $combination) {
                        // $R = floor($nb_pers / count($combination));
                        $R = count($combination);

                        if($R <= $D) {
                            if($min_index >= 0) {
                                if(count($combinations[$min_index]) > count($combination)) {
                                    $min_index = $index;
                                }
                            }
                            else {
                                $min_index = $index;
                            }
                        }
                    }
                    // we found at least one combination
                    if($min_index >= 0) {
                        $rental_units = $combinations[$min_index];
                    }
                    else if($alternate_index >= 0) {
                        $rental_units = [$rental_units[$alternate_index]];
                    }
                    else {
                        $rental_units = [];
                    }
                }
                else {
                    $rental_units = [];
                }
            }
        }
        return $rental_units;
    }

}