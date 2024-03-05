<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace sale\booking;
use core\setting\Setting;

class Funding extends \sale\pay\Funding {

    public static function getColumns() {

        return [

            'name' => [
                'type'              => 'computed',
                'result_type'       => 'string',
                'function'          => 'calcName',
                'store'             => true
            ],

            'due_amount' => [
                'type'              => 'float',
                'usage'             => 'amount/money:2',
                'description'       => 'Amount expected for the funding (computed based on VAT incl. price).',
                'required'          => true,
                'onupdate'          => 'onupdateDueAmount'
            ],

            'amount_share' => [
                'type'              => 'computed',
                'result_type'       => 'float',
                'usage'             => 'amount/percent',
                'function'          => 'calcAmountShare',
                'store'             => true,
                'description'       => "Share of the payment over the total due amount (booking)."
            ],

            'booking_id' => [
                'type'              => 'many2one',
                'foreign_object'    => Booking::getType(),
                'description'       => 'Booking the contract relates to.',
                'ondelete'          => 'cascade',        // delete funding when parent booking is deleted
                'required'          => true
            ],

            'order' => [
                'type'              => 'integer',
                'description'       => 'Order by which the funding have to be sorted when presented.',
                'default'           => 0
            ],

            'invoice_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\booking\Invoice',
                'description'       => 'The invoice targeted by the funding, if any.',
                'visible'           => [ ['type', '=', 'invoice'] ]
            ],

            'payment_reference' => [
                'type'              => 'computed',
                'result_type'       => 'string',
                'function'          => 'calcPaymentReference',
                'description'       => 'Message for identifying the purpose of the transaction.',
                'store'             => true
            ],

            'payments_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'sale\booking\Payment',
                'foreign_field'     => 'funding_id'
            ]

        ];
    }


    public static function calcName($om, $oids, $lang) {
        $result = [];
        $fundings = $om->read(get_called_class(), $oids, ['booking_id.name', 'due_amount'], $lang);

        if($fundings > 0) {
            foreach($fundings as $oid => $funding) {
                $result[$oid] = $funding['booking_id.name'].'    '.Setting::format_number_currency($funding['due_amount']);
            }
        }
        return $result;
    }

    public static function calcAmountShare($om, $oids, $lang) {
        $result = [];
        $fundings = $om->read(self::getType(), $oids, ['booking_id.price', 'due_amount'], $lang);

        if($fundings > 0) {
            foreach($fundings as $oid => $funding) {
                $total = $funding['booking_id.price'];
                $share = ($total != 0)?round($funding['due_amount'] / $total, 2):0;
                $result[$oid] = min(1.0, $share);
            }
        }

        return $result;
    }

    public static function calcPaymentReference($om, $oids, $lang) {
        $result = [];
        $fundings = $om->read(get_called_class(), $oids, ['booking_id.name', 'type', 'order', 'payment_deadline_id.code'], $lang);
        foreach($fundings as $oid => $funding) {
            $booking_code = intval($funding['booking_id.name']);
            if($funding['payment_deadline_id.code']) {
                $code_ref = intval($funding['payment_deadline_id.code']);
            }
            else {
                // arbitrary value : 151 for first funding, 152 for second funding, ...
                $code_ref = 150;
                if($funding['order']) {
                    $code_ref += $funding['order'];
                }
            }
            $result[$oid] = self::_get_payment_reference($code_ref, $booking_code);
        }
        return $result;
    }

    public static function onupdateDueAmount($orm, $oids, $values, $lang) {
        $orm->update(self::getType(), $oids, ['name' => null, 'amount_share' => null], $lang);
    }

    /**
     * Check wether an object can be created.
     * These tests come in addition to the unique constraints returned by method `getUnique()`.
     * Checks wheter the sum of the fundings of a booking remains lower than the price of the booking itself.
     *
     * @param  \equal\orm\ObjectManager     $om         ObjectManager instance.
     * @param  array                        $values     Associative array holding the values to be assigned to the new instance (not all fields might be set).
     * @param  string                       $lang       Language in which multilang fields are being updated.
     * @return array            Returns an associative array mapping fields with their error messages. An empty array means that object has been successfully processed and can be created.
     */
    public static function cancreate($om, $values, $lang) {
        if(isset($values['booking_id']) && isset($values['due_amount'])) {
            $bookings = $om->read(Booking::getType(), $values['booking_id'], ['price', 'fundings_ids.due_amount'], $lang);
            if($bookings > 0 && count($bookings)) {
                $booking = reset($bookings);
                $fundings_price = (float) $values['due_amount'];
                foreach($booking['fundings_ids.due_amount'] as $fid => $funding) {
                    $fundings_price += (float) $funding['due_amount'];
                }
                if($fundings_price > $booking['price'] && abs($booking['price']-$fundings_price) >= 0.0001) {
                    return ['status' => ['exceded_price' => 'Sum of the fundings cannot be higher than the booking total.']];
                }
            }
        }
        return parent::cancreate($om, $values, $lang);
    }


    /**
     * Check wether an object can be updated.
     * These tests come in addition to the unique constraints returned by method `getUnique()`.
     * Checks wheter the sum of the fundings of each booking remains lower than the price of the booking itself.
     *
     * @param  \equal\orm\ObjectManager     $om         ObjectManager instance.
     * @param  array                        $oids       List of objects identifiers.
     * @param  array                        $values     Associative array holding the new values to be assigned.
     * @param  string                       $lang       Language in which multilang fields are being updated.
     * @return array            Returns an associative array mapping fields with their error messages. An empty array means that object has been successfully processed and can be updated.
     */
    public static function canupdate($om, $oids, $values, $lang) {
        if(isset($values['due_amount'])) {
            $fundings = $om->read(self::getType(), $oids, ['booking_id'], $lang);

            if($fundings > 0) {
                foreach($fundings as $fid => $funding) {
                    $bookings = $om->read(Booking::getType(), $funding['booking_id'], ['price', 'fundings_ids.due_amount'], $lang);
                    if($bookings > 0 && count($bookings)) {
                        $booking = reset($bookings);
                        $fundings_price = (float) $values['due_amount'];
                        foreach($booking['fundings_ids.due_amount'] as $oid => $odata) {
                            if($oid != $fid) {
                                $fundings_price += (float) $odata['due_amount'];
                            }
                        }
                        if($fundings_price > $booking['price'] && abs($booking['price']-$fundings_price) >= 0.0001) {
                            return ['status' => ['exceded_price' => "Sum of the fundings cannot be higher than the booking total."]];
                        }
                    }
                }
            }
        }
        return parent::canupdate($om, $oids, $values, $lang);
    }
}