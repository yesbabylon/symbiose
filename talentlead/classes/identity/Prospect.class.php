<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace talentlead\identity;

use equal\orm\Model;

class Prospect extends Model {

    public static function getColumns() {
        return [

            'name' => [
                'type'              => 'alias',
                'alias'             => 'id'
            ],

            'campaign_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'talentlead\campaign\Campaign',
                'description'       => "Campaign associated to the Prospect."
            ],

            'talent_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'talentlead\identity\Talent',
                'description'       => "Talent associated to the prospect."
            ],

            'conversations_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'talentlead\communication\Conversation',
                'foreign_field'     => 'prospect_id',
                'description'       => '',
                // 'domain'            => ['owner_identity_id', '<>', 'object.id']
            ]

        ];
    }

}