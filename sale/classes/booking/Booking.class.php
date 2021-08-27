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
                'description'       => "The customer to whom the booking relates to.",
                'required'          => true
            ],

            'center_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'lodging\identity\Center',
                'description'       => "The center to which the booking relates to.",
                'required'          => true
            ],

            'price_total' => [
                'type'              => 'computed',
                'result_type'       => 'float',
                'usage'             => 'amount/money',
                'function'          => 'sale\booking\Booking::getPriceTotal',
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
            
            'type_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\booking\BookingType',
                'description'       => "The kind of booking it is about.",
                'required'          => true
            ],

            'status' => [
                'type'              => 'string',
                'selection'         => ['quote', 'option', 'validated'],
                'description'       => 'Status of the booking.',
                'required'          => true
            ],

// #todo : make those fields computed (based on dates from booking line groups)
            'date_from' => [
                'type'              => 'computed',
                'result_type'       => 'datetime',
                'function'          => 'sale\booking\Booking::getDateFrom'
            ],

            'date_to' => [
                'type'              => 'computed',
                'result_type'       => 'datetime',
                'function'          => 'sale\booking\Booking::getDateTo'
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
                'domain'            => [ ['owner_identity_id', '=', 'object.customer_id'], ['relationship', '=', 'payer'] ],                
                'description'       => "The partner whom the invoices have to be sent to."
            ]
            
        ];
    }

    public static function getDisplayName($om, $oids, $lang) {
        $result = [];
        $bookings = $om->read(__CLASS__, $oids, ['created', 'customer_id.name']);
        foreach($bookings as $oid => $odata) {
            $result[$oid] = date("Y-m-d", $odata['created'])." - {$odata['customer_id.name']}";
        }
        return $result;              
    }

    // #todo
    public static function getDateFrom($om, $oids, $lang) {

    }

    public static function getDateTo($om, $oids, $lang) {

    }
    
    public static function getPriceTotal($om, $oids, $lang) {

    }

}