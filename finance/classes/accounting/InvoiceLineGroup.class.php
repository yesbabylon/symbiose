<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace finance\accounting;
use equal\orm\Model;

class InvoiceLineGroup extends Model {

    public static function getName() {
        return "Invoice line group";
    }

    public static function getDescription() {
        return "Invoice line groups are related to an invoice and are meant to join several invoice lines.";
    }

    public static function getColumns() {
        return [
            'name' => [
                'type'              => 'string',
                'description'       => 'Label for the group.'
            ],

            'invoice_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'finance\accounting\Invoice',
                'description'       => 'Invoice the line is related to.',
                'required'          => true
            ],

        ];
    }

}