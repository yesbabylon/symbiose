<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace purchase\accounting\invoice;

class InvoiceLine extends \finance\accounting\InvoiceLine {

    public static function getColumns() {
        return [
            'invoice_line_group_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'purchase\accounting\invoice\InvoiceLineGroup',
                'description'       => 'Group the line relates to (in turn, groups relate to their invoice).',
                'ondelete'          => 'cascade'
            ],

            'invoice_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'purchase\accounting\invoice\Invoice',
                'description'       => 'Invoice the line is related to.',
                'required'          => true,
                'onupdate'          => 'onupdateInvoiceId',
                'ondelete'          => 'cascade'
            ]
        ];
    }
}
