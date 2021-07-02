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
            'name' => [
                'type'              => 'computed',
                'function'          => 'sale\booking\Booking::getDisplayName',
                'result_type'       => 'string'
            ],
            
            'description' => [
                'type'              => 'string',
                'description'       => "Reason of the booking."
            ],

            'customer_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'identity\Partner',
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

// #todo            
            // origin ID (OTA)

            // A booking can have several contacts
            'contacts_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'sale\booking\Contact',
                'foreign_field'     => 'booking_id',                
                'description'       => 'List of contacts related to the booking, if any.' 
            ],

            // contracts_id
            'contracts_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'sale\booking\Contract',
                'foreign_field'     => 'booking_id',                
                'description'       => 'List of contacts related to the booking, if any.' 
            ],


            'has_contract' => [
                'type'              => 'boolean',
                'description'       => "Flag to know if a contract has been generated. Reset in case of changes before the sojourn.",
                'default'           => false
            ],
                        
            'type_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\booking\Type',
                'description'       => "The customer to whom the booking relates to.",
                'required'          => true
            ],

            'status' => [
                'type'              => 'string',
                'selection'         => ['quote', 'option', 'validated'],
                'description'       => 'Type of organisation.',
                'required'          => true
            ],

// #todo : make those fields computed (based on dates from booking line groups)
            'date_from' => [
                'type'              => 'datetime',
            ],
            'date_to' => [
                'type'              => 'datetime',
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
                'description'       => "The partner to whom the invoices have to be sent."
            ],

            
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

}