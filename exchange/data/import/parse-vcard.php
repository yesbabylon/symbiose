<?php
/*
    Developed by Yesbabylon - https://yesbabylon.com
    (c) 2025-2026 Yesbabylon SA
    Licensed under the GNU AGPL v3 License - https://www.gnu.org/licenses/agpl-3.0.html
*/

use exchange\import\DataImport;
use Symfony\Component\Intl\Countries;

[$params, $providers] = eQual::announce([
    'type'          => 'get',
    'name'          => 'import_parse-vcard',
    'package_name'  => 'exchange',
    'description'   => 'Parses a vCard 4.0 data import and returns its contacts as JSON.',
    'params'        => [
        'id' => [
            'type'              => 'many2one',
            'description'       => 'Identifier of the targeted DataImport object.',
            'foreign_object'    => 'exchange\import\DataImport',
            'required'          => true
        ]
    ],
    'access'        => [
        'visibility'    => 'protected'
    ],
    'response'      => [
        'accept-origin' => '*',
        'content-type'  => 'application/json',
        'charset'       => 'utf-8'
    ],
    'providers'     => ['context']
]);

/**
 * @var \equal\php\Context $context
 */
['context' => $context] = $providers;

$dataImport = DataImport::id($params['id'])
    ->read(['document_id' => ['data']])
    ->first();

if(!$dataImport) {
    throw new Exception('unknown_data_import', EQ_ERROR_UNKNOWN_OBJECT);
}

/**
 * Split a vCard value on an unescaped delimiter while preserving escape sequences.
 */
$split_unescaped = static function(string $value, string $delimiter, bool $honor_quotes = false): array {
    $parts = [];
    $part = '';
    $escaped = false;
    $quoted = false;

    for($index = 0, $length = strlen($value); $index < $length; ++$index) {
        $character = $value[$index];

        if($escaped) {
            $part .= $character;
            $escaped = false;
            continue;
        }

        if($character === '\\') {
            $part .= $character;
            $escaped = true;
            continue;
        }

        if($honor_quotes && $character === '"') {
            $quoted = !$quoted;
            $part .= $character;
            continue;
        }

        if(!$quoted && $character === $delimiter) {
            $parts[] = $part;
            $part = '';
            continue;
        }

        $part .= $character;
    }

    $parts[] = $part;

    return $parts;
};

$unescape_value = static function(string $value): string {
    return preg_replace_callback(
        '/\\\\([nN,;\\\\])/',
        static function(array $matches): string {
            if($matches[1] === 'n' || $matches[1] === 'N') {
                return "\n";
            }

            return $matches[1];
        },
        $value
    );
};

$decode_parameter = static function(string $value): string {
    $value = trim($value);
    if(strlen($value) >= 2 && $value[0] === '"' && $value[strlen($value) - 1] === '"') {
        $value = substr($value, 1, -1);
    }

    return preg_replace_callback(
        '/\^(\^|n|\')/i',
        static function(array $matches): string {
            return match(strtolower($matches[1])) {
                'n'     => "\n",
                "'"     => '"',
                default => '^'
            };
        },
        $value
    );
};

try {
    $vcard_data = $dataImport['document_id']['data'] ?? null;
    if(!is_string($vcard_data)) {
        throw new Exception('Invalid vCard data.');
    }

    // RFC 6350 folds long content lines by starting the continuation with whitespace.
    $vcard_data = preg_replace('/^\xEF\xBB\xBF/', '', $vcard_data);
    $vcard_data = str_replace(["\r\n", "\r"], "\n", $vcard_data);
    $vcard_data = preg_replace('/\n[ \t]/', '', $vcard_data);

    $cards = [];
    $current_card = null;

    foreach(explode("\n", $vcard_data) as $line) {
        if(trim($line) === '') {
            continue;
        }

        $separator_position = null;
        $quoted = false;
        $escaped = false;
        for($index = 0, $length = strlen($line); $index < $length; ++$index) {
            $character = $line[$index];
            if($escaped) {
                $escaped = false;
                continue;
            }
            if($character === '\\') {
                $escaped = true;
                continue;
            }
            if($character === '"') {
                $quoted = !$quoted;
                continue;
            }
            if(!$quoted && $character === ':') {
                $separator_position = $index;
                break;
            }
        }

        if($separator_position === null) {
            throw new Exception('Invalid vCard content line.');
        }

        $header = substr($line, 0, $separator_position);
        $value = substr($line, $separator_position + 1);
        $header_parts = $split_unescaped($header, ';', true);
        $property_identifier = array_shift($header_parts);
        $group_separator_position = strrpos($property_identifier, '.');
        $property_name = strtoupper(
            $group_separator_position === false
                ? $property_identifier
                : substr($property_identifier, $group_separator_position + 1)
        );

        $parameters = [];
        foreach($header_parts as $parameter) {
            $equals_position = strpos($parameter, '=');
            if($equals_position === false) {
                continue;
            }

            $parameter_name = strtoupper(substr($parameter, 0, $equals_position));
            $parameter_values = substr($parameter, $equals_position + 1);
            foreach($split_unescaped($parameter_values, ',', true) as $parameter_value) {
                $parameters[$parameter_name][] = $decode_parameter($parameter_value);
            }
        }

        if($property_name === 'BEGIN' && strtoupper($value) === 'VCARD') {
            if($current_card !== null) {
                throw new Exception('Nested vCards are not supported.');
            }
            $current_card = [
                'version'       => null,
                'properties'    => []
            ];
            continue;
        }

        if($property_name === 'END' && strtoupper($value) === 'VCARD') {
            if($current_card === null) {
                throw new Exception('Unexpected vCard ending.');
            }
            if($current_card['version'] !== '4.0') {
                throw new Exception('Only vCard version 4.0 is supported.');
            }

            $cards[] = $current_card;
            $current_card = null;
            continue;
        }

        if($current_card === null) {
            continue;
        }

        if($property_name === 'VERSION') {
            $current_card['version'] = trim($value);
            continue;
        }

        $current_card['properties'][$property_name][] = [
            'params'    => $parameters,
            'value'     => $value
        ];
    }

    if($current_card !== null || (!$cards && trim($vcard_data) !== '')) {
        throw new Exception('Incomplete or invalid vCard data.');
    }

    $pick_property = static function(array $properties, string $name, ?callable $filter = null): ?array {
        $matches = [];
        foreach($properties[$name] ?? [] as $index => $property) {
            $types = array_map('strtoupper', $property['params']['TYPE'] ?? []);
            if($filter !== null && !$filter($types)) {
                continue;
            }

            $preference = $property['params']['PREF'][0] ?? 1000;
            $matches[] = [
                'property'      => $property,
                'preference'    => ctype_digit((string) $preference) ? (int) $preference : 1000,
                'index'         => $index
            ];
        }

        usort($matches, static function(array $first, array $second): int {
            return [$first['preference'], $first['index']] <=> [$second['preference'], $second['index']];
        });

        return $matches[0]['property'] ?? null;
    };

    $component_to_text = static function(string $value, string $separator = ', ') use ($split_unescaped, $unescape_value): string {
        $values = array_map(
            static fn(string $part): string => trim($unescape_value($part)),
            $split_unescaped($value, ',')
        );

        return implode($separator, array_filter($values, static fn(string $part): bool => $part !== ''));
    };

    $normalize_phone = static function(?array $property) use ($unescape_value): string {
        if($property === null) {
            return '';
        }

        $phone = trim($unescape_value($property['value']));
        if(strncasecmp($phone, 'tel:', 4) === 0) {
            $phone = rawurldecode(substr($phone, 4));
            $phone = explode(';', $phone, 2)[0];
        }

        $phone = preg_replace('/[^0-9+]/', '', $phone);
        if(str_starts_with($phone, '+')) {
            return '+' . str_replace('+', '', substr($phone, 1));
        }

        return str_replace('+', '', $phone);
    };

    $normalize_country = static function(string $country, ?string $language = null): string {
        $country = trim($country);
        if($country === '') {
            return '';
        }

        $country_code = strtoupper($country);
        if(strlen($country_code) === 2 && Countries::exists($country_code)) {
            return $country_code;
        }

        if(strlen($country_code) === 3 && Countries::alpha3CodeExists($country_code)) {
            return Countries::getAlpha2Code($country_code);
        }

        $normalize_country_name = static function(string $name): string {
            $name = mb_strtolower(trim($name), 'UTF-8');
            $name = preg_replace('/[^\p{L}\p{N}]+/u', ' ', $name);

            return trim(preg_replace('/\s+/u', ' ', $name));
        };

        $country_name = $normalize_country_name($country);
        $country_aliases = [
            'united states of america'                         => 'US',
            'republic of albania'                              => 'AL',
            'principality of andorra'                          => 'AD',
            'republic of armenia'                              => 'AM',
            'republic of austria'                              => 'AT',
            'republic of azerbaijan'                           => 'AZ',
            'republic of belarus'                              => 'BY',
            'kingdom of belgium'                               => 'BE',
            'bosnia herzegovina'                               => 'BA',
            'republic of bosnia and herzegovina'               => 'BA',
            'republic of bulgaria'                             => 'BG',
            'republic of croatia'                              => 'HR',
            'republic of cyprus'                               => 'CY',
            'czech republic'                                   => 'CZ',
            'kingdom of denmark'                               => 'DK',
            'republic of estonia'                              => 'EE',
            'republic of finland'                              => 'FI',
            'french republic'                                  => 'FR',
            'republic of georgia'                              => 'GE',
            'federal republic of germany'                      => 'DE',
            'hellenic republic'                                => 'GR',
            'republic of hungary'                              => 'HU',
            'republic of iceland'                              => 'IS',
            'eire'                                             => 'IE',
            'éire'                                             => 'IE',
            'republic of ireland'                              => 'IE',
            'italian republic'                                 => 'IT',
            'republic of latvia'                               => 'LV',
            'principality of liechtenstein'                    => 'LI',
            'republic of lithuania'                            => 'LT',
            'grand duchy of luxembourg'                        => 'LU',
            'republic of malta'                                => 'MT',
            'republic of moldova'                              => 'MD',
            'moldova republic of'                              => 'MD',
            'principality of monaco'                           => 'MC',
            'republic of montenegro'                           => 'ME',
            'holland'                                          => 'NL',
            'the netherlands'                                  => 'NL',
            'kingdom of the netherlands'                       => 'NL',
            'macedonia'                                        => 'MK',
            'republic of north macedonia'                      => 'MK',
            'former yugoslav republic of macedonia'            => 'MK',
            'macedonia the former yugoslav republic of'        => 'MK',
            'kingdom of norway'                                => 'NO',
            'republic of poland'                               => 'PL',
            'portuguese republic'                              => 'PT',
            'rumania'                                          => 'RO',
            'roumania'                                         => 'RO',
            'russian federation'                               => 'RU',
            'republic of san marino'                           => 'SM',
            'most serene republic of san marino'               => 'SM',
            'republic of serbia'                               => 'RS',
            'slovak republic'                                  => 'SK',
            'republic of slovenia'                             => 'SI',
            'kingdom of spain'                                 => 'ES',
            'kingdom of sweden'                                => 'SE',
            'swiss confederation'                              => 'CH',
            'turkey'                                           => 'TR',
            'republic of turkey'                               => 'TR',
            'republic of turkiye'                              => 'TR',
            'republic of türkiye'                              => 'TR',
            'the ukraine'                                      => 'UA',
            'uk'                                               => 'GB',
            'u k'                                              => 'GB',
            'britain'                                          => 'GB',
            'great britain'                                    => 'GB',
            'united kingdom of great britain and northern ireland' => 'GB',
            'holy see'                                         => 'VA',
            'vatican'                                          => 'VA',
            'vatican city state'                               => 'VA',
            'holy see vatican city state'                      => 'VA'
        ];
        if(isset($country_aliases[$country_name])) {
            return $country_aliases[$country_name];
        }

        $locales = array_values(array_unique(array_filter([$language, 'en'])));
        foreach($locales as $locale) {
            try {
                foreach(Countries::getNames($locale) as $code => $name) {
                    if($normalize_country_name($name) === $country_name) {
                        return $code;
                    }
                }
            }
            catch(Throwable $e) {
                // Ignore unknown locales and try the next fallback.
            }
        }

        return '';
    };

    $mapped_data = [];
    foreach($cards as $card) {
        $properties = $card['properties'];

        $firstname = '';
        $lastname = '';
        $name_property = $pick_property($properties, 'N');
        if($name_property !== null) {
            $name_components = array_pad($split_unescaped($name_property['value'], ';'), 5, '');
            $lastname = $component_to_text($name_components[0], ' ');
            $firstname = implode(' ', array_filter([
                $component_to_text($name_components[1], ' '),
                $component_to_text($name_components[2], ' ')
            ], static fn(string $part): bool => $part !== ''));
        }

        if($firstname === '' && $lastname === '') {
            $formatted_name = $pick_property($properties, 'FN');
            $formatted_name = $formatted_name === null ? '' : trim($unescape_value($formatted_name['value']));
            if(str_contains($formatted_name, ',')) {
                [$lastname, $firstname] = array_map('trim', explode(',', $formatted_name, 2));
            }
            elseif($formatted_name !== '') {
                $name_parts = preg_split('/\s+/', $formatted_name);
                $lastname = count($name_parts) > 1 ? array_pop($name_parts) : '';
                $firstname = implode(' ', $name_parts);
                if($firstname === '') {
                    $firstname = $formatted_name;
                }
            }
        }

        $email_property = $pick_property($properties, 'EMAIL');
        $email = $email_property === null ? '' : trim($unescape_value($email_property['value']));
        if(strncasecmp($email, 'mailto:', 7) === 0) {
            $email = rawurldecode(substr($email, 7));
        }

        $mobile_property = $pick_property(
            $properties,
            'TEL',
            static fn(array $types): bool => in_array('CELL', $types, true)
        );
        $phone_property = $pick_property(
            $properties,
            'TEL',
            static fn(array $types): bool => !in_array('CELL', $types, true)
        );

        $address = array_fill(0, 7, '');
        $address_property = $pick_property($properties, 'ADR');
        if($address_property !== null) {
            $address = array_pad($split_unescaped($address_property['value'], ';'), 7, '');
            $address = array_map($component_to_text, array_slice($address, 0, 7));
        }

        $address_language = $address_property['params']['LANGUAGE'][0] ?? null;

        $mapped_data[] = [
            'firstname'         => $firstname,
            'lastname'          => $lastname,
            'email'             => $email,
            'phone'             => $normalize_phone($phone_property),
            'mobile'            => $normalize_phone($mobile_property),
            'address_street'    => $address[2],
            'address_dispatch'  => implode(', ', array_filter([$address[0], $address[1]])),
            'address_city'      => $address[3],
            'address_zip'       => $address[5],
            'address_state'     => $address[4],
            'address_country'   => $normalize_country($address[6], $address_language)
        ];
    }
}
catch(Throwable $e) {
    trigger_error('APP::unable to load data from given vCard: ' . $e->getMessage(), EQ_REPORT_ERROR);
    throw new Exception('failed_loading_vcard', EQ_ERROR_UNKNOWN);
}

$context
    ->httpResponse()
    ->body($mapped_data)
    ->send();
