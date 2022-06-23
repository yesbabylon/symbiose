<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace lodging\sale\booking;

use core\setting\Setting;
class Invoice extends \sale\booking\Invoice {

    public static function getColumns() {

        return [

            'booking_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'lodging\sale\booking\Booking',
                'description'       => 'Booking the invoice relates to.',
                'required'          => true
            ],

            'funding_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'lodging\sale\booking\Funding',
                'description'       => 'The funding the invoice originates from, if any.'
            ],

            'center_office_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'lodging\identity\CenterOffice',
                'description'       => 'Office the invoice relates to (for center management).',
                'required'          => true
            ],

            'number' => [
                'type'              => 'computed',
                'function'          => 'calcNumber',
                'result_type'       => 'string',
                'store'             => true,
                'description'       => "Number of the invoice, according to organisation logic (@see config/invoicing)."
            ],

            'payment_reference' => [
                'type'              => 'computed',
                'result_type'       => 'string',
                'function'          => 'calcPaymentReference',
                'description'       => 'Message for identifying payments related to the invoice.',
                'store'             => true
            ],

            'status' => [
                'type'              => 'string',
                'selection'         => [
                    'proforma',
                    'invoice'
                ],
                'default'           => 'proforma',
                'onupdate'          => 'onupdateStatus',
            ]


        ];
    }

    public static function calcNumber($om, $oids, $lang) {
        $result = [];

        $invoices = $om->read(get_called_class(), $oids, ['status', 'organisation_id', 'center_office_id.code'], $lang);

        foreach($invoices as $oid => $invoice) {

            // no number is generated for proforma
            if($invoice['status'] == 'proforma') {
                $result[$oid] = '[proforma]';
            }
            else if($invoice['status'] == 'invoice') {
                $result[$oid] = '';

                $organisation_id = $invoice['organisation_id'];
                $format = Setting::get_value('finance', 'invoice', 'invoice.sequence_format', '%05d{sequence}');
                $year = Setting::get_value('finance', 'invoice', 'invoice.fiscal_year');
                $sequence = Setting::get_value('sale', 'invoice', 'invoice.sequence.'.$organisation_id);

                if($sequence) {
                    Setting::set_value('sale', 'invoice', 'invoice.sequence.'.$organisation_id, $sequence + 1);
                    $result[$oid] = Setting::parse_format($format, [
                        'year'      => $year,
                        'office'    => $invoice['center_office_id.code'],
                        'org'       => $organisation_id,
                        'sequence'  => $sequence
                    ]);
                }
            }
        }
        return $result;
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

    /**
     * This is mandatory since the way number is generated differs from the parent class method.
     */
    public static function onupdateStatus($om, $ids, $values, $lang) {
        $om->write(__CLASS__, $ids, ['number' => null, 'date' => time()], $lang);
        // immediate recompute
        $om->read(__CLASS__, $ids, ['number'], $lang);

        // #todo 
        //             'accounting_entries_ids' => [
    }
}