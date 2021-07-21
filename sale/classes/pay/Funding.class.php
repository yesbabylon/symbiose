<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace sale\pay;
use equal\orm\Model;

class Funding extends Model {

    public static function getColumns() {

        return [

            'payments_ids' => [ 
                'type'              => 'many2many',
                'foreign_object'    => 'sale\pay\Payment',
                'foreign_field'     => 'fundings_ids',
                'rel_table'         => 'sale_pay_rel_payment_funding',
                'rel_foreign_key'   => 'payment_id',
                'rel_local_key'     => 'funding_id'
            ],

            'type' => [
                'type'              => 'string',
                'selection'         => ['installment','invoice'],
                'description'       => "Deadlines are installment except for last one: final invoice."
            ],

            'due_amount' => [
                'type'              => 'float',
                'usage'             => 'amount/money',
                'description'       => 'Amount expected for the funding.'
            ],

            'due_date' => [
                'type'              => 'date',
                'description'       => "Deadline before which the funding is expected."
            ],

            'is_paid' => [
                'type'              => 'boolean',
                'default'           => false,
                'description'       => "Has the full payment been received?"
            ],

            'payment_deadline_id' => [
                'type'              => 'many2one',                
                'foreign_object'    => 'sale\pay\PaymentDeadline',
                'description'       => "The deadline model used for creating the funding, if any."
            ],

            'invoice_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'finance\accounting\Invoice',
                'description'       => 'The invoice targeted by the funding, if any.',
                'visible'           => [ ['type', '=', 'invoice'] ]
            ],            
        ];
    }

}