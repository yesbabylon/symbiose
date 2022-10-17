<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace yesbabylon\accounting;

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

            'product_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\catalog\Product',
                'description'       => 'The product (SKU) the line relates to.',
                'required'          => false
            ]
        ];
    }

}