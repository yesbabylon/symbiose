<?php
/*
    This file is part of the eQual framework <http://www.github.com/cedricfrancoys/equal>
    Some Rights Reserved, Cedric Francoys, 2010-2021
    Licensed under GNU GPL 3 license <http://www.gnu.org/licenses/>
*/
namespace symbiose\setting;

class SettingValue extends \core\setting\SettingValue {

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
