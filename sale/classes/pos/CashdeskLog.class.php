<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace sale\pos;
use equal\orm\Model;

class CashdeskLog extends Model {

    public static function getColumns() {

        return [
            'date' => [
                'type'              => 'datetime',
                'description'       => "Short mnemo to identify the desk.",
                'required'          => true
            ],

            'amount' => [
                'type'              => 'float',
                'description'       => "Remaining amount of money at the end of the day.",
                'required'          => true
            ],

            'user_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'identity\User',
                'description'       => 'User whom performed the log entry.',
                'required'          => true
            ],

            'cashdesk_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\pos\Cashdesk',
                'description'       => 'Cash desk the log entry belongs to.',
                'required'          => true
            ],

        ];
    }

}