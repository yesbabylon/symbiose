<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace talentlead\communication;

use equal\orm\Model;

class Message extends \core\Mail{

    public static function getColumns() {
        return [

            'title' => [
                'type'              => 'string',
                'description'       => "Title of the message."
            ],

            'conversation_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'talentlead\Conversation',
                'description'       => "Conversation to which the message is associated."
            ],

            'message' => [
                'type'              => 'string',
                'description'       => "Content of the message."
            ],

            'is_request' => [
                'type'              => 'boolean',
                "description"       => 'Is the message a request?',
                'default'           => false
            ],

            'is_response' => [
                'type'              => 'boolean',
                "description"       => 'Is the message a response?',
                'default'           => false
            ],

            'message_template_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'talentlead\MessageTemplate',
                'description'       => "Message template associated to the message."
            ]

        ];
    }
}