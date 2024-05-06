<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace sale\accounting\invoice;
use core\setting\Setting;
use sale\customer\Customer;

class Invoice extends \finance\accounting\Invoice {

    public static function getName() {
        return "Sale invoice";
    }

    public static function getDescription() {
        return "A sale invoice is a legal document issued after some goods have been sold to a customer.";
    }

    public static function getColumns() {

        return [
            
            'invoice_number' => [
                'type'              => 'computed',
                'result_type'       => 'string',
                'description'       => "Number of the invoice, according to organization logic.",
                'function'          => 'calcInvoiceNumber',
                'store'             => true,
                'instant'           => true
            ],

            'organisation_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\identity\Organisation',
                'description'       => "The organization that emitted the invoice.",
                'default'           => 1
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
            
            'customer_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\customer\Customer',
                'description'       => "The counter party organization the invoice relates to.",
                'required'          => true,
                'dependencies'      => ['invoice_number']
            ],
        ];
    }

    public static function onchange($event,$values) {
        $result = [];
        if(isset($event['customer_id']) && isset($values['status']) && $values['status'] == 'proforma'){
            $customer = Customer::search(['id', '=', $event['customer_id']])
                ->read(['name'])
                ->first();
            $result['invoice_number']='[proforma]'. '['.$customer['name'].']'.'['.date('Y-m-d').']';
        }

        return $result;
    }

    public static function calcInvoiceNumber($self) {
        $result = [];
        $self->read(['status', 'organisation_id','customer_id'=> ['name']]);
        foreach($self as $id => $invoice) {

            // no code is generated for proforma
            if($invoice['status'] == 'proforma') {
                $result[$id] = '[proforma]'. '['.$invoice['customer_id']['name'].']'.'['.date('Y-m-d').']';
                continue;
            }

            $result[$id] = '';

            $organisation_id = $invoice['organisation_id'];

            $format = Setting::get_value('sale', 'invoice', 'sequence_format', '%2d{year}-%02d{org}-%05d{sequence}');
            $year = Setting::get_value('sale', 'invoice', 'fiscal_year', date('Y'));
            $sequence = Setting::get_value('sale', 'invoice', 'sequence.'.$organisation_id,1);
            
            if($sequence) {
                // #todo - user ORM fetchAndAdd()
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
}