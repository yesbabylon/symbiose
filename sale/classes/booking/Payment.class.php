<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace sale\booking;

class Payment extends \sale\pay\Payment {

    public static function getColumns() {

        return [           

            'fundings_ids' => [ 
                'type'              => 'many2many',
                'foreign_object'    => 'sale\booking\Funding',
                'foreign_field'     => 'payments_ids',
                'rel_table'         => 'sale_pay_rel_payment_funding',
                'rel_foreign_key'   => 'funding_id',
                'rel_local_key'     => 'payment_id'
            ]            

        ];
    }

}