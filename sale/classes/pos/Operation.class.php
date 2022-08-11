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
                'type'              => 'computed',
                'result_type'       => 'string',
                'function'          => 'getDisplayName',
                'store'             => true
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
                    'opening',        // operation is a session opening
                    'sale',           // operation is a sale
                    'move'            // operation is a cash movement (cash in or cash out)
                ],
                'description'       => 'The kind of operation.'
            ],

            'cashdesk_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\pos\Cashdesk',
                'description'       => 'Cash desk the operations belongs to.',
                'required'          => true
            ],

            'session_id' => [
                'type'              => 'many2one',
                'foreign_object'    => CashdeskSession::getType(),
                'description'       => 'The cashdesk session the operation relates to.',
                'required'          => true
            ],            

            'amount' => [
                'type'              => 'float',
                'usage'             => 'amount/money',
                'description'       => 'Amount of the operation (cash in for positive amount, cash out otherwise).',    
                'default'           => 0.0
            ],

            'description' => [
                'type'              => 'string',
                'usage'             => 'text/plain',
                'description'       => 'Reason of the movement.'
            ]

        ];
    }


    public static function getDisplayName($om, $ids, $lang) {
        $result = [];

        $operations = $om->read(get_called_class(), $ids, ['cashdesk_id.name', 'type', 'amount'], $lang);

        if($operations > 0) {
            foreach($operations as $oid => $operation) {
                $result[$oid] = $operation['cashdesk_id.name'].' ('.$operation['type'].') - '.sprintf("%.2f", $operation['amount']);
            }
        }

        return $result;
    }    

}