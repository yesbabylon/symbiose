<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2024
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

namespace communication;

use equal\orm\Model;

class Message extends Model {

    public static function getColumns() {
        return [

            'moment' => [
                'type'              => 'datetime',
                'description'       => 'Message emission date and time.',
                'default'           => function () { return time(); },
                'required'          => true
            ],

            'user_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'core\User',
                'description'       => 'Author of the message.'
            ],

            'content' => [
                'type'              => 'string',
                'usage'             => 'text/plain',
                'description'       => 'Text of the message.',
                'required'          => true
            ]

        ];
    }
}
