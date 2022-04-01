<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace lodging\sale\pos;


class Cashdesk extends \sale\pos\Cashdesk {

    public static function getColumns() {

        return [
            'center_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'lodging\identity\Center',
                'description'       => "The center the desk relates to.",
                'required'          => true,
                'ondelete'          => 'cascade'         // delete cashdesk when parent Center is deleted
            ]

        ];
    }

}