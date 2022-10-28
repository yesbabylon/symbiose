<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace talentlead\campaign;

use equal\orm\Model;

class CampaignCriteria extends Model {

    public static function getColumns() {
        return [
            'name' =>  [
                'type'              => 'string',
                'description'       => "name of the Criteria",
                'multilang'         => true
            ],

            'criteria_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'talentlead\campaign\Criteria',
                'description'       => "The criteria of the Campaign."
            ],

            'campaign_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'talentlead\campaign\Campaign',
                'description'       => "The related campaign."
            ],

            'order'       => [
                'type'              => 'integer',
                'description'       => "order of the Criteria"
            ],

            'campaign_criteria_values_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'talentlead\campaign\CampaignCriteriaValue',
                'foreign_field'     => 'campaign_criteria_id',
                'description'       => 'Values of the campaign criteria.',
                // 'domain'            => ['owner_identity_id', '<>', 'object.id']
            ]

        ];
    }

}