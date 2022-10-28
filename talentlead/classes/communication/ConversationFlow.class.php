<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace talentlead\communication;

use equal\orm\Model;

class ConversationFlow extends \communication\Template {

    public static function getColumns() {
        return [

            'name' => [
                'type'              => 'string',
                'description'       => "Name of the Conversation Flow."
            ],

            'conversations_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'talentlead\communication\Conversation',
                'foreign_field'     => 'conversation_flow_id',
                'description'       => ''
            ],

            'conversation_flow_actions_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'talentLead\communication\ConversationFlowAction',
                'foreign_field'     => 'conversation_flow_id',
                'description'       => 'Messages associated to a conversation.'
            ]

        ];
    }
}