<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace talentlead\campaign;

use equal\orm\Model;

class CampaignTemplate extends Model {

    public static function getColumns() {
        return [

            'name' => [
                'type'              => 'string',
                'description'       => "Name of the Template.",
                'required'          => true
            ],

            'description' => [
                'type'              => 'string',
                'usage'             => 'text/plain',
                'description'       => 'Description of the Template.'
            ],

            'campaign_template_criterias_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'talentlead\campaign\CampaignTemplateCriteria',
                'foreign_field'     => 'campaign_template_id',
                'description'       => 'Values of the campaign template.',
            ],

            'campaigns_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'talentlead\campaign\Campaign',
                'foreign_field'     => 'campaign_template_id',
                'description'       => 'Campaigns using the template.',
            ]
        ];
    }

}