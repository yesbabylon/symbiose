<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace sale\booking;

class Funding extends \sale\pay\Funding {

    public static function getColumns() {

        return [

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

            'payment_reference' => [
                'type'              => 'computed',
                'result_type'       => 'string',
                'function'          => 'sale\booking\Funding::getPaymentReference',
                'description'       => 'Message for identifying the purpose of the transaction.',
                'store'             => true
            ],

            'payments_ids' => [ 
                'type'              => 'many2many',
                'foreign_object'    => 'sale\booking\Payment',
                'foreign_field'     => 'fundings_ids',
                'rel_table'         => 'sale_pay_rel_payment_funding',
                'rel_foreign_key'   => 'payment_id',
                'rel_local_key'     => 'funding_id'
            ]            

        ];
    }

    public static function getPaymentReference($om, $oids, $lang) {
        $result = [];
        $fundings = $om->read(get_called_class(), $oids, ['booking_id.name', 'type', 'order']);
        foreach($fundings as $oid => $funding) {
            $booking_code = intval($funding['booking_id.name']);
            $code_ref = 150;                    // '+++150/+++' for initial installment
            if($funding['order']) {
                $code_ref += $funding['order']; 
            }
            $control = ((76*$code_ref) + $booking_code ) % 97;
            $control = ($control == 0)?97:$control;            
            $result[$oid] = sprintf("%3d%04d%03d%02d", $code_ref, $booking_code / 1000, $booking_code % 1000, $control);
        }
        return $result;
    }
    
    public function getUnique() {
        return [
            ['payment_deadline_id', 'booking_id']
        ];
    }        

}