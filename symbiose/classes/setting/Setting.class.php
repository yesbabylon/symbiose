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

    public static function fetch_and_add(string $package, string $section, string $code, $increment=null, array $selector=[], string $lang='en') {
        $result = null;

        $providers = \eQual::inject(['orm']);
        /** @var \equal\orm\ObjectManager */
        $orm = $providers['orm'];

        $settings_ids = $orm->search(self::getType(), [
            ['package', '=', $package],
            ['section', '=', $section],
            ['code', '=', $code]
        ]);

        if($settings_ids > 0 && count($settings_ids)) {

            $settings = $orm->read(self::getType(), $settings_ids, ['type', 'setting_sequences_ids']);

            if($settings > 0 && count($settings)) {
                // #memo - there should be exactly one setting matching the criterias
                $setting = array_pop($settings);

                $setting_sequence_id = 0;
                $setting_sequences = $orm->read(SettingSequence::getType(), $setting['setting_sequences_ids'], ['id', 'organisation_id']);
                if($setting_sequences > 0) {
                    foreach($setting_sequences as $sequence) {
                        if(intval($sequence['organisation_id']) == intval($selector['organisation_id'] ?? 0)) {
                            $setting_sequence_id = $sequence['id'];
                            break;
                        }
                    }
                }
                if($setting_sequence_id > 0) {
                    $result = $orm->fetchAndAdd(SettingSequence::getType(), $setting_sequence_id, 'value', $increment);
                }
            }
        }

        return $result;
    }

}
