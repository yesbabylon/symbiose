<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace sale\accounting\invoice;

class InvoiceLineGroup extends \finance\accounting\InvoiceLineGroup {
    public static function getColumns() {
        return [
            'invoice_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\accounting\invoice\Invoice',
                'description'       => 'Invoice the line is related to.',
                'required'          => true,
                'ondelete'          => 'cascade'
            ]
        ];
    }
}