<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace talentlead\campaign;

use equal\orm\Model;

class Campaign extends Model {

    public static function getColumns() {
        return [

            'customer_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'talentlead\Customer',
                // 'onupdate'          => 'onupdateTypeId',
                // 'default'           => 1,                                    // default is 'I' individual
                'description'       => 'Customer touched by the campaign.'
            ],

            'job_title' => [
                'type'              => 'string',
                "description"       => 'Name of the job'
            ],

            'job_description' => [
                'type'              => 'string',
                "description"       => 'Description of a job'
            ],

            'code' => [
                'type'              => 'string',
                'description'       => "A variable length string representing the number of the campaign.",
                'required'          => true
            ],


            'campaign_template_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'talentlead\CampaignTemplate',
                'description'       => "Template associated to a Campaign."
            ],

            'campaign_criterias_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'talentlead\CampaignCriteria',
                'foreign_field'     => 'campaign_id',
                'description'       => 'Criterias related to a campaign.',
                // 'domain'            => ['owner_identity_id', '<>', 'object.id']
            ],

            'prospects_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'talentlead\Prospect',
                'foreign_field'     => 'campaign_id',
                'description'       => 'Pespectives de campagne.',
                // 'domain'            => ['owner_identity_id', '<>', 'object.id']
            ],

            'status' => [
                'type'      => 'string',
                'selection' => [
                    "open",
                    "on hold",
                    "canceled",
                    "closed"
                ]
            ],

        ];
    }

    public function getUnique() {
        return [
            ['code']
        ];
    }

}