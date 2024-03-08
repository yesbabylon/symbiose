<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace purchase\supplier;

class Supplier extends \identity\Partner {

    public function getTable() {
        return 'purchase_supplier_supplier';
    }

    public static function getName() {
        return 'Supplier';
    }

    public static function getDescription() {
        return "A supplier is a company from which the organisation buys goods and services.";
    }

    public static function getColumns() {

        return [

            'relationship' => [
                'type'              => 'string',
                'default'           => 'supplier',
                'description'       => 'Force relationship to Supplier'
            ],

            'invoices_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'purchase\accounting\Invoice',
                'foreign_field'     => 'customer_id',
                'description'       => 'Purchase invoices from the supplier.'
            ]

        ];
    }

}
