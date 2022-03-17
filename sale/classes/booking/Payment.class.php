<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace sale\booking;

class Payment extends \sale\pay\Payment {

    public static function getColumns() {

        return [           

            'booking_id' => [
                'type'              => 'computed',
                'result_type'       => 'many2one',
                'function'          => 'sale\booking\Payment::getBookingId',
                'foreign_object'    => 'sale\booking\Booking',
                'description'       => 'The booking the payement relates to, if any (computed).',
                'store'             => true
            ],

            'funding_id' => [ 
                'type'              => 'many2one',
                'foreign_object'    => 'sale\booking\Funding',
                'description'       => 'The funding the payement relates to, if any.'
            ]            

        ];
    }


    public static function getBookingId($om, $oids, $lang) {
        $result = [];
        $items = $om->read(__CLASS__, $oids, ['funding_id.booking_id']);

        foreach($items as $oid => $odata) {

            $result[$oid] = $odata['funding_id.booking_id'];
        }
        return $result;
    }


    /**
     * Signature for single object change from views.
     * 
     * @param array $event      Associative array holding changed fields as keys, and their related new values.
     * @param array $values     Copy of the current (partial) state of the object.
     * @return Array    Associative array mapping fields with their resulting values.
     */    
    public static function onchange($om, $event, $values, $lang) {
        $result = [];

        if(isset($event['funding_id'])) {
            $fundings = $om->read('sale\booking\Funding', $event['funding_id'], ['due_amount', 'booking_id.customer_id.id', 'booking_id.customer_id.name'], $lang);
            if($fundings > 0) {
                $funding = reset($fundings);
                $result['partner_id'] = [ 'id' => $funding['booking_id.customer_id.id'], 'name' => $funding['booking_id.customer_id.name'] ];
                $result['amount'] = $funding['due_amount'];
            }            
        }

        return $result;
    }    
}