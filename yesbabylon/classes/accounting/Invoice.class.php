<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace yesbabylon\accounting;
use core\setting\Setting;
use yesbabylon\service\Service;

class Invoice extends \finance\accounting\Invoice {

    public static function getLink() {
        return "/accounting/#/invoice/object.id";
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

            'service_id' => [
                'type'              => 'many2one',
                'foreign_object'    => Service::getType(),
                'description'       => 'Service the invoice relates to.',
                'required'          => true
            ],

        ];
    }

    public static function onupdateInvoiceLinesIds($om, $oids, $values, $lang) {
        $om->update(__CLASS__, $oids, ['price' => null, 'total' => null]);
    }


    // public static function calcNumber($om, $oids, $lang) {
    //     $result = [];

    //     $invoices = $om->read(self::getType(), $oids, ['status', 'organisation_id', 'center_office_id.code'], $lang);

    //     foreach($invoices as $oid => $invoice) {

    //         // no code is generated for proforma
    //         if($invoice['status'] == 'proforma') {
    //             $result[$oid] = '[proforma]';
    //             continue;
    //         }

    //         $organisation_id = $invoice['organisation_id'];
    //         $format = Setting::get_value('finance', 'invoice', 'invoice.sequence_format', '%05d{sequence}');
    //         $year = Setting::get_value('finance', 'invoice', 'invoice.fiscal_year');
    //         $sequence = Setting::get_value('sale', 'invoice', 'invoice.sequence.'.$organisation_id);

    //         if($sequence) {
    //             Setting::set_value('sale', 'invoice', 'invoice.sequence.'.$organisation_id, $sequence + 1);

    //             $result[$oid] = Setting::parse_format($format, [
    //                 'year'      => $year,
    //                 'office'    => $invoice['center_office_id.code'],
    //                 'org'       => $organisation_id,
    //                 'sequence'  => $sequence
    //             ]);
    //         }
    //     }
    //     return $result;
    // }

    // public static function onupdateStatus($om, $oids, $values, $lang) {
    //     if(isset($values['status']) && $values['status'] == 'invoice') {
    //         // reset invoice number and set emission date
    //         $om->update(self::getType(), $oids, ['number' => null, 'date' => time()], $lang);

    //         $invoices = $om->read(self::getType(), $oids, [
    //             // #memo - generate an invoice number (force immediate recomputing)
    //             'number',
    //             'center_office_id'
    //         ], $lang);

    //         // generate accounting entries
    //         $invoices_accounting_entries = self::_generateAccountingEntries($om, $oids, [], $lang);

    //         foreach($invoices as $oid => $invoice) {

    //             $res = $om->search(AccountingJournal::getType(), [['center_office_id', '=', $invoice['center_office_id']], ['type', '=', 'sales']]);
    //             $journal_id = reset($res);

    //             if($journal_id && isset($invoices_accounting_entries[$oid])) {
    //                 $accounting_entries = $invoices_accounting_entries[$oid];
    //                 // create new entries objects and assign to the sale journal relating to the center_office_id
    //                 foreach($accounting_entries as $entry) {
    //                     $entry['journal_id'] = $journal_id;
    //                     $om->create(\finance\accounting\AccountingEntry::getType(), $entry);
    //                 }
    //             }
    //         }

    //     }
    // }
}