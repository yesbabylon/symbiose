<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace sale\pos;
use equal\orm\Model;

class Operation extends Model {

    public static function getColumns() {

        return [

            'name' => [
                'type'              => 'string',
                'description'       => "Short mnemo to identify the desk.",
                'required'          => true
            ],

            'user_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'identity\User',
                'description'       => 'User whom the operation originated.',
                'required'          => true
            ],

            'type' => [
                'type'              => 'string',
                'selection'         => [ 
                    'sale',           // operation is a sale
                    'move',           // operation is a cash movement (cash refill or cash out)
                ],
                'description'       => 'The kind of operation.'
            ],

            'cashdesk_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\pos\Cashdesk',
                'description'       => 'Cash desk the operations belongs to.',
                'required'          => true
            ],

            'total' => [
                'type'              => 'float',
                'required'          => true,
                'description'       => "Total amount of the sale."
            ],

            'amount_paid' => [
                'type'              => 'float',
                'default'           => 0.0
            ],

            'amount_returned' => [
                'type'              => 'float',
                'default'           => 0.0
            ],

            'payments_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'sale\pay\Payment',
                'foreign_field'     => 'operation_id',
                'description'       => 'The payments that are part of the operation.'
            ],            
        ];
    }

}