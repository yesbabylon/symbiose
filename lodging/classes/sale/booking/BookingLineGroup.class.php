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

            'is_extra' => [
                'type'              => 'boolean',
                'description'       => 'Does the group relate to sales made off-contract? (ex. point of sale)',
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

            'sojourn_type_id' => [
                'type'              => 'string',
                'default'           => 1,
                'description'       => 'The kind of sojourn the group is about.',
                'onupdate'          => 'onupdateSojournTypeId'
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
                'description'       => "The rate class that applies to the group.",
                'required'          => true,
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
                'foreign_object'    => 'lodging\sale\booking\Booking',
                'description'       => 'Booking the line relates to (for consistency, lines should be accessed using the group they belong to).',
                'required'          => true,
                'ondelete'          => 'cascade'         // delete group when parent booking is deleted
            ],

            'accomodations_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'lodging\sale\booking\BookingLine',
                'foreign_field'     => 'booking_line_group_id',
                'description'       => 'Booking lines relating to accomodations.',
                'ondetach'          => 'delete',
                'domain'            => ['is_accomodation', '=', true]
            ],

            'rental_unit_assignments_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'lodging\sale\booking\BookingLineRentalUnitAssignement',
                'foreign_field'     => 'booking_line_group_id',
                'description'       => "The rental units lines of the group are assigned to (from lines)."
            ],

            'age_range_assignments_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'lodging\sale\booking\BookingLineGroupAgeRangeAssignment',
                'foreign_field'     => 'booking_line_group_id',
                'description'       => 'Age range assignments defined for the group.',
                'ondelete'          => 'cascade',
                'ondetach'          => 'delete'
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

    public static function calcVatRate($om, $oids, $lang) {
        $result = [];
        $lines = $om->read(__CLASS__, $oids, ['price_id.accounting_rule_id.vat_rule_id.rate']);
        foreach($lines as $oid => $odata) {
            $result[$oid] = floatval($odata['price_id.accounting_rule_id.vat_rule_id.rate']);
        }
        return $result;
    }

    public static function calcQty($om, $oids, $lang) {
        $result = [];
        $groups = $om->read(__CLASS__, $oids, ['has_pack', 'is_locked', 'pack_id.product_model_id.qty_accounting_method', 'nb_pers', 'nb_nights']);
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

        $groups = $om->read(__CLASS__, $oids, ['price_id.price']);

        if($groups > 0 && count($groups)) {
            foreach($groups as $gid => $group) {

                $price_adapters_ids = $om->search('lodging\sale\booking\BookingPriceAdapter', [
                    ['booking_line_group_id', '=', $gid],
                    ['booking_line_id','=', 0],
                    ['is_manual_discount', '=', false]
                ]);

                $disc_value = 0.0;
                $disc_percent = 0.0;

                if($price_adapters_ids > 0) {
                    $adapters = $om->read('lodging\sale\booking\BookingPriceAdapter', $price_adapters_ids, ['type', 'value', 'discount_id.discount_list_id.rate_max']);

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

        $groups = $om->read(__CLASS__, $oids, ['booking_lines_ids', 'total', 'vat_rate', 'is_locked', 'has_pack']);

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
        $groups = $om->read(__CLASS__, $oids, ['booking_id', 'booking_lines_ids', 'is_locked', 'has_pack', 'unit_price', 'qty']);
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


    public static function onupdateHasPack($om, $oids, $values, $lang) {
        trigger_error("QN_DEBUG_ORM::calling lodging\sale\booking\BookingLineGroup:onchangeHasPack", QN_REPORT_DEBUG);

        $groups = $om->read(__CLASS__, $oids, ['has_pack']);
        if($groups > 0 && count($groups)) {
            foreach($groups as $gid => $group) {
                if(!$group['has_pack']) {
                    $om->write(__CLASS__, $gid, ['is_locked' => false, 'pack_id' => null ]);
                }
            }
        }
    }

    public static function onupdateIsLocked($om, $oids, $values, $lang) {
        trigger_error("QN_DEBUG_ORM::calling lodging\sale\booking\BookingLineGroup:onchangeIsLocked", QN_REPORT_DEBUG);
        // invalidate prices
        $om->callonce('sale\booking\BookingLineGroup', '_resetPrices', $oids, [], $lang);
        $om->callonce(__CLASS__, '_updatePriceId', $oids, [], $lang);
    }

    public static function onupdatePriceId($om, $oids, $values, $lang) {
        // invalidate prices
        $om->callonce('sale\booking\BookingLineGroup', '_resetPrices', $oids, [], $lang);
    }

    /**
     * Update is_locked field according to selected pack (pack_id).
     * (This is done when pack_id is changed, but can be manually set by the user afterward.)
     *
     * Since this method is called, we assume that current group has 'has_pack' set to true,
     * and that pack_id relates to a product_model that is a pack.
     */
    public static function onupdatePackId($om, $oids, $values, $lang) {
        trigger_error("QN_DEBUG_ORM::calling lodging\sale\booking\BookingLineGroup:onchangePackId", QN_REPORT_DEBUG);
        // invalidate prices
        $om->callonce('sale\booking\BookingLineGroup', '_resetPrices', $oids, [], $lang);
        // generate booking lines
        $om->callonce(__CLASS__, '_updatePackId', $oids, [], $lang);
        // update groups, if necessary
        $groups = $om->read(__CLASS__, $oids, [
            'booking_id',
            'date_from',
            'nb_pers',
            'is_locked',
            'booking_lines_ids',
            'pack_id.product_model_id.qty_accounting_method',
            'pack_id.product_model_id.has_duration',
            'pack_id.product_model_id.duration',
            'pack_id.product_model_id.capacity',
            'pack_id.product_model_id.booking_type'
        ]);

        foreach($groups as $gid => $group) {
            // if model of chosen product has a non-generic booking type, update the booking of the group accordingly
            if(isset($group['pack_id.product_model_id.booking_type']) && $group['pack_id.product_model_id.booking_type'] != 'general') {
                $om->write('lodging\sale\booking\Booking', $group['booking_id'], ['type' => $group['pack_id.product_model_id.booking_type']]);
            }

            $updated_fields = ['vat_rate' => null];

            // if targeted product model has its own duration, date_to is updated accordingly
            if($group['pack_id.product_model_id.has_duration']) {
                $updated_fields['date_to'] = $group['date_from'] + ($group['pack_id.product_model_id.duration'] * 60*60*24);
                // will update price_adapters, nb_nights
            }

            // always update nb_pers
            // to make sure to trigger self::_updatePriceAdapters and BookingLine::_updateQty
            $updated_fields['nb_pers'] = $group['nb_pers'];
            if($group['pack_id.product_model_id.qty_accounting_method'] == 'accomodation' && $group['pack_id.product_model_id.capacity'] > 0) {
                $updated_fields['nb_pers'] = $group['pack_id.product_model_id.capacity'];
            }

            $om->write(__CLASS__, $gid, $updated_fields, $lang);
        }
    }

    public static function onupdateDateFrom($om, $oids, $values, $lang) {
        trigger_error("QN_DEBUG_ORM::calling lodging\sale\booking\BookingLineGroup:onchangeDateFrom", QN_REPORT_DEBUG);
        // invalidate prices
        $om->callonce('sale\booking\BookingLineGroup', '_resetPrices', $oids, [], $lang);

        $om->write(__CLASS__, $oids, ['nb_nights' => null ]);
        $om->callonce(__CLASS__, '_updatePriceAdapters', $oids, [], $lang);
        $om->callonce(__CLASS__, '_updateAutosaleProducts', $oids, [], $lang);

        // update bookinglines
        $groups = $om->read(__CLASS__, $oids, ['booking_id', 'is_sojourn', 'has_pack', 'nb_nights', 'booking_lines_ids']);
        if($groups > 0 && count($groups)) {
            foreach($groups as $group) {
                // notify booking lines that price_id has to be updated
                $om->callonce('lodging\sale\booking\BookingLine', '_updatePriceId', $group['booking_lines_ids'], [], $lang);
                // recompute bookinglines quantities
                $om->callonce('lodging\sale\booking\BookingLine', '_updateQty', $group['booking_lines_ids'], [], $lang);
                if($group['is_sojourn']) {
                    // force parent booking to recompute date_from
                    $om->write('lodging\sale\booking\Booking', $group['booking_id'], ['date_from' => null]);
                }
            }
        }
    }

    public static function onupdateDateTo($om, $oids, $values, $lang) {
        trigger_error("QN_DEBUG_ORM::calling lodging\sale\booking\BookingLineGroup:onchangeDateTo", QN_REPORT_DEBUG);
        // invalidate prices
        $om->callonce('sale\booking\BookingLineGroup', '_resetPrices', $oids, [], $lang);

        $om->write(__CLASS__, $oids, ['nb_nights' => null ]);
        $om->callonce(__CLASS__, '_updatePriceAdapters', $oids, [], $lang);
        $om->callonce(__CLASS__, '_updateAutosaleProducts', $oids, [], $lang);

        // update bookinglines
        $groups = $om->read(__CLASS__, $oids, ['booking_id', 'is_sojourn', 'has_pack', 'nb_nights', 'nb_pers', 'booking_lines_ids']);
        if($groups > 0) {
            foreach($groups as $group) {
                // re-compute bookinglines quantities
                $om->callonce('lodging\sale\booking\BookingLine', '_updateQty', $group['booking_lines_ids'], [], $lang);
                if($group['is_sojourn']) {
                    // force parent booking to recompute date_from
                    $om->write('lodging\sale\booking\Booking', $group['booking_id'], ['date_to' => null]);
                }
            }
        }
    }

    public static function onupdateBookingLinesIds($om, $oids, $values, $lang) {
        $om->callonce('sale\booking\BookingLineGroup', '_resetPrices', $oids, [], $lang);
    }

    public static function onupdateRateClassId($om, $oids, $values, $lang) {
        trigger_error("QN_DEBUG_ORM::calling lodging\sale\booking\BookingLineGroup:onchangeRateClassId", QN_REPORT_DEBUG);
        $om->callonce('sale\booking\BookingLineGroup', '_resetPrices', $oids, [], $lang);
        $om->callonce(__CLASS__, '_updatePriceAdapters', $oids, [], $lang);
    }

    public static function onupdateSojournTypeId($om, $oids, $values, $lang) {
        trigger_error("QN_DEBUG_ORM::calling lodging\sale\booking\BookingLineGroup:onchangeSojournTypeId", QN_REPORT_DEBUG);
        $om->callonce('sale\booking\BookingLineGroup', '_resetPrices', $oids, [], $lang);
        $om->callonce(__CLASS__, '_updatePriceAdapters', $oids, [], $lang);
    }

    public static function onupdateNbPers($om, $oids, $values, $lang) {
        trigger_error("QN_DEBUG_ORM::calling lodging\sale\booking\BookingLineGroup:onchangeNbPers", QN_REPORT_DEBUG);
        // invalidate prices
        $om->callonce('sale\booking\BookingLineGroup', '_resetPrices', $oids, [], $lang);

        $om->callonce(__CLASS__, '_updatePriceAdapters', $oids, [], $lang);
        $om->callonce(__CLASS__, '_updateAutosaleProducts', $oids, [], $lang);
        $om->callonce(__CLASS__, '_updateMealPreferences', $oids, [], $lang);

        // update bookinglines
        $groups = $om->read(__CLASS__, $oids, ['booking_id', 'nb_nights', 'nb_pers', 'has_pack', 'is_locked', 'booking_lines_ids']);
        $bookings_ids = [];
        if($groups > 0) {
            $booking_lines_ids = [];
            foreach($groups as $group) {
                $booking_lines_ids = array_merge($group['booking_lines_ids']);
                $bookings_ids[] = $group['booking_id'];
            }
            // re-compute bookinglines quantities
            $om->callonce('lodging\sale\booking\BookingLine', '_updateQty', $booking_lines_ids, [], $lang);
        }
        // reset parent bookings nb_pers
        $om->write('sale\booking\Booking', $bookings_ids, ['nb_pers' => null]);
    }



    /**
     * Check wether an object can be created, and optionally perform additional operations.
     * These tests come in addition to the unique constraints return by method `getUnique()`.
     * This method can be overriden to define a more precise set of tests.
     *
     * @param  object   $om         ObjectManager instance.
     * @param  array    $values     Associative array holding the values to be assigned to the new instance (not all fields might be set).
     * @param  string   $lang       Language in which multilang fields are being updated.
     * @return array    Returns an associative array mapping fields with their error messages. En empty array means that object has been successfully processed and can be created.
     */
    public static function cancreate($om, $values, $lang) {
        $bookings = $om->read('lodging\sale\booking\Booking', $values['booking_id'], ['status'], $lang);

        if($bookings) {
            $booking = reset($bookings);

            if(
                in_array($booking['status'], ['invoiced', 'debit_balance', 'credit_balance', 'balanced'])
                ||
                ($booking['status'] != 'quote' && (!isset($values['is_extra']) ||!$values['is_extra']))
            ) {
                return ['status' => ['non_editable' => 'Non-extra service lines cannot be changed for non-quote bookings.']];
            }
        }

        return parent::cancreate($om, $values, $lang);
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
    public static function canupdate($om, $oids, $values, $lang=DEFAULT_LANG) {
        $groups = $om->read(get_called_class(), $oids, ['booking_id.status', 'is_extra'], $lang);

        if($groups > 0) {
            foreach($groups as $group) {
                if(
                    in_array($group['booking_id.status'], ['invoiced', 'debit_balance', 'credit_balance', 'balanced'])
                    ||
                    ($group['booking_id.status'] != 'quote' && !$group['is_extra'])
                ) {
                    return ['status' => ['non_editable' => 'Non-extra service lines cannot be changed for non-quote bookings.']];
                }
            }
        }

        return parent::canupdate($om, $oids, $values, $lang);
    }

    /**
     * Check wether an object can be deleted, and perform some additional operations if necessary.
     * This method can be overriden to define a more precise set of tests.
     *
     * @param  object   $om         ObjectManager instance.
     * @param  array    $oids       List of objects identifiers.
     * @return boolean  Returns true if the object can be deleted, or false otherwise.
     */
    public static function candelete($om, $oids) {
        $groups = $om->read(get_called_class(), $oids, ['booking_id', 'booking_id.status', 'is_extra']);

        if($groups > 0) {
            foreach($groups as $group) {
                if(
                    in_array($group['booking_id.status'], ['invoiced', 'debit_balance', 'credit_balance', 'balanced'])
                    ||
                    ($group['booking_id.status'] != 'quote' && !$group['is_extra'])
                ) {
                    return ['status' => ['non_editable' => 'Non-extra service lines cannot be changed for non-quote bookings.']];
                }
            }
        }

        return parent::candelete($om, $oids);
    }

    /**
     * Create Price adapters according to group settings.
     *
     * Price adapters are applied only on meal and accomodation products
     *
     * (This method is called upon booking_id.customer_id change)
     */
    public static function _updatePriceAdapters($om, $oids, $values, $lang) {
        trigger_error("QN_DEBUG_ORM::calling lodging\sale\booking\BookingLineGroup:_updatePriceAdapters (".implode(',', $oids).")", QN_REPORT_DEBUG);
        /*
            Remove all previous price adapters that were automatically created
        */
        $price_adapters_ids = $om->search('lodging\sale\booking\BookingPriceAdapter', [ ['booking_line_group_id', 'in', $oids], ['is_manual_discount','=', false]]);

        $om->remove('lodging\sale\booking\BookingPriceAdapter', $price_adapters_ids, true);

        $line_groups = $om->read(__CLASS__, $oids, ['rate_class_id', 'sojourn_type_id', 'date_from', 'date_to', 'nb_pers', 'nb_nights', 'booking_id', 'is_locked',
                                                    'booking_lines_ids',
                                                    'booking_id.customer_id.count_booking_24',
                                                    'booking_id.center_id.season_category_id',
                                                    'booking_id.center_id.discount_list_category_id']);

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
                $operands['duration'] = $group['nb_nights'];     // duration in nights
                $operands['nb_pers'] = $group['nb_pers'];        // number of participants

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


    /**
     * Update pack_id and re-create booking lines accordingly.
     *
     */
    public static function _updatePackId($om, $oids, $values, $lang) {
        trigger_error("QN_DEBUG_ORM::calling lodging\sale\booking\BookingLineGroup:_updatePackId", QN_REPORT_DEBUG);

        $groups = $om->read(__CLASS__, $oids, [
            'booking_id', 'booking_lines_ids',
            'pack_id.is_locked',
            'pack_id.pack_lines_ids',
            'pack_id.product_model_id.has_own_price'
        ]);

        foreach($groups as $gid => $group) {

            /*
                Update current group according to selected pack
            */

            // might need to update price_id
            if($group['pack_id.product_model_id.has_own_price']) {
                $om->write(__CLASS__, $gid, ['is_locked' => true], $lang);
            }
            else {
                $om->write(__CLASS__, $gid, ['is_locked' => $group['pack_id.is_locked'] ], $lang);
            }

            /*
                Reset booking_lines (updating booking_lines_ids will trigger ondetach event)
            */
            $om->write(__CLASS__, $gid, ['booking_lines_ids' => array_map(function($a) { return "-$a";}, $group['booking_lines_ids'])]);

            /*
                Create booking lines according to pack composition
            */
            $pack_lines = $om->read('lodging\sale\catalog\PackLine', $group['pack_id.pack_lines_ids'], [
                'child_product_id',
                'has_own_qty', 'own_qty',
                'has_own_duration', 'own_duration',
                'child_product_id.product_model_id.qty_accounting_method'
            ]);
            $order = 1;

            foreach($pack_lines as $pid => $pack_line) {
                $line = [
                    'order'                     => $order,
                    'booking_id'                => $group['booking_id'],
                    'booking_line_group_id'     => $gid,
                    'product_id'                => $pack_line['child_product_id'],
                    'qty_accounting_method'     => $pack_line['child_product_id.product_model_id.qty_accounting_method']
                ];
                if($pack_line['has_own_qty']) {
                    $line['has_own_qty'] = true;
                    $line['qty'] = $pack_line['own_qty'];
                }
                if($pack_line['has_own_duration']) {
                    $line['has_own_duration'] = true;
                    $line['own_duration'] = $pack_line['own_duration'];
                }
                $lid = $om->create('lodging\sale\booking\BookingLine', $line, $lang);
                if($lid > 0) {
                    $om->write(__CLASS__, $gid, ['booking_lines_ids' => ["+$lid"] ]);
                }
                ++$order;
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

        $groups = $om->read(__CLASS__, $oids, [
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
                        $om->write(__CLASS__, $gid, ['price_id' => $prices_ids[0]]);
                        break;
                    }
                }
            }
            if(!$found) {
                $om->write(__CLASS__, $gid, ['price_id' => null, 'vat_rate' => 0, 'unit_price' => 0]);
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
        $groups = $om->read(__CLASS__, $oids, [
                                                    'is_autosale',
                                                    'nb_pers',
                                                    'nb_nights',
                                                    'date_from',
                                                    'date_to',
                                                    'booking_id',
                                                    'booking_id.center_id.autosale_list_category_id',
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
                $om->write(__CLASS__, $group_id, ['booking_lines_ids' => $lines_ids_to_delete], $lang);
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

        $groups = $om->read(__CLASS__, $oids, [
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
                        $om->write('sale\booking\MealPreference', $group['meal_preferences_ids'], ['qty' => $group['nb_pers']], $lang);
                    }
                }
            }
        }
    }

}