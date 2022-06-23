<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace lodging\sale\pos;

class CashdeskSession extends \sale\pos\CashdeskSession {

    public static function getColumns() {

        return [

            'user_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'lodging\identity\User',
                'description'       => 'User whom performed the log entry.',
                'required'          => true
            ],

            'cashdesk_id' => [
                'type'              => 'many2one',
                'foreign_object'    => \lodging\sale\pos\Cashdesk::getType(),
                'description'       => 'Cash desk the log entry belongs to.',
                'required'          => true,
                'onupdate'          => 'onupdateCashdeskId'
            ],

            'orders_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'lodging\sale\pos\Order',
                'foreign_field'     => 'session_id',
                'description'       => 'The orders that relate to the session.'
            ],

            'center_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'lodging\identity\Center',
                'description'       => "The center the desk relates to."
            ]


        ];
    }

    public static function onupdateCashdeskId($om, $oids, $values, $lang) {
        $sessions = $om->read(__CLASS__, $oids, ['cashdesk_id.center_id'], $lang);

        if($sessions > 0) {
            foreach($sessions as $sid => $session) {
                $om->write(__CLASS__,  $sid, ['center_id' => $session['cashdesk_id.center_id']], $lang);
            }
        }
        parent::onupdateCashdeskId($om, $oids, $values, $lang);
    }

}