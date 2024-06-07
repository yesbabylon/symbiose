<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2024
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

namespace communication\conversation;

use equal\orm\Model;

class Channel extends Model {

    public static function getColumns() {
        return [

            'name' => [
                'type'              => 'string',
                'description'       => 'Name of the channel.',
                'required'          => true
            ],

            'organisation_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'identity\Organisation',
                'description'       => 'The organisation the channel is dedicated.',
                'default'           => 1,
                'required'          => true
            ],

            'users_ids' => [
                'type'              => 'many2many',
                'foreign_object'    => 'core\User',
                'foreign_field'     => 'channels_ids',
                'rel_table'         => 'communication_channel_rel_core_user',
                'rel_foreign_key'   => 'user_id',
                'rel_local_key'     => 'channel_id'
            ],

            'conversations_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'communication\conversation\Conversation',
                'foreign_field'     => 'channel_id',
                'description'       => 'The conversations of the channel.'
            ]

        ];
    }
}
