<?php

use core\Lang;
use core\setting\Setting;
use core\setting\SettingSection;
use core\setting\SettingSequence as CoreSettingSequence;
use core\setting\SettingValue as CoreSettingValue;
use symbiose\setting\SettingSequence as SymbioseSettingSequence;
use symbiose\setting\SettingValue as SymbioseSettingValue;

/*
    Migration script for settings naming conventions.

    Existing Setting, SettingValue, SettingSequence and SettingSection rows are
    preserved. When an old setting exists, missing normalized settings and
    values are created as copies.
*/

['orm' => $orm] = eQual::inject(['orm']);

$setting_value_classes = [
    CoreSettingValue::getType(),
    SymbioseSettingValue::getType()
];

$setting_sequence_classes = [
    CoreSettingSequence::getType(),
    SymbioseSettingSequence::getType()
];

$setting_migrations = [
    // Deprecated locale flat keys.
    'core.locale.date_format'                         => 'core.locale.date.format',
    'core.locale.time_format'                         => 'core.locale.time.format',

    // Deprecated numbers group.
    'core.locale.numbers.thousands_separator'         => 'core.locale.number.thousands_separator',
    'core.locale.numbers.decimal_separator'           => 'core.locale.number.decimal_separator',
    'core.locale.numbers.decimal_precision'           => 'core.locale.number.decimal_precision',

    // Deprecated currency and units settings.
    'core.units.currency'                             => 'core.locale.currency.symbol',
    'core.locale.currency'                            => 'core.locale.currency.symbol',
    'core.units.length'                               => 'core.locale.unit.length',
    'core.units.weight'                               => 'core.locale.unit.weight',
    'core.units.volume'                               => 'core.locale.unit.volume',
    'core.units.surface'                              => 'core.locale.unit.surface',
    'core.locale.length'                              => 'core.locale.unit.length',
    'core.locale.weight'                              => 'core.locale.unit.weight',
    'core.locale.volume'                              => 'core.locale.unit.volume',
    'core.locale.surface'                             => 'core.locale.unit.surface',

    // Deprecated main section settings.
    'core.main.company.id'                            => 'core.organization.company.id',
    'core.main.formats.paper'                         => 'core.locale.paper.format',

    // Deprecated security flat keys.
    'core.security.account_creation'                  => 'core.security.auth.account_creation.enabled',
    'core.security.passkey_creation'                  => 'core.security.auth.passkey.enabled',
    'core.security.passkey_rp_id'                     => 'core.security.auth.passkey.rp.id',
    'core.security.passkey_rp_name'                   => 'core.security.auth.passkey.rp.name',
    'core.security.passkey_user_verification'         => 'core.security.auth.passkey.user_verification',
    'core.security.passkey_cross_platform'            => 'core.security.auth.passkey.cross_platform',
    'core.security.passkey_format_android-key'        => 'core.security.auth.passkey.format.android_key',
    'core.security.passkey_format_android-safetynet'  => 'core.security.auth.passkey.format.android_safetynet',
    'core.security.passkey_format_apple'              => 'core.security.auth.passkey.format.apple',
    'core.security.passkey_format_fido-u2f'           => 'core.security.auth.passkey.format.fido_u2f',
    'core.security.passkey_format_none'               => 'core.security.auth.passkey.format.none',
    'core.security.passkey_format_packed'             => 'core.security.auth.passkey.format.packed',
    'core.security.passkey_format_tpm'                => 'core.security.auth.passkey.format.tpm',
    'core.security.passkey_authenticator_usb'         => 'core.security.auth.passkey.authenticator_support.usb',
    'core.security.passkey_authenticator_nfc'         => 'core.security.auth.passkey.authenticator_support.nfc',
    'core.security.passkey_authenticator_ble'         => 'core.security.auth.passkey.authenticator_support.ble',
    'core.security.passkey_authenticator_hybrid'      => 'core.security.auth.passkey.authenticator_support.hybrid',
    'core.security.passkey_authenticator_internal'    => 'core.security.auth.passkey.authenticator_support.internal',

    // Deprecated sale order / booking settings.
    'sale.order.sequence_format'                      => 'sale.accounting.order.sequence_format',
    'sale.order.option_validity'                      => 'sale.features.option.validity_delay',
    'sale.order.option.validity'                      => 'sale.features.option.validity_delay',
    'sale.booking.quote.remind_delay'                 => 'sale.features.quote.remind_delay',
    'sale.booking.option.validity'                    => 'sale.features.option.validity_delay',
    'lodging.main.booking.archive_delay'              => 'sale.features.booking.archive_delay',
    'lodging.main.quote.validity_delay'               => 'sale.features.quote.validity_delay',

    // Deprecated SKU settings.
    'sale.accounting.downpayment_sku'                 => 'sale.organization.sku.downpayment.1',
    'sale.booking.make-beds.sku'                      => 'sale.organization.sku.make_beds',
    'sale.booking.transport.sku'                      => 'sale.organization.sku.transport',
    'sale.booking.bed-linens.sku'                     => 'sale.organization.sku.bed_linens',
    'sale.invoice.downpayment.sku.1'                  => 'sale.organization.sku.downpayment.1',
    'sale.invoice.downpayment.sku.2'                  => 'sale.organization.sku.downpayment.2',
    'sale.invoice.downpayment.sku.3'                  => 'sale.organization.sku.downpayment.3',
    'sale.invoice.downpayment.sku.4'                  => 'sale.organization.sku.downpayment.4',

    // Deprecated accounting path segments.
    'sale.accounting.account_sales'                   => 'sale.accounting.account.sales',
    'sale.accounting.account_sales-taxes'             => 'sale.accounting.account.sales_taxes',
    'sale.accounting.account_trade-debtors'           => 'sale.accounting.account.trade_debtors',
    'sale.accounting.account_downpayment'             => 'sale.accounting.account.downpayment',

    // Deprecated invoice labels stored in locale.
    'sale.locale.label_invoice'                       => 'sale.features.invoice.label.invoice',
    'sale.locale.label_credit-note'                   => 'sale.features.invoice.label.credit_note',
    'sale.locale.label_customer-name'                 => 'sale.features.invoice.label.customer_name',
    'sale.locale.label_customer-address'              => 'sale.features.invoice.label.customer_address',
    'sale.locale.label_registration-number'           => 'sale.features.invoice.label.registration_number',
    'sale.locale.label_vat-number'                    => 'sale.features.invoice.label.vat_number',
    'sale.locale.label_number'                        => 'sale.features.invoice.label.number',
    'sale.locale.label_date'                          => 'sale.features.invoice.label.date',
    'sale.locale.label_status'                        => 'sale.features.invoice.label.status',
    'sale.locale.label_status-paid'                   => 'sale.features.invoice.label.status_paid',
    'sale.locale.label_status-to-pay'                 => 'sale.features.invoice.label.status_to_pay',
    'sale.locale.label_status-to-refund'              => 'sale.features.invoice.label.status_to_refund',
    'sale.locale.label_product-column'                => 'sale.features.invoice.label.product_column',
    'sale.locale.label_qty-column'                    => 'sale.features.invoice.label.qty_column',
    'sale.locale.label_free-column'                   => 'sale.features.invoice.label.free_column',
    'sale.locale.label_unit-price-column'             => 'sale.features.invoice.label.unit_price_column',
    'sale.locale.label_discount-column'               => 'sale.features.invoice.label.discount_column',
    'sale.locale.label_vat-column'                    => 'sale.features.invoice.label.vat_column',
    'sale.locale.label_taxes-column'                  => 'sale.features.invoice.label.taxes_column',
    'sale.locale.label_price-ex-vat-column'           => 'sale.features.invoice.label.price_ex_vat_column',
    'sale.locale.label_price-column'                  => 'sale.features.invoice.label.price_column',
    'sale.locale.label_total-ex-vat'                  => 'sale.features.invoice.label.total_ex_vat',
    'sale.locale.label_total-inc-vat'                 => 'sale.features.invoice.label.total_inc_vat',
    'sale.locale.label_footer-registration-number'    => 'sale.features.invoice.label.footer_registration_number',
    'sale.locale.label_footer-iban'                   => 'sale.features.invoice.label.footer_iban',
    'sale.locale.label_footer-email'                  => 'sale.features.invoice.label.footer_email',
    'sale.locale.label_footer-web'                    => 'sale.features.invoice.label.footer_web',
    'sale.locale.label_footer-tel'                    => 'sale.features.invoice.label.footer_tel',
    'sale.locale.label_footer-fax'                    => 'sale.features.invoice.label.footer_fax',
    'sale.locale.label_pdf-page'                      => 'sale.features.invoice.label.pdf_page',
    'sale.locale.label_balance-of-must-be-paid-before'=> 'sale.features.invoice.label.balance_must_be_paid_before',
    'sale.locale.label_communication'                 => 'sale.features.invoice.label.communication',
    'sale.locale.label_proforma-notice'               => 'sale.features.invoice.label.proforma_notice'
];

$parse_setting_path = static function(string $path): ?array {
    $parts = explode('.', $path, 3);

    if(count($parts) !== 3 || $parts[0] === '' || $parts[1] === '' || $parts[2] === '') {
        return null;
    }

    return [
        'package' => $parts[0],
        'section' => $parts[1],
        'code'    => $parts[2]
    ];
};

$model_to_array = static function($model): array {
    if(is_object($model) && method_exists($model, 'toArray')) {
        return $model->toArray();
    }

    return is_array($model) ? $model : [];
};

$get_languages = static function() use ($orm, $model_to_array): array {
    $languages = [constant('DEFAULT_LANG')];
    $lang_ids = $orm->search(Lang::getType(), []);

    if(is_array($lang_ids) && count($lang_ids)) {
        $langs = $orm->read(Lang::getType(), $lang_ids, ['code']);
        foreach($langs as $lang_model) {
            $lang = $model_to_array($lang_model);
            if(isset($lang['code']) && $lang['code'] !== '') {
                $languages[] = $lang['code'];
            }
        }
    }

    return array_values(array_unique($languages));
};

$get_setting_id = static function(array $setting) use ($orm): ?int {
    $ids = $orm->search(Setting::getType(), [
        ['package', '=', $setting['package']],
        ['section', '=', $setting['section']],
        ['code', '=', $setting['code']]
    ]);

    if(!is_array($ids) || !count($ids)) {
        return null;
    }

    return (int) current($ids);
};

$ensure_section = static function(string $section) use ($orm): int {
    $ids = $orm->search(SettingSection::getType(), ['code', '=', $section]);

    if(is_array($ids) && count($ids)) {
        return (int) current($ids);
    }

    return (int) $orm->create(SettingSection::getType(), [
        'code' => $section,
        'name' => ucfirst(str_replace('_', ' ', $section))
    ]);
};

$ensure_setting = static function(array $source_setting, array $target) use ($orm, $ensure_section, $get_setting_id): ?int {
    $target_setting_id = $get_setting_id($target);

    if($target_setting_id) {
        return $target_setting_id;
    }

    $section_id = $ensure_section($target['section']);
    $values = [
        'package'       => $target['package'],
        'section_id'    => $section_id,
        'code'          => $target['code'],
        'type'          => $source_setting['type'] ?? 'string',
        'is_sequence'   => $source_setting['is_sequence'] ?? false,
        'is_multilang'  => $source_setting['is_multilang'] ?? false,
        'form_control'  => $source_setting['form_control'] ?? 'input'
    ];

    foreach(['title', 'description', 'help', 'object_class'] as $field) {
        if(isset($source_setting[$field])) {
            $values[$field] = $source_setting[$field];
        }
    }

    return (int) $orm->create(Setting::getType(), $values);
};

$get_selector_fields = static function(string $class): array {
    $fields = ['user_id'];

    if(is_a($class, SymbioseSettingValue::class, true) || is_a($class, SymbioseSettingSequence::class, true)) {
        $fields[] = 'organisation_id';
    }

    return $fields;
};

$get_target_item_id = static function(string $class, int $target_setting_id, array $selector) use ($orm): ?int {
    $domain = [
        ['setting_id', '=', $target_setting_id]
    ];

    foreach($selector as $field => $value) {
        if($value === null) {
            $domain[] = [$field, 'is', null];
        }
        else {
            $domain[] = [$field, '=', $value];
        }
    }

    $ids = $orm->search($class, $domain);

    if(!is_array($ids) || !count($ids)) {
        return null;
    }

    return (int) current($ids);
};

$copy_setting_values = static function(int $source_setting_id, int $target_setting_id, bool $is_multilang) use ($orm, $setting_value_classes, $get_languages, $get_selector_fields, $get_target_item_id, $model_to_array): void {
    $languages = $is_multilang ? $get_languages() : [constant('DEFAULT_LANG')];

    foreach($setting_value_classes as $class) {
        $selector_fields = $get_selector_fields($class);
        $fields = array_merge($selector_fields, ['value']);
        $source_value_ids = $orm->search($class, ['setting_id', '=', $source_setting_id]);

        if(!is_array($source_value_ids) || !count($source_value_ids)) {
            continue;
        }

        $source_values = $orm->read($class, $source_value_ids, $fields);
        foreach($source_values as $source_value_id => $source_value_model) {
            $source_value = $model_to_array($source_value_model);
            $selector = [];

            foreach($selector_fields as $field) {
                $selector[$field] = $source_value[$field] ?? null;
            }

            $target_value_id = $get_target_item_id($class, $target_setting_id, $selector);

            if($target_value_id) {
                continue;
            }

            $values = [
                'setting_id' => $target_setting_id,
                'value'      => $source_value['value'] ?? null
            ];

            foreach($selector as $field => $value) {
                $values[$field] = $value;
            }

            $target_value_id = (int) $orm->create($class, $values);

            if(!$is_multilang || $target_value_id <= 0) {
                continue;
            }

            foreach($languages as $lang) {
                $localized_source_values = $orm->read($class, (array) $source_value_id, ['value'], $lang);
                $localized_source_value = $model_to_array($localized_source_values[$source_value_id] ?? []);
                if(isset($localized_source_value['value'])) {
                    $orm->update($class, (array) $target_value_id, ['value' => $localized_source_value['value']], $lang);
                }
            }
        }
    }
};

$copy_setting_sequences = static function(int $source_setting_id, int $target_setting_id) use ($orm, $setting_sequence_classes, $get_selector_fields, $get_target_item_id, $model_to_array): void {
    foreach($setting_sequence_classes as $class) {
        $selector_fields = $get_selector_fields($class);
        $fields = array_merge($selector_fields, ['value']);
        $source_sequence_ids = $orm->search($class, ['setting_id', '=', $source_setting_id]);

        if(!is_array($source_sequence_ids) || !count($source_sequence_ids)) {
            continue;
        }

        $source_sequences = $orm->read($class, $source_sequence_ids, $fields);
        foreach($source_sequences as $source_sequence_model) {
            $source_sequence = $model_to_array($source_sequence_model);
            $selector = [];

            foreach($selector_fields as $field) {
                $selector[$field] = $source_sequence[$field] ?? null;
            }

            if($get_target_item_id($class, $target_setting_id, $selector)) {
                continue;
            }

            $values = [
                'setting_id' => $target_setting_id,
                'value'      => $source_sequence['value'] ?? 1
            ];

            foreach($selector as $field => $value) {
                $values[$field] = $value;
            }

            $orm->create($class, $values);
        }
    }
};

$copy_setting = static function(string $source_path, string $target_path) use ($orm, $parse_setting_path, $get_setting_id, $ensure_setting, $copy_setting_values, $copy_setting_sequences, $model_to_array): void {
    $source = $parse_setting_path($source_path);
    $target = $parse_setting_path($target_path);

    if(!$source || !$target) {
        trigger_error("APP::invalid setting migration path {$source_path} => {$target_path}.", EQ_REPORT_WARNING);
        return;
    }

    $source_setting_id = $get_setting_id($source);

    if(!$source_setting_id) {
        return;
    }

    $source_settings = $orm->read(Setting::getType(), (array) $source_setting_id, [
        'title',
        'description',
        'help',
        'type',
        'object_class',
        'is_sequence',
        'is_multilang',
        'form_control'
    ]);

    if(!isset($source_settings[$source_setting_id])) {
        return;
    }

    $source_setting = $model_to_array($source_settings[$source_setting_id]);
    $target_setting_id = $ensure_setting($source_setting, $target);

    if(!$target_setting_id) {
        return;
    }

    if($source_setting['is_sequence'] ?? false) {
        $copy_setting_sequences($source_setting_id, $target_setting_id);
    }
    else {
        $copy_setting_values($source_setting_id, $target_setting_id, (bool) ($source_setting['is_multilang'] ?? false));
    }
};

$guess_currency_code = static function($value): string {
    $symbol = strtoupper(trim((string) $value));

    if($symbol === html_entity_decode('&euro;', ENT_QUOTES, 'UTF-8') || $symbol === 'EUR') {
        return 'EUR';
    }

    if($symbol === html_entity_decode('&pound;', ENT_QUOTES, 'UTF-8') || $symbol === 'GBP') {
        return 'GBP';
    }

    if($symbol === '$' || $symbol === 'USD') {
        return 'USD';
    }

    if($symbol === 'CHF') {
        return 'CHF';
    }

    return $symbol !== '' ? $symbol : 'EUR';
};

$copy_currency_code = static function(string $source_path) use ($orm, $parse_setting_path, $get_setting_id, $ensure_setting, $setting_value_classes, $get_selector_fields, $get_target_item_id, $guess_currency_code, $model_to_array): void {
    $source = $parse_setting_path($source_path);
    $target = $parse_setting_path('core.locale.currency.code');

    if(!$source || !$target) {
        return;
    }

    $source_setting_id = $get_setting_id($source);

    if(!$source_setting_id) {
        return;
    }

    $source_settings = $orm->read(Setting::getType(), (array) $source_setting_id, [
        'title',
        'description',
        'help',
        'is_multilang',
        'form_control'
    ]);

    if(!isset($source_settings[$source_setting_id])) {
        return;
    }

    $source_setting = $model_to_array($source_settings[$source_setting_id]);
    $target_setting_id = $ensure_setting(array_merge($source_setting, [
        'type'         => 'string',
        'is_sequence'  => false,
        'is_multilang' => false
    ]), $target);

    if(!$target_setting_id) {
        return;
    }

    foreach($setting_value_classes as $class) {
        $selector_fields = $get_selector_fields($class);
        $fields = array_merge($selector_fields, ['value']);
        $source_value_ids = $orm->search($class, ['setting_id', '=', $source_setting_id]);

        if(!is_array($source_value_ids) || !count($source_value_ids)) {
            continue;
        }

        $source_values = $orm->read($class, $source_value_ids, $fields);
        foreach($source_values as $source_value_model) {
            $source_value = $model_to_array($source_value_model);
            $selector = [];

            foreach($selector_fields as $field) {
                $selector[$field] = $source_value[$field] ?? null;
            }

            if($get_target_item_id($class, $target_setting_id, $selector)) {
                continue;
            }

            $values = [
                'setting_id' => $target_setting_id,
                'value'      => $guess_currency_code($source_value['value'] ?? null)
            ];

            foreach($selector as $field => $value) {
                $values[$field] = $value;
            }

            $orm->create($class, $values);
        }
    }
};

foreach($setting_migrations as $source_path => $target_path) {
    $copy_setting($source_path, $target_path);
}

$copy_currency_code('core.units.currency');
$copy_currency_code('core.locale.currency');
