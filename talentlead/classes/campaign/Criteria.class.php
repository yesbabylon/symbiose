<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace talentlead\campaign;

use equal\orm\Model;

class Criteria extends Model {

    public static function getColumns() {
        return [

            'name' => [
                'type'              => 'string',
                'description'       => "Name of the Criteria.",
                'multilang'         => true
            ],

            'description' => [
                'type'              => 'string',
                'usage'             => 'text/plain',
                'description'       => 'Description of the Criteria.',
                'multilang'         => true
            ],

            'type' => [
                'type'              => 'string',
                'description'       => 'Type of the Criteria.'
            ],

            'form_control' => [
                'type'              => 'string',
                'description'       => 'Form control of the Criteria.'
            ],

            'is_multiple' => [
                'type'              => 'boolean',
                "description"       => 'Is there multiple criteria ?',
                'default'           => false
            ],

            'criteria_choices_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'talentlead\campaign\CriteriaChoice',
                'foreign_field'     => 'criteria_id',
                'description'       => 'Criteria choices.',
                // 'domain'            => ['owner_identity_id', '<>', 'object.id']
            ],

            // field for retrieving all partners related to the identity
            'campaign_criterias_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'talentlead\campaign\CampaignCriteria',
                'foreign_field'     => 'criteria_id',
                'description'       => '',
                // 'domain'            => ['owner_identity_id', '<>', 'object.id']
            ]

        ];
    }

}