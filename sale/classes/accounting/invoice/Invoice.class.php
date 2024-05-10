<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2024
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

namespace sale\accounting\invoice;

use core\setting\Setting;
use finance\accounting\Invoice as FinanceInvoice;
use sale\customer\Customer;

class Invoice extends FinanceInvoice {

    protected static $invoice_editable_fields = ['payment_status', 'customer_ref'];

    public static function getName() {
        return 'Sale invoice';
    }

    public static function getDescription() {
        return 'A sale invoice is a legal document issued after some goods have been sold to a customer.';
    }

    public static function getColumns() {

        return [

            /**
             * Override Finance Invoice columns
             */

            'organisation_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\identity\Organisation',
                'description'       => 'The organization that emitted the invoice.',
                'default'           => 1
            ],

            'invoice_purpose' => [
                'type'              => 'string',
                'default'           => 'sell',
                'visible'           => false
            ],

            'invoice_number' => [
                'type'              => 'computed',
                'result_type'       => 'string',
                'description'       => 'Number of the invoice, according to organization logic.',
                'function'          => 'calcInvoiceNumber',
                'store'             => true,
                'instant'           => true
            ],

            'payment_reference' => [
                'type'              => 'computed',
                'result_type'       => 'string',
                'function'          => 'calcPaymentReference',
                'description'       => 'Message for identifying payments related to the invoice.',
                'store'             => true,
                'instant'           => true
            ],

            'due_date' => [
                'type'              => 'computed',
                'result_type'       => 'date',
                'description'       => 'Deadline for the payment is expected, from payment terms.',
                'function'          => 'calcDueDate',
                'store'             => true,
                'instant'           => true
            ],

            'invoice_lines_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'sale\accounting\invoice\InvoiceLine',
                'foreign_field'     => 'invoice_id',
                'description'       => 'Detailed lines of the invoice.',
                'ondetach'          => 'delete',
                'dependencies'      => ['total', 'price']
            ],

            'invoice_line_groups_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'sale\accounting\invoice\InvoiceLineGroup',
                'foreign_field'     => 'invoice_id',
                'description'       => 'Groups of lines of the invoice.',
                'ondetach'          => 'delete',
                'dependencies'      => ['total', 'price']
            ],

            /**
             * Specific Sale Invoice columns
             */

            'customer_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\customer\Customer',
                'description'       => 'The counter party organization the invoice relates to.',
                'required'          => true,
                'dependencies'      => ['invoice_number']
            ],

            'customer_ref' => [
                'type'              => 'string',
                'description'       => 'Reference that must appear on invoice (requested by customer).'
            ],

            'is_deposit' => [
                'type'              => 'boolean',
                'description'       => 'Marks the invoice as a deposit one, relating to a downpayment.',
                'default'           => false
            ],

            'payment_terms_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\pay\PaymentTerms',
                'description'       => 'The payment terms to apply to the invoice.',
                'default'           => 1
            ],

            'fundings_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'sale\pay\Funding',
                'foreign_field'     => 'invoice_id',
                'description'       => 'Fundings related to the invoice (should be max. 1).'
            ]
        ];
    }

    public static function onchange($event, $values): array {
        $result = [];
        if(isset($event['customer_id'], $values['status']) && $values['status'] == 'proforma'){
            $customer = Customer::search(['id', '=', $event['customer_id']])
                ->read(['name'])
                ->first();

            $result['invoice_number'] = '[proforma]['.$customer['name'].']['.date('Y-m-d').']';
        }

        return $result;
    }

    public static function calcInvoiceNumber($self): array {
        $result = [];
        $self->read(['status', 'organisation_id', 'customer_id' => ['name']]);
        foreach($self as $id => $invoice) {

            // no code is generated for proforma
            if($invoice['status'] == 'proforma') {
                $result[$id] = '[proforma]['.$invoice['customer_id']['name'].']['.date('Y-m-d').']';
                continue;
            }

            $result[$id] = '';

            $organisation_id = $invoice['organisation_id'];

            $format = Setting::get_value('sale', 'invoice', 'sequence_format', '%2d{year}-%02d{org}-%05d{sequence}');
            $year = Setting::get_value('sale', 'invoice', 'fiscal_year', date('Y'));
            $sequence = Setting::get_value('sale', 'invoice', 'sequence.'.$organisation_id,1);

            if($sequence) {
                Setting::set_value('sale', 'invoice', 'sequence.'.$organisation_id, $sequence + 1);
                $result[$id] = Setting::parse_format($format, [
                    'year'      => $year,
                    'org'       => $organisation_id,
                    'sequence'  => $sequence
                ]);
            }
        }

        return $result;
    }

    public static function calcPaymentReference($self): array {
        $result = [];
        $self->read(['invoice_number']);
        foreach($self as $id => $invoice) {
            $invoice_number = intval($invoice['invoice_number']);

            // arbitrary value for balance (final) invoice
            $code_ref = 200;

            $result[$id] = self::_get_payment_reference($code_ref, $invoice_number);
        }

        return $result;
    }

    public static function calcDueDate($self): array {
        $result = [];
        $self->read(['created', 'payment_terms_id' => ['delay_from', 'delay_count']]);
        foreach($self as $id => $invoice) {
            if(!isset($invoice['payment_terms_id']['delay_from'], $invoice['payment_terms_id']['delay_count'])) {
                continue;
            }

            $from = $invoice['payment_terms_id']['delay_from'];
            $delay = $invoice['payment_terms_id']['delay_count'];
            $created = $invoice['created'];

            switch($from) {
                case 'created':
                    $due_date = $created + ($delay*24*3600);
                    break;
                case 'next_month':
                default:
                    $due_date = strtotime(date('Y-m-t', $created)) + ($delay * 24 * 3600);
                    break;
            }

            $result[$id] = $due_date;
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
    protected static function _get_payment_reference($prefix, $suffix) {
        $a = intval($prefix);
        $b = intval($suffix);
        $control = ((76*$a) + $b ) % 97;
        $control = ($control == 0)?97:$control;
        return sprintf("%3d%04d%03d%02d", $a, $b / 1000, $b % 1000, $control);
    }
}