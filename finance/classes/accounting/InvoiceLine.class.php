<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace finance\accounting;
use equal\orm\Model;

class InvoiceLine extends Model {

    public static function getName() {
        return "Invoice line";
    }

    public static function getDescription() {
        return "Invoice lines describe the products and quantities that are part of an invoice.";
    }

    public static function getColumns() {
        return [

            'invoice_line_group_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'finance\accounting\InvoiceLineGroup',
                'description'       => 'Group the line relates to (in turn, groups relate to their invoice).',
                'required'          => true
            ],

            'invoice_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'finance\accounting\Invoice',
                'description'       => 'Invoice the line is related to.',
                'required'          => true
            ],

            'product_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\catalog\Product',
                'description'       => 'The product (SKU) the line relates to.',
                'required'          => true
            ],

            'price' => [ 
                'type'              => 'float', 
                'description'       => 'Price of the product related to the line.'
            ],

            'vat_rate' => [ 
                'type'              => 'float', 
                'description'       => 'VAT rate to be applied.'
            ],
            
            'qty' => [ 
                'type'              => 'float', 
                'description'       => 'Quantity of product.'
            ],

            'free_qty' => [ 
                'type'              => 'integer', 
                'description'       => 'Free quantity.'
            ],

            'discount' => [ 
                'type'              => 'float', 
                'description'       => 'Total amount of discount to apply, if any.',
                'default'           => 0.0
            ],

         
        ];
    }

}