<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace hr\recruit;

class Talent extends \identity\Partner {

    public function getTable() {
        return 'talentlead_identity_talent';
    }

    public static function getColumns() {
        return [

            'is_active' => [
                'type'              => 'boolean',
                'description'       => 'Is the talent active?',
                'default'           => false
            ],

            'linkedin_url' => [
                'type'              => 'string',
                'usage'             => 'uri/url',
                'description'       => 'URL of a LinkedIn page.',
                'visible'           => ['type', '<>', 'IN']
            ],

            'origin' => [
                'type'              => 'string',
                'description'       => 'Origin of the talent.'
            ],

            'last_contact' => [
                'type'              => 'date',
                'description'       => 'Date of the last contact with the talent.'
            ],

            'status' => [
                'type'      => 'string',
                'selection' => [
                    'open_to_work',
                    'open_to_strong_opportunity',
                    'not_now',
                    'not_interested'
                ]
            ]

        ];
    }

}
