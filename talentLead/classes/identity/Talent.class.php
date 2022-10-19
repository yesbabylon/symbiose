<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace talentLead\identity;

class Talent extends \identity\Partner {

    public static function getColumns() {
        return [

            'is_active' => [
                'type'              => 'boolean',
                "description"       => 'Is the customer active ?',
                'default'           => false
            ],

            'linkedin_url' => [
                'type'              => 'string',
                'usage'             => 'url',
                'description'       => 'URL of a linkedin page.',
                'visible'           => ['type', '<>', 'I']
            ],

            'origin' => [
                'type'              => 'string',
                'description'       => "Origin of the talent."
            ],

            'last_contact' => [
                'type'              => 'date',
                'description'       => "Date of the last contact with the talent."
            ],

            // field for retrieving all partners related to the identity
            'prospects_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'talentLead\Prospect',
                'foreign_field'     => 'talent_id',
                'description'       => 'Prospects associated to a Talent.',
                // 'domain'            => ['owner_identity_id', '<>', 'object.id']
            ],

            'status' => [
                'type'      => 'string',
                'selection' => [
                    "open_to_work",
                    "open_to_string_opportunity",
                    "not_now",
                    "not_interested"
                ]
            ],

        ];
    }

}