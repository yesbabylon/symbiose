<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace lodging\sale\booking;

class InvoiceLine extends \finance\accounting\InvoiceLine {

    public static function getColumns() {
        return [
            
            'invoice_id' => [
                'type'              => 'many2one',
                'foreign_object'    => Invoice::getType(),
                'description'       => 'Invoice the line is related to.',
                'required'          => true,
                'ondelete'          => 'cascade'
            ],

            'invoice_line_group_id' => [
                'type'              => 'many2one',
                'foreign_object'    => InvoiceLineGroup::getType(),
                'description'       => 'Group the line relates to (in turn, groups relate to their invoice).',
                'ondelete'          => 'cascade'
            ],

            'unit_price' => [
                'type'              => 'float',
                'usage'             => 'amount/money:4',
                'description'       => 'Unit price of the product related to the line.'
            ],

            'price_id' => [
                'type'              => 'many2one',
                'foreign_object'    => \sale\price\Price::getType(),
                'description'       => 'The price the line relates to (assigned at line creation).'
            ],

            'vat_rate' => [
                'type'              => 'float',
                'usage'             => 'amount/rate',
                'description'       => 'VAT rate to be applied.',
                'default'           => 0.0
            ],

            'qty' => [
                'type'              => 'float',
                'description'       => 'Quantity of product.',
                'default'           => 0
            ],

            'free_qty' => [
                'type'              => 'integer',
                'description'       => 'Free quantity.',
                'default'           => 0
            ],

            'discount' => [
                'type'              => 'float',
                'description'       => 'Total amount of discount to apply, if any.',
                'default'           => 0.0
            ]

        ];
    }

}