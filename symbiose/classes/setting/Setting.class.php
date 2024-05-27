<?php
/*
    This file is part of the eQual framework <http://www.github.com/cedricfrancoys/equal>
    Some Rights Reserved, Cedric Francoys, 2010-2021
    Licensed under GNU GPL 3 license <http://www.gnu.org/licenses/>
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

            $settings = $orm->read(self::getType(), $settings_ids, ['type', 'is_multilang', 'setting_values_ids']);

            if($settings > 0 && count($settings)) {
                // #memo - there should be exactly one setting matching the criterias
                $setting = array_pop($settings);

                $values_lang = constant('DEFAULT_LANG');
                if($setting['is_multilang']) {
                    $values_lang = $lang;
                }

                $setting_id = 0;
                $setting_values = $orm->read(SettingValue::getType(), $setting['setting_values_ids'], ['id', 'organisation_id', 'user_id'], $values_lang);
                if($setting_values > 0) {
                    foreach($setting_values as $setting_value) {
                        if(intval($setting_value['user_id']) == intval($selector['user_id'] ?? 0)
                            && intval($setting_value['organisation_id']) == intval($selector['organisation_id'] ?? 0)) {
                            $setting_id = $setting_value['id'];
                            break;
                        }
                    }
                }
                if($setting_id > 0) {
                    $result = $orm->fetchAndAdd(SettingValue::getType(), $setting_id, 'value', $increment);
                }
            }
        }

        return $result;
    }

}
