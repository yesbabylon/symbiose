<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2024
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace symbiose\setting;

class Setting extends \core\setting\Setting {

    public static function getColumns() {
        return [

            'setting_values_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'symbiose\setting\SettingValue',
                'foreign_field'     => 'setting_id',
                'sort'              => 'asc',
                'order'             => 'name',
                'description'       => 'List of values related to the setting.'
            ],

            'setting_sequences_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'symbiose\setting\SettingSequence',
                'foreign_field'     => 'setting_id',
                'sort'              => 'asc',
                'order'             => 'name',
                'description'       => 'List of sequences related to the setting.'
            ]

        ];
    }

    protected static function getSelectorKeys() {
        return ['user_id', 'organisation_id'];
    }

    protected static function getSettingValueClass(): string {
        return SettingValue::class;
    }

    protected static function getSettingSequenceClass(): string {
        return SettingSequence::class;
    }
}
