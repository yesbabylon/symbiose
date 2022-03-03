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

            /* owner organisation */
            'organisation_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'identity\Identity',
                'description'       => "The organisation the invoice belongs to.",
                'required'          => true
            ],

            'status' => [
                'type'              => 'string',
                'selection'         => ['proforma', 'invoice'],
                'default'           => 'proforma',
                'onchange'          => 'finance\accounting\Invoice::onchangeStatus',
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
                'default'           => false,
                'description'       => "Flag to mark the invoice as fully paid.",
                'visible'           => ['status', '=', 'invoice']
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

            /* the organisation the invoice relates to (multi-company support) */
            'organisation_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'identity\Identity',
                'description'       => "The organisation that emitted the invoice.",
                'default'           => 1
            ],

            'invoice_lines_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'finance\accounting\InvoiceLine',
                'foreign_field'     => 'invoice_id',
                'description'       => 'Detailed lines of the invoice.'
            ],

            'invoice_line_groups_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'finance\accounting\InvoiceLineGroup',
                'foreign_field'     => 'invoice_id',
                'description'       => 'Groups of lines of the invoice.'
            ]

        ];
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

    public static function onchangeStatus($om, $ids, $lang) {
        $om->write(__CLASS__, $ids, ['number' => null, 'date' => time()], $lang);
        $om->read(__CLASS__, $ids, ['number'], $lang);
    }

}