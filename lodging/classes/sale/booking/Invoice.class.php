<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace lodging\sale\booking;

class Invoice extends \lodging\finance\accounting\Invoice {

    public static function getLink() {
        return "/booking/#/booking/object.booking_id/invoice/object.id";
    }

    public static function getColumns() {

        return [

            'invoice_lines_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => InvoiceLine::getType(),
                'foreign_field'     => 'invoice_id',
                'description'       => 'Detailed lines of the invoice.',
                'ondetach'          => 'delete',
                'onupdate'          => 'onupdateInvoiceLinesIds'
            ],

            'booking_id' => [
                'type'              => 'many2one',
                'foreign_object'    => Booking::getType(),
                'description'       => 'Booking the invoice relates to.',
                'required'          => true
            ],

            'funding_id' => [
                'type'              => 'many2one',
                'foreign_object'    => Funding::getType(),
                'description'       => 'The funding the invoice originates from, if any.'
            ],

            // override to use booking_id in `calcPaymentReference`
            'payment_reference' => [
                'type'              => 'computed',
                'result_type'       => 'string',
                'function'          => 'calcPaymentReference',
                'description'       => 'Message for identifying payments related to the invoice.',
                'store'             => true
            ],

            'reversed_invoice_id' => [
                'type'              => 'many2one',
                'foreign_object'    => self::getType(),
                'description'       => "Credit note that was created for cancelling the invoice.",
                'visible'           => ['status', '=', 'cancelled']
            ]

        ];
    }

    public static function calcPaymentReference($om, $oids, $lang) {
        $result = [];
        $invoices = $om->read(get_called_class(), $oids, ['booking_id.name']);
        foreach($invoices as $oid => $invoice) {
            $booking_code = intval($invoice['booking_id.name']);
            // arbitrary value : 155 for final invoice
            $code_ref = 155;
            $result[$oid] = self::_get_payment_reference($code_ref, $booking_code);
        }
        return $result;
    }

}