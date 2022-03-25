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
                'description'       => "Code to serve as reference (should be unique).",
                'function'          => 'getDisplayName',
                'store'             => true,
                'readonly'          => true
            ],

            'customer_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\customer\Customer',
                'description'       => "The customer whom the booking relates to (computed).",
                'onchange'          => 'onchangeCustomerId'
            ],

            'customer_identity_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'identity\Identity',
                'description'       => "The customer whom the booking relates to.",
                'onchange'          => 'onchangeCustomerIdentityId'
            ],

            'customer_nature_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\customer\CustomerNature',
                'description'       => 'Nature of the customer (synched with customer) for views convenience.',
                'onchange'          => 'onchangeCustomerNatureId'
            ],

            'center_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'lodging\identity\Center',
                'description'       => "The center to which the booking relates to.",
                'required'          => true,
                'onchange'          => 'lodging\sale\booking\Booking::onchangeCenterId'
            ],

            'center_office_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'lodging\identity\CenterOffice',
                'description'       => 'Office the invoice relates to (for center management).',
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
            ],

            'nb_pers' => [
                'type'              => 'computed',
                'result_type'       => 'integer',
                'description'       => 'Approx. amount of persons involved in the booking.',
                'function'          => 'getNbPers',
                'store'             => true
            ]
        ];
    }

    public static function getDisplayName($om, $oids, $lang) {
        $result = [];

        $bookings = $om->read(__CLASS__, $oids, ['center_id.center_office_id.code'], $lang);
        $format = Setting::get_value('sale', 'booking', 'booking.sequence_format', '%05d{sequence}');

        foreach($bookings as $oid => $booking) {

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


    public static function getCustomerNatureId($om, $oids, $lang) {
        $result = [];

        $bookings = $om->read(__CLASS__, $oids, ['customer_id.customer_nature_id'], $lang);

        if($bookings > 0) {
            foreach($bookings as $oid => $odata) {
                $result[$oid] = $odata['customer_id.customer_nature_id'];
            }
        }
        return $result;
    }

    public static function getNbPers($om, $oids, $lang) {
        $result = [];
        $bookings = $om->read(__CLASS__, $oids, ['booking_lines_groups_ids.nb_pers', 'booking_lines_groups_ids.is_autosale']);

        if($bookings > 0) {
            foreach($bookings as $bid => $booking) {
                $result[$bid] = 0;
                foreach($booking['booking_lines_groups_ids.nb_pers'] as $group_id => $group) {
                    $is_autosale = $booking['booking_lines_groups_ids.is_autosale'][$group_id]['is_autosale'];
                    if(!$is_autosale) {
                        $result[$bid] += $group['nb_pers'];
                    }
                }
            }
        }
        return $result;
    }

    /**
     * Maintain sync with Customer
     */
    public static function onchangeCustomerNatureId($om, $oids, $lang) {
        $bookings = $om->read(__CLASS__, $oids, ['customer_id', 'customer_nature_id'], $lang);

        if($bookings > 0) {
            foreach($bookings as $oid => $odata) {
                $om->write('sale\customer\Customer', $odata['customer_id'], ['customer_nature_id' => $odata['customer_nature_id']]);
            }
        }
    }

    /**
     * Maintain sync with Customer when assigning a new customer by selecting a customer_identity_id
     */
    public static function onchangeCustomerIdentityId($om, $oids, $lang) {

        $bookings = $om->read(__CLASS__, $oids, ['customer_identity_id', 'customer_id']);

        if($bookings > 0) {
            foreach($bookings as $oid => $booking) {
                if(!$booking['customer_id']) {
                    $partner_id = null;

                    // find the partner that related to this identity, if any
                    $partners_ids = $om->search('sale\customer\Customer', [
                        ['relationship', '=', 'customer'],
                        ['owner_identity_id', '=', 1],
                        ['partner_identity_id', '=', $booking['customer_identity_id']]
                    ]);
                    if(count($partners_ids)) {
                        $partner_id = reset($partners_ids);
                    }
                    else {
                        // read Identity [type_id]
                        $identities = $om->read('identity\Identity', $booking['customer_identity_id'], ['type_id']);
                        if($identities > 0) {
                            $identity = reset($identities);
                            $partner_id = $om->create('sale\customer\Customer', [
                                'partner_identity_id'   => $booking['customer_identity_id'],
                                'customer_type_id'      => $identity['type_id']
                            ]);
                        }
                    }
                    if($partner_id) {
                        $om->write(__CLASS__, $oid, ['customer_id' => $partner_id]);
                    }
                }
            }
        }
    }

    public static function onchangeCustomerId($om, $oids, $lang) {

        $bookings = $om->read(__CLASS__, $oids, ['customer_identity_id', 'booking_lines_groups_ids', 'customer_id.partner_identity_id', 'customer_id.partner_identity_id.description'], $lang);

        if($bookings > 0) {
            $booking_line_groups_ids = [];
            foreach($bookings as $bid => $booking) {
                if($booking['booking_lines_groups_ids']) {
                    $booking_line_groups_ids = array_merge($booking_line_groups_ids, $booking['booking_line_groups_ids']);
                }
                $values = [];
                if($booking['customer_id.partner_identity_id.description']) {
                    $values['description'] = $booking['customer_id.partner_identity_id.description'];
                }
                if(!$booking['customer_identity_id']) {
                    $values['customer_identity_id'] = $booking['customer_id.partner_identity_id'];
                }
                if(count($values)) {
                    $om->write(__CLASS__, $bid, $values, $lang);
                }
            }
            if(count($booking_line_groups_ids)) {
                // BookingLineGroup::_updatePriceAdapters($om, array_unique($booking_line_groups_ids), $lang);
                $om->call('lodging\sale\booking\BookingLineGroup', '_updatePriceAdapters', array_unique($booking_line_groups_ids), $lang);
            }
            $om->call(__CLASS__, '_updateAutosaleProducts', $oids, $lang);
        }
    }





    public static function onchangeCenterId($om, $oids, $lang) {
        $bookings = $om->read(__CLASS__, $oids, ['booking_lines_ids', 'center_id.center_office_id']);

        if($bookings > 0) {
            foreach($bookings as $bid => $booking) {
                $booking_lines_ids = $booking['booking_lines_ids'];
                if($booking_lines_ids > 0 && count($booking_lines_ids)) {
                    $om->call('lodging\sale\booking\BookingLine', '_updatePriceId', $booking_lines_ids, $lang);
                }
                $om->write(__CLASS__, $bid, ['center_office_id' => $booking['center_id.center_office_id']]);
            }
        }
    }

    /**
     * Signature for single object change from views.
     *
     * @param  Object   $om        Object Manager instance.
     * @param  Array    $event     Associative array holding changed fields as keys, and their related new values.
     * @param  Array    $values    Copy of the current (partial) state of the object.
     * @param  String   $lang      Language (char 2) in which multilang field are to be processed.
     * @return Array    Associative array mapping fields with their resulting values.
     */
    public static function onchange($om, $event, $values, $lang=DEFAULT_LANG) {
        $result = [];

        if(isset($event['customer_identity_id'])) {

            // find the partner that related to this identity, if any
            $partners_ids = $om->search('sale\customer\Customer', [
                ['relationship', '=', 'customer'],
                ['owner_identity_id', '=', 1],
                ['partner_identity_id', '=', $event['customer_identity_id']]
            ]);
            if(count($partners_ids)) {
                $partners = $om->read('sale\customer\Customer', $partners_ids, ['id', 'name', 'customer_nature_id.id', 'customer_nature_id.name']);
                if($partners > 0) {
                    $partner = reset($partners);
                    $result['customer_id'] = ['id' => $partner['id'], 'name' => $partner['name']];
                    if(isset($partner['customer_nature_id.id']) && $partner['customer_nature_id.id']) {
                        $result['customer_nature_id'] = ['id' => $partner['customer_nature_id.id'], 'name' => $partner['customer_nature_id.name'] ];
                    }
                }
            }
        }

        return $result;
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

        // loop through bookings and create groups for autosale products, if any
        foreach($bookings as $booking_id => $booking) {

            $groups_ids_to_delete = [];
            $booking_lines_groups = $om->read('lodging\sale\booking\BookingLineGroup', $booking['booking_lines_groups_ids'], ['is_autosale'], $lang);
            if($booking_lines_groups > 0) {
                foreach($booking_lines_groups as $gid => $group) {
                    if($group['is_autosale']) {
                        $groups_ids_to_delete[] = -$gid;
                    }
                }
                $om->write(__CLASS__, $booking_id, ['booking_lines_groups_ids' => $groups_ids_to_delete], $lang);
            }

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