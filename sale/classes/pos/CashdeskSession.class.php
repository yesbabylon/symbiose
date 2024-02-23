<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace sale\pos;
use equal\orm\Model;

class CashdeskSession extends Model {

    public static function getColumns() {

        return [

            'name' => [
                'type'              => 'computed',
                'result_type'       => 'string',
                'function'          => 'calcName',
                'store'             => true
            ],

            'amount' => [
                'type'              => 'alias',
                'alias'             => 'amount_opening'
            ],

            'date_opening' => [
                'type'              => 'alias',
                'alias'             => 'created'
            ],

            'date_closing' => [
                'type'              => 'datetime',
                'description'       => "Date and time of the closing of the Session."
            ],

            'amount_opening' => [
                'type'              => 'float',
                'usage'             => 'amount/money:2',
                'description'       => "Amount of money in the cashdesk at the opening.",
                'required'          => true
            ],

            'amount_closing' => [
                'type'              => 'float',
                'usage'             => 'amount/money:2',
                'description'       => "Amount of money in the cashdesk at the closing.",
                'default'           => 0.0
            ],

            'note' => [
                'type'              => 'string',
                'usage'             => 'text/plain',
                'description'       => "Optional explanatory note given at the closing.",
                'default'           => ''
            ],

            'user_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'identity\User',
                'description'       => 'User whom performed the log entry.',
                'required'          => true
            ],

            'cashdesk_id' => [
                'type'              => 'many2one',
                'foreign_object'    => Cashdesk::getType(),
                'description'       => 'The cashdesk the session relates to.',
                'onupdate'          => 'onupdateCashdeskId',
                'required'          => true
            ],

            'status' => [
                'type'              => 'string',
                'selection'         => [
                    'pending',
                    'closed'
                ],
                'description'       => 'Current status of the session.',
                'onupdate'          => 'onupdateStatus',
                'default'           => 'pending'
            ],

            'orders_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'sale\pos\Order',
                'foreign_field'     => 'session_id',
                'description'       => 'The orders that relate to the session.'
            ],

            'operations_ids'  => [
                'type'              => 'one2many',
                'foreign_object'    => Operation::getType(),
                'foreign_field'     => 'session_id',
                'ondetach'          => 'delete',
                'description'       => 'List of operations performed during session.'
            ]

        ];
    }

    /**
     * Check for special constraint : only one session can be opened at a time on a given cashdesk.
     * Make sure there are no other pending sessions, otherwise, deny the update (which might be called on draft instance).
     */
    public static function cancreate($om, $values, $lang) {
        $res = $om->search(get_called_class(), [ ['status', '=', 'pending'], ['cashdesk_id', '=', $values['cashdesk_id']] ]);
        if($res > 0 && count($res)) {
            return ['status' => ['already_open' => 'There can be only one session at a time on a given cashdesk.']];
        }
        return parent::cancreate($om, $values, $lang);
    }


    /**
     * Check wether an object can be updated.
     * These tests come in addition to the unique constraints return by method `getUnique()`.
     * This method can be overriden to define a more precise set of tests.
     *
     * @param  \equal\orm\ObjectManager    $om         ObjectManager instance.
     * @param  array                       $oids       List of objects identifiers.
     * @param  array                       $values     Associative array holding the new values to be assigned.
     * @param  string                      $lang       Language in which multilang fields are being updated.
     * @return array    Returns an associative array mapping fields with their error messages. An empty array means that object has been successfully processed and can be updated.
     */
    public static function canupdate($om, $oids, $values, $lang) {
        $sessions = $om->read(__CLASS__, $oids, ['status'], $lang);

        if($sessions > 0) {
            foreach($sessions as $sid => $session) {
                if($session['status'] == 'closed') {
                    return ['status' => ['non_editable' => 'Closed session cannot be modified.']];
                }
            }
        }
        return parent::canupdate($om, $oids, $values, $lang);
    }

    /**
     * Create an 'opening' operation in the operations log.
     * Cashdesk assignement cannot be changed, so this handler is called once, when the session has just been created.
     *
     */
    public static function onupdateCashdeskId($om, $oids, $values, $lang) {
        $sessions = $om->read(__CLASS__, $oids, ['cashdesk_id', 'amount_opening', 'user_id'], $lang);

        if($sessions > 0) {
            foreach($sessions as $sid => $session) {
                $om->create(Operation::getType(), [
                    'cashdesk_id'   => $session['cashdesk_id'],
                    'session_id'    => $sid,
                    'user_id'       => $session['user_id'],
                    'amount'        => $session['amount_opening'],
                    'type'          => 'opening'
                ], $lang);
            }
        }
    }

    public static function onupdateStatus($om, $oids, $values, $lang) {
        // upon session closing, set date_closing and create additional operation if there is a delta in cash amount
        if(isset($values['status']) && $values['status'] == 'closed') {
            $sessions = $om->read(self::getType(), $oids, ['id', 'cashdesk_id', 'user_id', 'amount_opening', 'amount_closing', 'operations_ids.amount'], $lang);
            if($sessions > 0) {
                // set cashdesk session date_closing
                $om->update(self::getType(), $oids, ['date_closing' => time()], $lang);

                foreach($sessions as $sid => $session) {
                    $total_cash = 0.0;
                    foreach($session['operations_ids.amount'] as $oid => $operation) {
                        $total_cash += $operation['amount'];
                    }
                    // compute the difference (if any) between expected cash and actual cash in the cashdesk
                    $delta = $session['amount_closing'] - $total_cash;
                    if($delta != 0) {
                        // create a new move with the delta
                        $om->create(Operation::getType(), [
                            'cashdesk_id'   => $session['cashdesk_id'],
                            'session_id'    => $sid,
                            'user_id'       => $session['user_id'],
                            'amount'        => $delta,
                            'type'          => 'move',
                            'description'   => 'cashdesk closing'
                        ], $lang);
                    }
                }
            }
        }
    }

    public static function calcName($om, $ids, $lang) {
        $result = [];

        $sessions = $om->read(get_called_class(), $ids, ['cashdesk_id.name', 'user_id.name'], $lang);

        if($sessions > 0) {
            foreach($sessions as $sid => $session) {
                if(strlen($session['user_id.name']) || strlen($session['cashdesk_id.name'])) {
                    $result[$sid] = $session['user_id.name'].' - '.$session['cashdesk_id.name'];
                }
            }
        }

        return $result;
    }
}