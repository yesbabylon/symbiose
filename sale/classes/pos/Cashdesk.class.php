<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace sale\pos;
use equal\orm\Model;

class Cashdesk extends Model {

    public static function getColumns() {

        return [
            'name' => [
                'type'              => 'string',
                'description'       => "Short mnemo to identify the cashdesk.",
                'required'          => true
            ],

            'establishment_id' => [
                'type'              => 'many2one',
                'foreign_object'    => \identity\Establishment::getType(),
                'description'       => "The center the desk relates to.",
                'required'          => true,
                'ondelete'          => 'cascade'         // delete cashdesk when parent Center is deleted
            ],

            'sessions_ids'  => [
                'type'              => 'one2many',
                'foreign_object'    => CashdeskSession::getType(),
                'foreign_field'     => 'cashdesk_id',
                'ondetach'          => 'delete',
                'description'       => 'List of sessions of the cashdesk.'
            ]
        ];
    }

}