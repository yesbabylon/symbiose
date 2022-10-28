<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace talentlead\campaign;

use equal\orm\Model;

class CampaignTemplateCriteria extends Model {

    public static function getColumns() {
        return [

            'criteria_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'talentlead\campaign\Criteria',
                'description'       => "The criteria of the Campaign."
            ],

            'campaign_template_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'talentlead\campaign\CampaignTemplate',
                'description'       => "The related campaign template."
            ],

            'order'       => [
                'type'              => 'integer',
                'description'       => "order of the Criteria"
            ]

        ];
    }

}