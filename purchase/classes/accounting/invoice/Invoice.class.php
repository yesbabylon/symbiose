<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace purchase\accounting\invoice;

class Invoice extends \finance\accounting\Invoice {

    public static function getName() {
        return "Purchase invoice";
    }

    public static function getDescription() {
        return "A purchase invoice is a legal document issued after some goods have been bought from a supplier.";
    }

    public static function getColumns() {
        return [
            'invoice_lines_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'purchase\accounting\invoice\InvoiceLine',
                'foreign_field'     => 'invoice_id',
                'description'       => 'Detailed lines of the invoice.',
                'ondetach'          => 'delete',
                'dependencies'      => ['total', 'price']
            ],

            'invoice_line_groups_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'purchase\accounting\invoice\InvoiceLineGroup',
                'foreign_field'     => 'invoice_id',
                'description'       => 'Groups of lines of the invoice.',
                'ondetach'          => 'delete',
                'dependencies'      => ['total', 'price']
            ],

            'supplier_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'purchase\supplier\Supplier',
                'description'       => "The supplier the invoice relates to.",
                'required'          => true,
            ]
        ];
    }
}
