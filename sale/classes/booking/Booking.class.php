<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace sale\booking;
use equal\orm\Model;

class Booking extends Model {


    public static function getColumns() {
        return [
            'creator' => [
                'type'              => 'many2one',
                'foreign_object'    => 'identity\User',
                'description'       => 'User who created the entry.',
            ],

            'name' => [
                'type'              => 'computed',
                'result_type'       => 'string',
                'description'       => "Code to serve as reference (might not be unique)",
                'function'          => 'sale\booking\Booking::getDisplayName',
                'store'             => true
            ],
            
            'description' => [
                'type'              => 'text',
                'usage'             => '',
                'description'       => "Reason of the booking, for internal use.",
                'default'           => ''
            ],

            'customer_reference' => [
                'type'              => 'string',
                'description'       => "Code or short string given by the customer as own reference, if any.",
                'default'           => ''
            ],

            'customer_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\customer\Customer',
                'domain'            => ['relationship', '=', 'customer'],
                'description'       => "The customer whom the booking relates to.",
                'required'          => true,
                'onchange'          => 'sale\booking\Booking::onchangeCustomerId'
            ],


            'customer_identity_id' => [
                'type'              => 'computed',
                'result_type'       => 'integer',
                'function'          => 'sale\booking\Booking::getCustomerIdentityId',
                'description'       => "The identifier of the Customer identity."
            ],

            'center_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'lodging\identity\Center',
                'description'       => "The center to which the booking relates to.",
                'required'          => true,
                'onchange'          => 'sale\booking\Booking::onchangeCenterId'
            ],

            'price' => [
                'type'              => 'computed',
                'result_type'       => 'float',
                'usage'             => 'amount/money',
                'function'          => 'sale\booking\Booking::getPrice',
                'description'       => 'Total price (vat incl.) of the booking.'
            ],

// #todo            
            // origin ID (OTA)

            // A booking can have several contacts (extending identity\Partner)
            'contacts_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'sale\booking\Contact',
                'foreign_field'     => 'booking_id',                
                'description'       => 'List of contacts related to the booking, if any.' 
            ],

            'has_contract' => [
                'type'              => 'boolean',
                'description'       => "Has a contract been generated yet? Flag is reset in case of changes before the sojourn.",
                'default'           => false
            ],

            'contracts_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'sale\booking\Contract',
                'foreign_field'     => 'booking_id',                
                'description'       => 'List of contacts related to the booking, if any.' 
            ],

            'booking_lines_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'sale\booking\BookingLine',
                'foreign_field'     => 'booking_id',
                'description'       => 'Detailed consumptions of the booking.' 
            ],

            'booking_lines_groups_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'sale\booking\BookingLineGroup',
                'foreign_field'     => 'booking_id',
                'description'       => 'Grouped consumptions of the booking.' 
            ],

            'composition_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\booking\Composition',
                'description'       => 'The composition that relates to the booking.' 
            ],
            
            'type_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\booking\BookingType',
                'description'       => "The kind of booking it is about.",
                'required'          => true
            ],

            'status' => [
                'type'              => 'string',
                'selection'         => ['quote', 'option', 'validated', 'checkedin', 'checkedout', 'due_balance', 'credit_balance', 'balanced'],
                'description'       => 'Status of the booking.',
                'default'           => 'quote'
            ],

            'payment_status' => [
                'type'              => 'computed',
                'result_type'       => 'string',
                'function'          => 'sale\booking\Booking::getPaymentStatus'
            ],


            // date fields are based on dates from booking line groups
            'date_from' => [
                'type'              => 'computed',
                'result_type'       => 'date',
                'function'          => 'sale\booking\Booking::getDateFrom',
                'store'             => true
            ],

            'date_to' => [
                'type'              => 'computed',
                'result_type'       => 'date',
                'function'          => 'sale\booking\Booking::getDateTo',
                'store'             => true                
            ],

            'has_payer_organisation' => [
                'type'              => 'boolean',
                'description'       => "Flag to know if invoice must be sent to another Identity.",
                'default'           => false
            ],

            'payer_organisation_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'identity\Partner',
                'visible'           => [ 'has_payer_organisation', '=', true ],
                'domain'            => [ ['owner_identity_id', '=', 'object.customer_identity_id'], ['relationship', '=', 'payer'] ],                
                'description'       => "The partner whom the invoices have to be sent to."
            ]
            
        ];
    }

    public static function getDisplayName($om, $oids, $lang) {
        $result = [];
        $bookings = $om->read(__CLASS__, $oids, ['created', 'customer_id', 'customer_id.partner_identity_id'], $lang);

        foreach($bookings as $oid => $odata) {
            $increment = 1;
            // search for bookings made the same day by same customer, if any
            if(!empty($odata['customer_id'])) {
                $bookings_ids = $om->search(__CLASS__, [ ['created', '=', $odata['created']], ['customer_id','=', $odata['customer_id']] ]);
                $increment = count($bookings_ids);    
            }
            $result[$oid] = sprintf("%s-%08d-%02d", date("ymd", $odata['created']), $odata['customer_id.partner_identity_id'], $increment);
        }
        return $result;              
    }

    public static function getDateFrom($om, $oids, $lang) {
        $result = [];
        $bookings = $om->read(__CLASS__, $oids, ['booking_lines_groups_ids']);
        
        foreach($bookings as $bid => $booking) {
            $min_date = PHP_INT_MAX;
            $booking_line_groups = $om->read('sale\booking\BookingLineGroup', $booking['booking_lines_groups_ids'], ['date_from']);
            if($booking_line_groups > 0 && count($booking_line_groups)) {
                foreach($booking_line_groups as $gid => $group) {
                    if($group['date_from'] < $min_date) {
                        $min_date = $group['date_from'];
                    }
                }
                $result[$bid] = $min_date;    
            }
        }

        return $result;
    }

    public static function getDateTo($om, $oids, $lang) {
        $result = [];
        $bookings = $om->read(__CLASS__, $oids, ['booking_lines_groups_ids']);
        
        foreach($bookings as $bid => $booking) {
            $max_date = 0;
            $booking_line_groups = $om->read('sale\booking\BookingLineGroup', $booking['booking_lines_groups_ids'], ['date_to']);
            if($booking_line_groups > 0 && count($booking_line_groups)) {            
                foreach($booking_line_groups as $gid => $group) {
                    if($group['date_to'] > $max_date) {
                        $max_date = $group['date_to'];
                    }
                }
                $result[$bid] = $max_date;
            }
        }

        return $result;
    }

    public static function getPaymentStatus($om, $oids, $lang) {
        // #todo
    }

    public static function getCustomerIdentityId($om, $oids, $lang) {
        $result = [];
        $bookings = $om->read(__CLASS__, $oids, ['customer_id.partner_identity_id']);

        foreach($bookings as $oid => $booking) {
            $result[$oid] = (int) $booking['customer_id.partner_identity_id'];
        }
        return $result;
    }

    public static function getPrice($om, $oids, $lang) {
        $result = [];
        $bookings = $om->read(__CLASS__, $oids, ['booking_lines_groups_ids']);
        if($bookings > 0 && count($bookings)) {            
            foreach($bookings as $bid => $booking) {
                $groups = $om->read('sale\booking\BookingLineGroup', $booking['booking_lines_groups_ids'], ['price']);
                $result[$bid] = 0.0;
                if($groups > 0 && count($groups)) {
                    foreach($groups as $group) {
                        $result[$bid] += $group['price'];
                    }
                }
            }
        }
        return $result;
    }    

    public static function onchangeCustomerId($om, $oids, $lang) {        
        $om->write(__CLASS__, $oids, ['name' => null]);
        // force immediate recomputing of the name/reference
        $booking_lines_groups_ids = $om->read(__CLASS__, $oids, ['name', 'booking_lines_groups_ids']);
        if($booking_lines_groups_ids > 0 && count($booking_lines_groups_ids)) {
            BookingLineGroup::_updatePriceAdapters($om, $booking_lines_groups_ids, $lang);
        }
    }

    public static function onchangeCenterId($om, $oids, $lang) {
        $booking_lines_ids = $om->read(__CLASS__, $oids, ['booking_lines_ids']);
        if($booking_lines_ids > 0 && count($booking_lines_ids)) {
            BookingLine::_updatePriceId($om, $booking_lines_ids, $lang);
        }
    }

}