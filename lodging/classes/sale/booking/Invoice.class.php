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
     * Handler for status change (which implies status has changed from 'proforma' to 'invoice').
     * This is mandatory since the way number is generated differs from the parent class method.
     * 
     * @param \equal\orm\ObjectManager  $om Instance of the objects manager.
     */
    public static function onupdateStatus($om, $ids, $values, $lang) {
        /*
            Generate an invoice number
        */
        $om->write(__CLASS__, $ids, ['number' => null, 'date' => time()], $lang);
        // immediate recompute invoice number
        $om->read(__CLASS__, $ids, ['number'], $lang);

        /*
            Generate the accounting entries
        */
        $invoices = $om->read(self::getType(), $ids, ['organisation_id', 'invoice_lines_ids'], $lang);
        if($invoices > 0) {
            foreach($invoices as $oid => $invoice) {
                // retrieve downpayment product
                $downpayment_product_id = 0;

                $downpayment_sku = Setting::get_value('sale', 'invoice', 'downpayment.sku.'.$invoice['organisation_id']);
                if($downpayment_sku) {
                    $products_ids = $om->search(\lodging\sale\catalog\Product::getType(), ['sku', '=', $downpayment_sku]);
                    if($products_ids) {
                        $downpayment_product_id = reset($products_ids);
                    }
                }




                $accounting_entries = [];
                // fetch invoice lines
                $lines = $om->read(\finance\accounting\InvoiceLine::getType(), $invoice['invoice_lines_ids'], ['product_id', 'total', 'price'], $lang);
                if($lines > 0) {
                    $vat_sum = 0.0;
                    $prices_sum = 0.0;
                    $downpayments_sum = 0.0;
                    foreach($lines as $lid => $line) {
                        // line refers to a downpayment
                        if($line['product_id'] == $downpayment_product_id) {
                            // sum up downpayments (VAT price)
                            $downpayments_sum += $line['price'];
                        }
                        // line is a regular product line
                        else {
                            // sum up VAT amounts
                            $vat_sum += $line['price'] - $line['total'];
                            // sum up sale prices vente (VTA price)
                            $prices_sum += $line['price'];

                        }
                        // créer une ligne de crédit avec le nom du produit, sur le compte de vente 70xxxxx (prix HTVA)                        
                        
                    }
                     // creer une ligne de crédit sur le compte 451 : taxes TVA à payer (somme des TVA) 
                     // une ligne de débit sur le compte 40000 : créances commerciales (sommes des prix de vente TVAC - somme des acomptes)
                }
            }
        }

    }
}