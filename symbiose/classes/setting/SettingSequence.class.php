<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2024
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace symbiose\setting;

class SettingSequence extends \core\setting\SettingSequence {

    public static function getColumns() {
        return [

            'setting_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'symbiose\setting\Setting',
                'description'       => 'Setting the value relates to.',
                'ondelete'          => 'cascade',
                'required'          => true
            ],

            'organisation_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'identity\Organisation',
                'description'       => 'Organisation the setting is specific to (optional).',
                'default'           => 0,
                'ondelete'          => 'cascade'
            ]

        ];
    }

}
