<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace talentlead\communication;

use equal\orm\Model;

class Conversation extends Model {

    public static function getColumns() {
        return [

            'prospect_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'talentlead\identity\Prospect',
                'description'       => "Prospect related to a conversation."
            ],

            'campaign_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'talentlead\campaign\Campaign',
                'description'       => "Campaign associated to the prospect."
            ],

            'conversation_flow_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'talentlead\communication\ConversationFlow',
                'description'       => "Conversation Flow associated to the prospect."
            ],

            "status"                => [
                'type'              => 'string',
                'description'       => "Status of the conversation."
            ],

            'messages_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'talentlead\communication\Message',
                'foreign_field'     => 'conversation_id',
                'description'       => 'Messages associated to a conversation.',
                // 'domain'            => ['owner_identity_id', '<>', 'object.id']
            ],

        ];
    }

}