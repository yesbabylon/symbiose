<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace finance\accounting;
use equal\orm\Model;
use core\setting\Setting;

class Invoice extends Model {

    public static function getName() {
        return "Invoice";
    }

    public static function getDescription() {
        return "An invoice is a legal document issued by a seller to a buyer that relates to a sale, and is part of the accounting system.";
    }

    public static function getColumns() {

        return [
            'name' => [
                'type'              => 'alias',
                'alias'             => "number"
            ],

           /* the (owner) organisation the invoice relates to (multi-company support) */
            'organisation_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'identity\Identity',
                'description'       => "The organisation that emitted the invoice.",
                'default'           => 1
            ],            

            'status' => [
                'type'              => 'string',
                'selection'         => [
                    'proforma', 
                    'invoice'
                ],
                'default'           => 'proforma',
                'onchange'          => 'onchangeStatus',
            ],

            'type' => [
                'type'              => 'string',
                'selection'         => ['invoice', 'credit_note'],
                'default'           => 'invoice'
            ],

            'number' => [
                'type'              => 'computed',
                'function'          => 'finance\accounting\Invoice::getNumber',
                'result_type'       => 'string',
                'store'             => true,
                'description'       => "Number of the invoice, according to organisation logic (@see config/invoicing)."
            ],

            'is_paid' => [
                'type'              => 'boolean',
                'description'       => "Flag to mark the invoice as fully paid.",
                'visible'           => ['status', '=', 'invoice'],
                'default'           => false
            ],

            'payment_status' => [
                'type'              => 'string',
                'selection'         => [
                    'pending',          // non-paid, payment terms delay running
                    'overdue',          // non-paid, and payment terms delay is over
                    'debit_balance',    // partially paid: customer still has to pay something
                    'credit_balance',   // fully paid and a reimbusrsement to customer is required
                    'balanced'          // fully paid and balanced
                ],
                'visible'           => ['status', '=', 'invoice'],
                'default'           => 'pending'
            ],

            'payment_reference' => [
                'type'              => 'computed',
                'result_type'       => 'string',
                'function'          => 'calcPaymentReference',
                'description'       => 'Message for identifying payments related to the invoice.',
                'store'             => true
            ],

            'date' => [
                'type'              => 'datetime',
                'description'       => 'Emission date of the invoice.',
                'default'           => time()
            ],

            'partner_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'identity\Partner',
                'description'       => "Organisation which has to pay for the goods and services related to the sale.",
                'required'          => true
            ],

            'price' => [
                'type'              => 'computed',
                'result_type'       => 'float',
                'function'          => 'finance\accounting\Invoice::getPrice',
                'usage'             => 'amount/money:2',
                'store'             => true,
                'description'       => "Final tax-included invoiced amount (computed)."
            ],

            'total' => [
                'type'              => 'computed',
                'result_type'       => 'float',
                'function'          => 'finance\accounting\Invoice::getTotal',
                'usage'             => 'amount/money:4',
                'description'       => 'Total tax-excluded price of the invoice (computed).',
                'store'             => true
            ],

            'invoice_lines_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'finance\accounting\InvoiceLine',
                'foreign_field'     => 'invoice_id',
                'description'       => 'Detailed lines of the invoice.',
                'ondetach'          => 'delete'
            ],

            'invoice_line_groups_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'finance\accounting\InvoiceLineGroup',
                'foreign_field'     => 'invoice_id',
                'description'       => 'Groups of lines of the invoice.',
                'ondetach'          => 'delete'
            ],

            'payment_terms_id' => [
                'type'              => 'many2one',                
                'foreign_object'    => 'sale\pay\PaymentTerms',
                'description'       => "The payment terms to apply to the invoice."
            ],

            'due_date' => [
                'type'              => 'computed',
                'result_type'       => 'date',
                'description'       => "Deadline before which the funding is expected.",
                'function'          => 'calcDueDate',
                'store'             => true
            ],


        ];
    }


    public static function calcPaymentReference($om, $oids, $lang) {
        $result = [];
        $invoices = $om->read(get_called_class(), $oids, ['number']);
        foreach($invoices as $oid => $invoice) {
            $booking_code = intval($invoice['number']);
            // arbitrary value : 155 for final invoice
            $code_ref = 155;
            $result[$oid] = self::_get_payment_reference($code_ref, $booking_code);
        }
        return $result;
    }    

    public static function getNumber($om, $oids, $lang) {
        $result = [];

        $invoices = $om->read(get_called_class(), $oids, ['status', 'organisation_id'], $lang);

        foreach($invoices as $oid => $invoice) {

            // no code is generated for proforma
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
                        'org'       => $organisation_id,
                        'sequence'  => $sequence
                    ]);
                }
            }
        }
        return $result;
    }

    public static function getPrice($om, $oids, $lang) {
        $result = [];

        $invoices = $om->read(get_called_class(), $oids, ['invoice_lines_ids.price'], $lang);

        foreach($invoices as $oid => $invoice) {
            $result[$oid] = array_reduce($invoice['invoice_lines_ids.price'], function ($c, $a) {
                return $c + $a['price'];
            }, 0.0);
        }
        return $result;
    }

    public static function getTotal($om, $oids, $lang) {
        $result = [];

        $invoices = $om->read(get_called_class(), $oids, ['invoice_lines_ids.total'], $lang);

        foreach($invoices as $oid => $invoice) {
            $result[$oid] = array_reduce($invoice['invoice_lines_ids.total'], function ($c, $a) {
                return $c + $a['total'];
            }, 0.0);
        }
        return $result;
    }

    public static function calcDueDate($om, $oids, $lang) {
        $result = [];

        $invoices = $om->read(get_called_class(), $oids, ['created', 'payment_terms_id.delay_from', 'payment_terms_id.delay_count'], $lang);
        if($invoices > 0) {
            foreach($invoices as $oid => $invoice) {
                $from = $invoice['payment_terms_id.delay_from'];
                $delay = $invoice['payment_terms_id.delay_count'];
                $origin = $invoice['created'];                
                switch($from) {
                    case 'created':                        
                        $due_date = $origin + ($delay*24*3600);
                        break;
                    case 'next_month':
                    default:
                        $due_date = strtotime(date("Y-m-t", $origin)) + ($delay*24*3600);
                        break;
                }
                $result[$oid] = $due_date;
            }
        }
        return $result;
    }

    public static function onchangeStatus($om, $ids, $lang) {
        $om->write(__CLASS__, $ids, ['number' => null, 'date' => time()], $lang);
        // immediate recompute
        $om->read(__CLASS__, $ids, ['number'], $lang);
    }


    /**
     * Check wether an object can be updated, and perform some additional operations if necessary.
     * This method can be overriden to define a more precise set of tests.
     *
     * @param  object   $om         ObjectManager instance.
     * @param  array    $oids       List of objects identifiers.
     * @param  array    $values     Associative array holding the new values to be assigned.
     * @param  string   $lang       Language in which multilang fields are being updated.
     * @return array    Returns an associative array mapping fields with their error messages. En empty array means that object has been successfully processed and can be updated.
     */
    public static function onupdate($om, $oids, $values, $lang=DEFAULT_LANG) {
        $res = $om->read(get_called_class(), $oids, [ 'status' ]);

        if($res > 0) {
            foreach($res as $oids => $odata) {
                if($odata['status'] != 'proforma') {
                    return ['status' => ['non_editable' => 'Invoice can only be updated while its status is proforma.']];
                }
            }
        }
        return parent::onupdate($om, $oids, $values, $lang);
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
    public static function _get_payment_reference($prefix, $suffix) {
        $a = intval($prefix);
        $b = intval($suffix);
        $control = ((76*$a) + $b ) % 97;
        $control = ($control == 0)?97:$control;
        return sprintf("%3d%04d%03d%02d", $a, $b / 1000, $b % 1000, $control);
    }
}