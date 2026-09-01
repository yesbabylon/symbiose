<?php

use core\setting\SettingSection;

$sections = [
    [
        'code'        => 'accounting',
        'name'        => 'Accounting',
        'description' => 'Accounting (not only financial)',
        'translations' => [
            'fr' => [
                'name'        => 'Comptabilité',
                'description' => 'Comptabilité et suivi des flux'
            ]
        ]
    ],
    [
        'code'        => 'organization',
        'name'        => 'Organization',
        'description' => 'Structure & Organization',
        'translations' => [
            'fr' => [
                'name'        => 'Organisation',
                'description' => 'Structure, départements et périodes internes'
            ]
        ]
    ]
];

foreach($sections as $section_data) {
    $translations = $section_data['translations'];
    unset($section_data['translations']);

    $section = SettingSection::search(['code', '=', $section_data['code']]);

    if(count($section->ids())) {
        $section->update($section_data, 'en');
    }
    else {
        $section = SettingSection::create($section_data, 'en');
    }

    foreach($translations as $lang => $translation) {
        $section->update($translation, $lang);
    }
}
