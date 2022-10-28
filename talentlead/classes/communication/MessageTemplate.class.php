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

            'name' => [
                'type'              => 'alias',
                'alias'             => 'subject'
            ],

            'subject' => [
                'type'              => 'string',
                'description'       => "Title of the message template."
            ],

            'body' => [
                'type'              => 'string',
                'usage'             => 'text/plain',
                'description'       => "Content of the message."
            ],

            'conversation_flow_actions_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'talentLead\communication\ConversationFlowAction',
                'foreign_field'     => 'message_template_id',
                'description'       => 'Message Templates associated to a conversation flow action.'
            ],

            'messages_id' => [
                'type'              => 'one2many',
                'foreign_object'    => 'talentLead\communication\Message',
                'foreign_field'     => 'message_template_id',
                'description'       => '.'
            ]

        ];
    }
}