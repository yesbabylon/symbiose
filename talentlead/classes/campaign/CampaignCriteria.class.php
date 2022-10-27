<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace talentLead\campaign;

use equal\orm\Model;

class CampaignCriteria extends Model {

    public static function getColumns() {
        return [

            'criteria_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'talentLead\Criteria',
                'description'       => "The criteria of the Campaign."
            ],

            'campaign_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'talentLead\Campaign',
                'description'       => "The related campaign."
            ],

            'order'       => [
                'type'              => 'integer',
                'description'       => "order of the Criteria"
            ],

            'campaign_criteria_values_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'talentLead\CampaignCriteriaValue',
                'foreign_field'     => 'campaign_criteria_id',
                'description'       => 'Values of the campaign criteria.',
                // 'domain'            => ['owner_identity_id', '<>', 'object.id']
            ]

        ];
    }

}