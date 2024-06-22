<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2024
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace sale\order;

use sale\accounting\invoice\Invoice as SaleInvoice;

class Invoice extends SaleInvoice {

    public static function getColumns() {

        return [

            'order_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\order\Order',
                'description'       => 'Order the invoice relates to.',
                'required'          => true
            ],

            'funding_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\order\Funding',
                'description'       => 'The funding the invoice originates from, if any.'
            ]

        ];
    }

}