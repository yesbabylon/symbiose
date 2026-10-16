<?php

use document\document\Document;
use exchange\import\identity\ContactImport;
use identity\Contact;
use identity\Identity;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$contact_headers = [
    'firstname',
    'lastname',
    'email',
    'phone',
    'mobile',
    'address_street',
    'address_dispatch',
    'address_city',
    'address_zip',
    'address_state',
    'address_country'
];

$build_csv = static function(array $headers, array $row): string {
    $stream = fopen('php://temp', 'w+');
    if($stream === false) {
        throw new RuntimeException('Unable to open the CSV memory stream.');
    }

    try {
        fputcsv($stream, $headers, ',', '"', '');
        fputcsv($stream, $row, ',', '"', '');
        rewind($stream);
        $data = stream_get_contents($stream);
        if($data === false) {
            throw new RuntimeException('Unable to read the CSV memory stream.');
        }

        return $data;
    }
    finally {
        fclose($stream);
    }
};

$build_xlsx = static function(array $headers, array $row): string {
    $spreadsheet = new Spreadsheet();
    $spreadsheet->getActiveSheet()
        ->setTitle('Instructions')
        ->setCellValue('A1', 'Contact import test workbook');
    $sheet = $spreadsheet->createSheet();
    $sheet->setTitle('Contacts');
    $spreadsheet->setActiveSheetIndex($spreadsheet->getIndex($sheet));
    foreach([$headers, $row] as $row_index => $values) {
        foreach(array_values($values) as $column_index => $value) {
            $sheet
                ->getCell(chr(65 + $column_index) . ($row_index + 1))
                ->setValueExplicit((string) $value, DataType::TYPE_STRING);
        }
    }

    $tmp_file = tempnam(sys_get_temp_dir(), 'contact_import_test_');
    if($tmp_file === false) {
        $spreadsheet->disconnectWorksheets();
        throw new RuntimeException('Unable to create the XLSX temporary file.');
    }

    try {
        $writer = new Xlsx($spreadsheet);
        $writer->save($tmp_file);
        $data = file_get_contents($tmp_file);
        if($data === false) {
            throw new RuntimeException('Unable to read the XLSX temporary file.');
        }

        return $data;
    }
    finally {
        $spreadsheet->disconnectWorksheets();
        if(file_exists($tmp_file)) {
            unlink($tmp_file);
        }
    }
};

$run_contact_import = static function(array $fixture) use ($contact_headers, $build_csv, $build_xlsx): array {
    $contact_import_id = null;
    $result = [
        'error'     => null,
        'expected'  => $fixture['expected'],
        'import'    => null,
        'parsed'    => null,
        'identity'  => null,
        'contact'   => null
    ];

    try {
        if($fixture['format'] === 'csv') {
            $data = $build_csv($fixture['headers'] ?? $contact_headers, $fixture['row']);
        }
        elseif($fixture['format'] === 'xlsx') {
            $data = $build_xlsx($fixture['headers'] ?? $contact_headers, $fixture['row']);
        }
        elseif($fixture['format'] === 'vcard') {
            $data = $fixture['data'];
        }
        else {
            throw new RuntimeException("Unsupported test fixture format `{$fixture['format']}`.");
        }

        $contact_import = ContactImport::create([
                'state' => 'draft',
                'name'  => $fixture['filename'],
                'data'  => $data
            ])
            ->read(['id'])
            ->first(true);
        $contact_import_id = $contact_import['id'];

        ContactImport::id($contact_import_id)->update(['state' => 'instance']);
        $result['parsed'] = eQual::run('get', 'exchange_import_identity_ContactImport_parse', [
            'id' => $contact_import_id
        ]);
    }
    catch(Throwable $e) {
        $result['error'] = get_class($e) . ': ' . $e->getMessage();
    }

    if($contact_import_id) {
        $result['import'] = ContactImport::id($contact_import_id)
            ->read(['id', 'status', 'logs', 'document_id'])
            ->first(true);
    }

    $created_identity_id = null;
    $logs = json_decode($result['import']['logs'] ?? '[]', true);
    foreach(is_array($logs) ? $logs : [] as $log) {
        if(preg_match('/^INFO- created identity id ([0-9]+)$/', $log, $matches)) {
            $created_identity_id = (int) $matches[1];
        }
    }

    if($created_identity_id) {
        $result['identity'] = Identity::id($created_identity_id)
            ->read(array_merge(['id'], array_keys($fixture['expected'])))
            ->first(true);
    }

    if($result['identity']) {
        $result['contact'] = Contact::search(['identity_id', '=', $result['identity']['id']])
            ->read(['id', 'identity_id'])
            ->first(true);
    }

    return $result;
};

$assert_contact_import = static function(array $result): bool {
    if($result['error'] !== null) {
        throw new RuntimeException($result['error']);
    }
    if(!$result['import']) {
        throw new RuntimeException('The ContactImport record was not created.');
    }

    if($result['import']['status'] !== 'imported') {
        throw new RuntimeException(
            "Unexpected import status `{$result['import']['status']}` with logs: " . ($result['import']['logs'] ?? '[]')
        );
    }
    if(!$result['identity']) {
        throw new RuntimeException('The expected Identity record was not created.');
    }
    if(!$result['contact']) {
        throw new RuntimeException('The expected Contact record was not created.');
    }
    if(!is_array($result['parsed']) || count($result['parsed']) !== 1) {
        throw new RuntimeException('The contact parser did not return exactly one contact.');
    }

    foreach($result['expected'] as $field => $expected_value) {
        if(($result['parsed'][0][$field] ?? null) !== $expected_value) {
            throw new RuntimeException(
                "Unexpected parsed value for `{$field}`: "
                . json_encode($result['parsed'][0][$field] ?? null)
                . ', expected ' . json_encode($expected_value) . '.'
            );
        }
    }

    foreach($result['expected'] as $field => $expected_value) {
        if(($result['identity'][$field] ?? null) !== $expected_value) {
            throw new RuntimeException(
                "Unexpected Identity value for `{$field}`: "
                . json_encode($result['identity'][$field] ?? null)
                . ', expected ' . json_encode($expected_value) . '.'
            );
        }
    }

    if($result['contact']['identity_id'] !== $result['identity']['id']) {
        throw new RuntimeException('The Contact is not linked to the imported Identity.');
    }

    $logs = json_decode($result['import']['logs'] ?? '[]', true);
    if(!is_array($logs) || empty($logs)) {
        throw new RuntimeException('The ContactImport logs are missing or invalid.');
    }
    foreach($logs as $log) {
        if(strncmp($log, 'ERR', 3) === 0) {
            throw new RuntimeException("The ContactImport logs contain an error: {$log}");
        }
    }

    return true;
};

$rollback_contact_import = static function($result): void {
    if(!is_array($result)) {
        return;
    }

    $errors = [];
    $identity_id = $result['identity']['id'] ?? null;
    $contact_id = $result['contact']['id'] ?? null;
    $import_id = $result['import']['id'] ?? null;
    $document_id = $result['import']['document_id'] ?? null;

    if($identity_id) {
        try {
            Identity::id($identity_id)->update(['contact_id' => null]);
        }
        catch(Throwable $e) {
            $errors[] = $e->getMessage();
        }
    }
    if($contact_id) {
        try {
            Contact::id($contact_id)->delete(true);
        }
        catch(Throwable $e) {
            $errors[] = $e->getMessage();
        }
    }
    if($identity_id) {
        try {
            Identity::id($identity_id)->delete(true);
        }
        catch(Throwable $e) {
            $errors[] = $e->getMessage();
        }
    }
    if($import_id) {
        try {
            ContactImport::id($import_id)->delete(true);
        }
        catch(Throwable $e) {
            $errors[] = $e->getMessage();
        }
    }
    if($document_id) {
        try {
            Document::id($document_id)->delete(true);
        }
        catch(Throwable $e) {
            $errors[] = $e->getMessage();
        }
    }

    if($errors) {
        throw new RuntimeException(implode(' | ', $errors));
    }
};

$tests = [
    '0101' => [
        'description' => 'Import a contact from a CSV document.',
        'arrange'     => static function() use ($contact_headers) {
            $suffix = bin2hex(random_bytes(6));
            $headers = [
                'mobile',
                'firstname',
                'email',
                'lastname',
                'phone',
                'address_country',
                'address_zip',
                'address_city',
                'address_state',
                'address_street',
                'address_dispatch'
            ];
            $row = [
                '+32 470 11 22 33',
                ' Celia ',
                " contact-import-csv-{$suffix}@example.com ",
                ' Comma ',
                '+32 (2) 555.01.01',
                ' be ',
                ' 1000 ',
                ' Brussels ',
                ' Brussels ',
                ' 10 CSV Street ',
                ' Floor 2 '
            ];

            return [
                'filename' => "contact-import-{$suffix}.csv",
                'format'   => 'csv',
                'headers'  => $headers,
                'row'      => $row,
                'expected' => array_combine($contact_headers, [
                    'Celia',
                    'Comma',
                    "contact-import-csv-{$suffix}@example.com",
                    '+3225550101',
                    '+32470112233',
                    '10 CSV Street',
                    'Floor 2',
                    'Brussels',
                    '1000',
                    'Brussels',
                    'BE'
                ])
            ];
        },
        'act'         => $run_contact_import,
        'assert'      => $assert_contact_import,
        'rollback'    => $rollback_contact_import
    ],

    '0102' => [
        'description' => 'Import a contact from an XLSX document.',
        'arrange'     => static function() use ($contact_headers) {
            $suffix = bin2hex(random_bytes(6));
            $headers = [
                'email',
                'lastname',
                'firstname',
                'mobile',
                'phone',
                'address_city',
                'address_street',
                'address_zip',
                'address_dispatch',
                'address_country',
                'address_state'
            ];
            $row = [
                " contact-import-xlsx-{$suffix}@example.com ",
                ' Sheets ',
                ' Xavier ',
                '+33 6 12 34 56 78',
                '+33 (0)1 42.68.53.00',
                ' Paris ',
                ' 20 Workbook Avenue ',
                ' 75001 ',
                ' Suite 5 ',
                ' fr ',
                ' Ile-de-France '
            ];

            return [
                'filename' => "contact-import-{$suffix}.xlsx",
                'format'   => 'xlsx',
                'headers'  => $headers,
                'row'      => $row,
                'expected' => array_combine($contact_headers, [
                    'Xavier',
                    'Sheets',
                    "contact-import-xlsx-{$suffix}@example.com",
                    '+330142685300',
                    '+33612345678',
                    '20 Workbook Avenue',
                    'Suite 5',
                    'Paris',
                    '75001',
                    'Ile-de-France',
                    'FR'
                ])
            ];
        },
        'act'         => $run_contact_import,
        'assert'      => $assert_contact_import,
        'rollback'    => $rollback_contact_import
    ],

    '0103' => [
        'description' => 'Import a contact from a vCard 4.0 document.',
        'arrange'     => static function() use ($contact_headers) {
            $suffix = bin2hex(random_bytes(6));
            $email = "contact-import-vcard-{$suffix}@example.com";
            $vcard = implode("\r\n", [
                'BEGIN:VCARD',
                'VERSION:4.0',
                'N:Vcard;Vivian;;;',
                'FN:Vivian Vcard',
                "EMAIL;PREF=1:mailto:{$email}",
                'TEL;TYPE=voice;PREF=1:tel:+32-2-555-02-02',
                'TEL;TYPE=cell;PREF=1:tel:+32-471-22-33-44',
                'ADR;TYPE=home;LANGUAGE=en:;Apartment 4;30 Card Street;Brussels;Brussels;1000;Belgium',
                'END:VCARD',
                ''
            ]);

            return [
                'filename' => "contact-import-{$suffix}.vcf",
                'format'   => 'vcard',
                'data'     => $vcard,
                'expected' => array_combine($contact_headers, [
                    'Vivian',
                    'Vcard',
                    $email,
                    '+3225550202',
                    '+32471223344',
                    '30 Card Street',
                    'Apartment 4',
                    'Brussels',
                    '1000',
                    'Brussels',
                    'BE'
                ])
            ];
        },
        'act'         => $run_contact_import,
        'assert'      => $assert_contact_import,
        'rollback'    => $rollback_contact_import
    ],

    '0104' => [
        'description' => 'Reimport the same CSV as a distinct duplicate contact.',
        'arrange'     => static function() use ($contact_headers) {
            $suffix = bin2hex(random_bytes(6));
            $row = [
                'Daisy',
                'Duplicate',
                "contact-reimport-{$suffix}@example.com",
                '+3225550303',
                '+32472334455',
                '40 Duplicate Lane',
                'Building B',
                'Namur',
                '5000',
                'Namur',
                'BE'
            ];

            return [
                'filename' => "contact-reimport-{$suffix}.csv",
                'format'   => 'csv',
                'row'      => $row,
                'expected' => array_combine($contact_headers, [
                    'Daisy',
                    'Duplicate',
                    "contact-reimport-{$suffix}@example.com",
                    '+3225550303',
                    '+32472334455',
                    '40 Duplicate Lane',
                    'Building B',
                    'Namur',
                    '5000',
                    'Namur',
                    'BE'
                ])
            ];
        },
        'act'         => static function(array $fixture) use ($run_contact_import): array {
            $first = $run_contact_import($fixture);
            $second = $run_contact_import($fixture);

            return [
                'first'          => $first,
                'second'         => $second,
                'identity_count' => Identity::search(['email', '=', $fixture['expected']['email']])->count()
            ];
        },
        'assert'      => static function(array $result) use ($assert_contact_import): bool {
            $assert_contact_import($result['first']);
            $assert_contact_import($result['second']);

            if($result['first']['identity']['id'] === $result['second']['identity']['id']) {
                throw new RuntimeException('The repeated import reused the first Identity.');
            }
            if($result['first']['contact']['id'] === $result['second']['contact']['id']) {
                throw new RuntimeException('The repeated import reused the first Contact.');
            }
            if($result['identity_count'] !== 2) {
                throw new RuntimeException(
                    "Unexpected duplicate count {$result['identity_count']}, expected 2."
                );
            }

            return true;
        },
        'rollback'    => static function($result) use ($rollback_contact_import): void {
            if(!is_array($result)) {
                return;
            }

            $rollback_contact_import($result['second'] ?? null);
            $rollback_contact_import($result['first'] ?? null);
        }
    ],

    '0105' => [
        'description' => 'Import an XLSX contact with only names and email columns.',
        'arrange'     => static function() {
            $suffix = bin2hex(random_bytes(6));

            return [
                'filename' => "contact-import-minimal-{$suffix}.xlsx",
                'format'   => 'xlsx',
                'headers'  => ['email', 'firstname', 'lastname'],
                'row'      => [
                    " contact-import-minimal-{$suffix}@example.com ",
                    ' Minimal ',
                    ' XLSX '
                ],
                'expected' => [
                    'firstname' => 'Minimal',
                    'lastname'  => 'XLSX',
                    'email'     => "contact-import-minimal-{$suffix}@example.com"
                ]
            ];
        },
        'act'         => $run_contact_import,
        'assert'      => $assert_contact_import,
        'rollback'    => $rollback_contact_import
    ],

    '0106' => [
        'description' => 'Import a CSV contact with only names and phone columns.',
        'arrange'     => static function() {
            $suffix = bin2hex(random_bytes(6));

            return [
                'filename' => "contact-import-minimal-{$suffix}.csv",
                'format'   => 'csv',
                'headers'  => ['phone', 'firstname', 'lastname'],
                'row'      => [
                    '+32 (2) 555.06.06',
                    ' Minimal ',
                    ' CSV '
                ],
                'expected' => [
                    'firstname' => 'Minimal',
                    'lastname'  => 'CSV',
                    'phone'     => '+3225550606'
                ]
            ];
        },
        'act'         => $run_contact_import,
        'assert'      => $assert_contact_import,
        'rollback'    => $rollback_contact_import
    ],

    '0107' => [
        'description' => 'Reject an XLSX contact import without a lastname column.',
        'arrange'     => static function() {
            $suffix = bin2hex(random_bytes(6));

            return [
                'filename' => "contact-import-invalid-{$suffix}.xlsx",
                'format'   => 'xlsx',
                'headers'  => ['firstname', 'email'],
                'row'      => ['Invalid', "contact-import-invalid-{$suffix}@example.com"],
                'expected' => []
            ];
        },
        'act'         => $run_contact_import,
        'assert'      => static function(array $result): bool {
            if(strpos((string) $result['error'], 'invalid_xlsx_headers') === false) {
                throw new RuntimeException(
                    'The XLSX parser accepted a file without a lastname column: '
                    . json_encode($result['error'])
                );
            }
            return true;
        },
        'rollback'    => $rollback_contact_import
    ],

    '0108' => [
        'description' => 'Reject a CSV contact import without a contact column.',
        'arrange'     => static function() {
            $suffix = bin2hex(random_bytes(6));

            return [
                'filename' => "contact-import-invalid-{$suffix}.csv",
                'format'   => 'csv',
                'headers'  => ['firstname', 'lastname'],
                'row'      => ['Invalid', 'Headers'],
                'expected' => []
            ];
        },
        'act'         => $run_contact_import,
        'assert'      => static function(array $result): bool {
            if(strpos((string) $result['error'], 'invalid_csv_headers') === false) {
                throw new RuntimeException(
                    'The CSV parser accepted a file without a contact column: '
                    . json_encode($result['error'])
                );
            }
            return true;
        },
        'rollback'    => $rollback_contact_import
    ],

    '0109' => [
        'description' => 'Reject a CSV contact row without a contact value.',
        'arrange'     => static function() {
            $suffix = bin2hex(random_bytes(6));

            return [
                'filename' => "contact-import-invalid-row-{$suffix}.csv",
                'format'   => 'csv',
                'headers'  => ['firstname', 'lastname', 'email'],
                'row'      => ['No Contact', 'Person', ''],
                'expected' => []
            ];
        },
        'act'         => $run_contact_import,
        'assert'      => static function(array $result): bool {
            if(strpos((string) $result['error'], 'invalid_csv_contact') === false) {
                throw new RuntimeException(
                    'The CSV parser accepted a row without a contact value: '
                    . json_encode($result['error'])
                );
            }

            return true;
        },
        'rollback'    => $rollback_contact_import
    ],

    '0110' => [
        'description' => 'Reject an XLSX contact row without a firstname value.',
        'arrange'     => static function() {
            $suffix = bin2hex(random_bytes(6));

            return [
                'filename' => "contact-import-invalid-row-{$suffix}.xlsx",
                'format'   => 'xlsx',
                'headers'  => ['firstname', 'lastname', 'mobile'],
                'row'      => ['', 'Person', '+32470123456'],
                'expected' => []
            ];
        },
        'act'         => $run_contact_import,
        'assert'      => static function(array $result): bool {
            if(strpos((string) $result['error'], 'invalid_xlsx_contact') === false) {
                throw new RuntimeException(
                    'The XLSX parser accepted a row without a firstname value: '
                    . json_encode($result['error'])
                );
            }

            return true;
        },
        'rollback'    => $rollback_contact_import
    ]
];
