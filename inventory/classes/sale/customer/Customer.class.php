<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

namespace inventory\sale\customer;

class Customer extends \sale\customer\Customer {


    public static function getColumns() {

        return [
            'products_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'inventory\Product',
                'foreign_field'     => 'customer_id',
                'description'       => 'List products of the customers.'
            ],

            'softwares_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'inventory\Software',
                'foreign_field'     => 'customer_id',
                'description'       => 'List softwares of the customers.'
            ],

            'services_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'inventory\service\Service',
                'foreign_field'     => 'customer_id',
                'description'       => 'List services of the customers.'
            ],

            'invoices_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'inventory\finance\accounting\Invoice',
                'foreign_field'     => 'customer_id',
                'description'       => 'List invoices of the customers.'
            ]
        ];
    }
}