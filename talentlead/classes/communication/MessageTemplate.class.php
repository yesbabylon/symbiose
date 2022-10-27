<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace talentlead\communication;

use equal\orm\Model;

class MessageTemplate extends Model{

    public static function getColumns() {
        return [

            'title' => [
                'type'              => 'string',
                'description'       => "Title of the message template."
            ],

            'message' => [
                'type'              => 'string',
                'description'       => "Content of the message."
            ],

        ];
    }
}