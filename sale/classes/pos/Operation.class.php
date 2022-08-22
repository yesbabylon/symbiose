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
                'function'          => 'calcName',
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

            'session_id' => [
                'type'              => 'many2one',
                'foreign_object'    => CashdeskSession::getType(),
                'description'       => 'The cashdesk session the operation relates to.',
                'onupdate'          => 'onupdateSessionId',
                'required'          => true
            ],

            'cashdesk_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\pos\Cashdesk',
                'description'       => 'Cash desk the operations belongs to (from session).'
            ],

            'amount' => [
                'type'              => 'float',
                'usage'             => 'amount/money:2',
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


    public static function calcName($om, $ids, $lang) {
        $result = [];

        $operations = $om->read(get_called_class(), $ids, ['cashdesk_id.name', 'type', 'amount'], $lang);

        if($operations > 0) {
            foreach($operations as $oid => $operation) {
                $result[$oid] = $operation['cashdesk_id.name'].' ('.$operation['type'].') '.sprintf("%.2f", $operation['amount']);
            }
        }

        return $result;
    }

    /**
     * Update handler for field session_id.
     * Upon session assignment, synchronize the local cashdesk_id with the one from the session.
     *
     * @param  \equal\orm\ObjectManager    $om         ObjectManager service instance.
     * @param  array                       $oids       List of objects identifiers.
     * @param  array                       $values     Associative array holding the new values to be assigned.
     * @param  string                      $lang       Language in which multilang fields are being updated.
     * @return void
     */
    public static function onupdateSessionId($om, $oids, $values, $lang) {
        $operations = $om->read(self::getType(), $oids, ['session_id.cashdesk_id'], $lang);
        if($operations > 0) {
            foreach($operations as $oid => $operation) {
                $om->update(self::getType(), $oid, ['cashdesk_id' => $operation['session_id.cashdesk_id']], $lang);
            }
        }
    }

}