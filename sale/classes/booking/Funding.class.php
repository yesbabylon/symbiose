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

            'booking_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\booking\Booking',
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
        $fundings = $om->read(get_called_class(), $oids, ['booking_id.name', 'payment_deadline_id.name', 'due_amount'], $lang);

        if($fundings > 0) {
            foreach($fundings as $oid => $funding) {
                $result[$oid] = $funding['booking_id.name'].'    '.Setting::format_number_currency($funding['due_amount']).'    '.$funding['payment_deadline_id.name'];
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
            $result[$oid] = self::get_payment_reference($code_ref, $booking_code);
        }
        return $result;
    }

    /**
     * Compute a Structured Reference using belgian SCOR (StructuredCommunicationReference) reference format.
     *
     * Note:
     *  format is aaa-bbbbbbb-XX
     *  where Xaaa is the prefix, bbbbbbb is the suffix, and XX is the control number, that must verify (aaa * 10000000 + bbbbbbb) % 97
     *  as 10000000 % 97 = 76
     *  we do (aaa * 76 + bbbbbbb) % 97
     */
    public static function get_payment_reference($prefix, $suffix) {
        $a = intval($prefix);
        $b = intval($suffix);
        $control = ((76*$a) + $b ) % 97;
        $control = ($control == 0)?97:$control;
        return sprintf("%3d%04d%03d%02d", $a, $b / 1000, $b % 1000, $control);
    }

    public function getUnique() {
        return [
            ['booking_id', 'due_date']
        ];
    }

}