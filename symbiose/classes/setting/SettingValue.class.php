<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2024
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace symbiose\setting;

class SettingValue extends \core\setting\SettingValue {

    public static function getModelScope(): ?string {
        return null;
    }

    public static function getColumns() {
        return [

            'setting_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'symbiose\setting\Setting',
                'description'       => 'Setting the value relates to.',
                'ondelete'          => 'cascade',
                'required'          => true
            ],

            'user_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'identity\User',
                'description'       => 'User the setting is specific to (optional).',
                'default'           => 0,
                'ondelete'          => 'cascade'
            ],

            'organization_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'identity\Organization',
                'description'       => 'Organization the setting is specific to (optional).',
                'default'           => 0,
                'ondelete'          => 'cascade'
            ]

        ];
    }

}
