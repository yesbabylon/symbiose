<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace lodging\sale\booking;
use core\setting\Setting;

class Booking extends \sale\booking\Booking {


    public static function getColumns() {
        return [

            'name' => [
                'type'              => 'computed',
                'result_type'       => 'string',
                'description'       => "Code to serve as reference (should be unique)",
                'function'          => 'lodging\sale\booking\Booking::getDisplayName',
                'store'             => true,
                'readonly'          => true
            ],

            'type'  => [
                'type'              => 'string',
                'selection'         => [
                    'general_public',
                    'school_stay',
                    'sport_camp'
                ],
                'readonly'          => true,
                'default'           => 'general_public'
            ],

            'customer_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\customer\Customer',
                'domain'            => ['relationship', '=', 'customer'],
                'description'       => "The customer to whom the booking relates.",
                'required'          => true,
                'onchange'          => 'lodging\sale\booking\Booking::onchangeCustomerId'
            ],

            'center_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'lodging\identity\Center',
                'description'       => "The center to which the booking relates to.",
                'required'          => true,
                'onchange'          => 'lodging\sale\booking\Booking::onchangeCenterId'
            ],

            'contracts_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'lodging\sale\booking\Contract',
                'foreign_field'     => 'booking_id',
                'sort'              => 'desc',
                'description'       => 'List of contacts related to the booking, if any.'
            ],

            'consumptions_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'lodging\sale\booking\Consumption',
                'foreign_field'     => 'booking_id',
                'description'       => 'Consumptions related to the booking.'
            ],

            'booking_lines_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'lodging\sale\booking\BookingLine',
                'foreign_field'     => 'booking_id',
                'description'       => 'Detailed consumptions of the booking.'
            ],

            'booking_lines_groups_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'lodging\sale\booking\BookingLineGroup',
                'foreign_field'     => 'booking_id',
                'description'       => 'Grouped consumptions of the booking.'
            ],

            'rental_unit_assignments_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'lodging\sale\booking\BookingLineRentalUnitAssignement',
                'foreign_field'     => 'booking_id',
                'description'       => 'Rental units assignments related to the booking.'
            ],

            'price' => [
                'type'              => 'computed',
                'result_type'       => 'float',
                'usage'             => 'amount/money:2',
                'function'          => 'lodging\sale\booking\Booking::getPrice',
                'description'       => 'Final total tax-included price of the booking (computed).'
            ],

            'fundings_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'lodging\sale\booking\Funding',
                'foreign_field'     => 'booking_id',
                'description'       => 'Fundings that relate to the booking.',
                'ondetach'          => 'delete'
            ],

            'invoices_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'lodging\sale\booking\Invoice',
                'foreign_field'     => 'booking_id',
                'description'       => 'Invoices that relate to the booking.'
            ]
        ];
    }

    public static function getDisplayName($om, $oids, $lang) {
        $result = [];

        $bookings = $om->read(__CLASS__, $oids, ['center_id.center_office_id.code'], $lang);
        $format = Setting::get_value('sale', 'booking', 'booking.sequence_format', '%05d{sequence}');

        foreach($bookings as $oid => $booking) {
            $result[$oid] = '';

            $code = 'booking.sequence.'.$booking['center_id.center_office_id.code'];
            $sequence = Setting::get_value('sale', 'booking', $code);

            if($sequence) {
                Setting::set_value('sale', 'booking', $code, $sequence + 1);

                $result[$oid] = Setting::parse_format($format, [
                    'center'    => $booking['center_id.center_office_id.code'],
                    'sequence'  => $sequence
                ]);
            }

        }
        return $result;
    }


    public static function getPrice($om, $oids, $lang) {
        $result = [];
        $bookings = $om->read(__CLASS__, $oids, ['booking_lines_groups_ids']);
        if($bookings > 0 && count($bookings)) {
            foreach($bookings as $bid => $booking) {
                $groups = $om->read('lodging\sale\booking\BookingLineGroup', $booking['booking_lines_groups_ids'], ['price']);
                $result[$bid] = 0.0;
                if($groups > 0 && count($groups)) {
                    foreach($groups as $group) {
                        $result[$bid] += $group['price'];
                    }
                    $result[$bid] = round($result[$bid], 2);
                }
            }
        }
        return $result;
    }

    public static function onchangeCustomerId($om, $oids, $lang) {
        // force immediate recomputing of the name/reference
        $om->write(__CLASS__, $oids, ['name' => null]);
        $bookings = $om->read(__CLASS__, $oids, ['name', 'booking_lines_groups_ids', 'customer_id.partner_identity_id.description']);

        if($bookings > 0 && count($bookings) > 0) {
            foreach($bookings as $bid => $booking) {
                $booking_lines_groups_ids = $booking['booking_lines_groups_ids'];
                if($booking_lines_groups_ids > 0 && count($booking_lines_groups_ids)) {
                    BookingLineGroup::_updatePriceAdapters($om, $booking_lines_groups_ids, $lang);
                }
                $om->write(__CLASS__, $bid, ['description' => $booking['customer_id.partner_identity_id.description']]);
            }
            Booking::_updateAutosaleProducts($om, $oids, $lang);            
        }
    }

    public static function onchangeCenterId($om, $oids, $lang) {
        $bookings = $om->read(__CLASS__, $oids, ['booking_lines_ids']);

        if($bookings > 0 && count($bookings) > 0) {
            foreach($bookings as $bid => $booking) {
                $booking_lines_ids = $booking['booking_lines_ids'];
                if($booking_lines_ids > 0 && count($booking_lines_ids)) {
                    BookingLine::_updatePriceId($om, $booking_lines_ids, $lang);
                }
            }
        }
    }


    /**
     * Generate one or more groups for products saled automatically.
     * We generate services groups related to autosales when the  is updated
     * customer, date_from, date_to, center_id
     *
     */
    public static function _updateAutosaleProducts($om, $oids, $lang) {

        /*
            remove groups related to autosales that already exist
        */
        $bookings = $om->read(__CLASS__, $oids, [
                                                    'id',
                                                    'customer_id.rate_class_id',
                                                    'customer_id.count_booking_12',
                                                    'booking_lines_groups_ids',
                                                    'date_from',
                                                    'date_to',
                                                    'center_id.autosale_list_category_id'
                                                ], $lang);

        $groups_ids_to_delete = [];
        foreach($bookings as $bid => $booking) {
            $booking_lines_groups = $om->read('lodging\sale\booking\BookingLineGroup', $booking['booking_lines_groups_ids'], ['is_autosale'], $lang);
            foreach($booking_lines_groups as $gid => $group) {
                if($group['is_autosale']) {
                    $groups_ids_to_delete[] = $gid;
                }
            }
        }
        $om->remove('lodging\sale\booking\BookingLineGroup', $groups_ids_to_delete, true);

        // loop through bookings and create groups for autosale products, if any
        foreach($bookings as $booking_id => $booking) {

            /*
                Find the first Autosale List that matches the booking dates
            */

            $autosale_lists_ids = $om->search('sale\autosale\AutosaleList', [
                ['autosale_list_category_id', '=', $booking['center_id.autosale_list_category_id']],
                ['date_from', '<=', $booking['date_from']],
                ['date_to', '>=', $booking['date_from']]
            ]);

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
                $operands['count_booking_12'] = $booking['customer_id.count_booking_12'];

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

                // filter discounts to be applied on booking lines
                foreach($autosales as $autosale_id => $autosale) {
                    if($autosale['scope'] != 'booking') continue;
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

                // apply all applicable products
                $count = count($products_to_apply);

                if($count) {
                    // create a new BookingLine Group dedicated to autosale products                    
                    $group = [
                        'name'          => 'Suppléments obligatoires',
                        'booking_id'    => $booking_id,
                        'rate_class_id' => $booking['customer_id.rate_class_id'],
                        'date_from'     => $booking['date_from'],
                        'date_to'       => $booking['date_to'],
                        'is_autosale'   => true
                    ];
                    if($count == 1) {
                        // in case of a single line, overwrite group name
                        foreach($products_to_apply as $autosale_id => $product) {
                            $group['name'] = $product['name'];
                        }
                    }
                    $gid = $om->create('lodging\sale\booking\BookingLineGroup', $group, $lang);

                    // add all applicable products to the group
                    $order = 1;
                    foreach($products_to_apply as $autosale_id => $product) {
                        // create a line relating to the product
                        $line = [
                            'order'                     => $order++,
                            'booking_id'                => $booking_id,
                            'booking_line_group_id'     => $gid,
                            'product_id'                => $product['id'],
                            'has_own_qty'               => $product['has_own_qty'],
                            'qty'                       => $product['qty']
                        ];
                        $om->create('lodging\sale\booking\BookingLine', $line, $lang);
                    }
                }
            }
            else {
                $date = date('Y-m-d', $booking['date_from']);
                trigger_error("QN_DEBUG_ORM::no matching autosale list found for date {$date}", QN_REPORT_DEBUG);
            }
        }
    }

}