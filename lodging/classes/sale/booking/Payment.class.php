<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace lodging\sale\booking;

class Payment extends \sale\booking\Payment {

    public static function getColumns() {

        return [

            'booking_id' => [
                'type'              => 'computed',
                'result_type'       => 'many2one',
                'function'          => 'getBookingId',
                'foreign_object'    => 'lodging\sale\booking\Booking',
                'description'       => 'The booking the payement relates to, if any (computed).',
                'store'             => true
            ],

            'funding_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'lodging\sale\booking\Funding',
                'description'       => 'The funding the payement relates to, if any.',
                'onupdate'          => 'sale\pay\Payment::onupdateFundingId'
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
     * @param  Object   $om        Object Manager instance.
     * @param  Array    $event      Associative array holding changed fields as keys, and their related new values.
     * @param  Array    $values     Copy of the current (partial) state of the object.
     * @param  String   $lang      Language (char 2) in which multilang field are to be processed.
     * @return Array    Associative array mapping fields with their resulting values.
     */
    public static function onchange($om, $event, $values, $lang=DEFAULT_LANG) {
        $result = [];

        if(isset($event['funding_id'])) {
            $fundings = $om->read('lodging\sale\booking\Funding', $event['funding_id'], ['type', 'due_amount', 'booking_id.customer_id.id', 'booking_id.customer_id.name', 'invoice_id.partner_id.id', 'invoice_id.partner_id.name'], $lang);
            if($fundings > 0) {
                $funding = reset($fundings);

                if($funding['type'] == 'invoice')  {
                    $result['partner_id'] = [ 'id' => $funding['invoice_id.partner_id.id'], 'name' => $funding['invoice_id.partner_id.name'] ];
                }
                else {
                    $result['partner_id'] = [ 'id' => $funding['booking_id.customer_id.id'], 'name' => $funding['booking_id.customer_id.name'] ];
                }

                if(isset($values['amount']) && $values['amount'] > $funding['due_amount']) {
                    $result['amount'] = $funding['due_amount'];
                }
            }
        }

        return $result;
    }


    public static function getConstraints() {
        return parent::getConstraints();
    }
}