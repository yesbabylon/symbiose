<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace talentlead\communication;

use equal\orm\Model;

class ConversationFlowAction extends Model{

    public static function getColumns() {
        return [

            'name' => [
                'type'              => 'string',
                'description'       => "Name of the Conversation Flow Action."
            ],

            'delay' => [
                'type'              => 'integer',
                'description'       => "Delay in days of a conversation flow action."
            ],

            'conversation_flow_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'talentlead\communication\ConversationFlow',
                'description'       => "Conversation Flow associated to the Conversation Flow Action."
            ],

            'message_template_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'talentlead\communication\MessageTemplate',
                'description'       => "Message template associated to the conversation flow action."
            ]

        ];
    }
}