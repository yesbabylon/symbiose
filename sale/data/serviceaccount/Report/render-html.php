<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2024
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

use sale\serviceaccount\Report;
use symbiose\setting\Setting;
use Twig\Environment as TwigEnvironment;
use Twig\Loader\FilesystemLoader as TwigFilesystemLoader;

[$params, $providers] = eQual::announce([
    'description' => 'Generate an HTML view of a service account report.',
    'params'      => [
        'id' => [
            'description'    => 'Identifier of the report to render.',
            'type'           => 'many2one',
            'foreign_object' => 'sale\serviceaccount\Report',
            'required'       => true
        ],
        'details' => [
            'description' => 'Display the details of time entries.',
            'type'        => 'boolean',
            'default'     => true
        ],
        'logs' => [
            'description' => 'Display point calculation logs.',
            'type'        => 'boolean',
            'default'     => false
        ],
        'view_id' => [
            'description' => 'View id of the template to use.',
            'type'        => 'string',
            'default'     => 'print.default'
        ],
        'lang' => [
            'description' => 'Language in which labels and multilang fields are rendered (2 letters ISO 639-1).',
            'type'        => 'string'
        ]
    ],
    'access' => [
        'visibility' => 'protected',
        'groups'     => ['sale.default.users']
    ],
    'response' => [
        'accept-origin' => '*',
        'content-type'  => 'text/html',
        'charset'       => 'utf-8'
    ],
    'providers' => ['context'],
    'constants' => ['DEFAULT_LANG', 'L10N_TIMEZONE']
]);

/** @var \equal\php\Context $context */
['context' => $context] = $providers;

$language_report = Report::id($params['id'])
    ->read([
        'id',
        'service_account_id' => [
            'customer_id' => [
                'lang_id' => ['code']
            ]
        ]
    ])
    ->first();

if(empty($language_report)) {
    throw new Exception('unknown_report', EQ_ERROR_UNKNOWN_OBJECT);
}

$lang = isset($params['lang']) && strlen(trim((string) $params['lang'])) > 0
    ? $params['lang']
    : ($language_report['service_account_id']['customer_id']['lang_id']['code'] ?? constant('DEFAULT_LANG'));
$lang = strtolower(trim((string) $lang));

if(!preg_match('/^[a-z]{2}$/', $lang)) {
    $lang = strtolower((string) constant('DEFAULT_LANG'));
}

$default_labels = [
    'report.title'                        => 'Service Account Report',
    'report.header.contract'              => 'Contract',
    'report.header.category'              => 'Category',
    'report.header.report'                => 'Report',
    'report.header.created_on'            => 'Created on',
    'report.header.previous_balance'      => 'Previous balance',
    'report.header.new_balance'           => 'New balance',
    'report.header.customer_name'         => 'Name',
    'report.header.customer_address'      => 'Address',
    'report.header.customer_vat_number'   => 'VAT no.',
    'report.services.title'               => 'Services details',
    'report.services.column.ticket'       => 'Ticket',
    'report.services.column.day'          => 'Day',
    'report.services.column.start'        => 'Start',
    'report.services.column.stop'         => 'Stop',
    'report.services.column.pause'        => 'Pause',
    'report.services.column.on_site'      => 'Onsite',
    'report.services.column.who'          => 'Who',
    'report.services.column.role'         => 'Role',
    'report.services.column.type'         => 'Type',
    'report.services.column.project'      => 'Project',
    'report.services.column.points'       => '#',
    'report.services.empty'               => 'No services for this period.',
    'report.services.total'               => 'Total services for period',
    'report.miscellaneous.title'          => 'Miscellaneous',
    'report.miscellaneous.column.date'    => 'Date',
    'report.miscellaneous.column.who'     => 'Who',
    'report.miscellaneous.column.details' => 'Description',
    'report.miscellaneous.column.points'  => '#',
    'report.miscellaneous.empty'          => 'No credits or corrections for this period.',
    'report.miscellaneous.total'          => 'Total credit & corrections for period',
    'report.balance.title'                => 'Service Account Balance',
    'report.balance.initial'              => 'Initial Balance',
    'report.balance.services'             => 'Total Services for period (details on previous pages)',
    'report.balance.credits'              => 'Total Credits & Corrections for period',
    'report.balance.new'                  => 'New balance',
    'report.common.yes'                   => 'Yes',
    'report.common.no'                    => 'No',
    'report.fallback.ticket'              => 'Ticket',
    'report.fallback.time_entry'          => 'Time entry',
    'report.footer.phone'                 => 'Tel.',
    'report.footer.email'                 => 'Email',
    'report.footer.website'               => 'Web',
    'report.footer.registration_number'   => 'Registration no.',
    'report.footer.vat_number'            => 'VAT no.',
    'report.footer.not_applicable'        => 'N/A'
];

$labels_path = sprintf(
    '%s/packages/sale/i18n/%s/serviceaccount/Report.%s.json',
    EQ_BASEDIR,
    $lang,
    $params['view_id']
);

$getLabels = function(string $lang, string $view_labels_path, array $default_labels = []): array {
    $readLabels = function(string $path): array {
        if(!file_exists($path)) {
            return [];
        }

        $content = file_get_contents($path);
        $labels = $content === false ? null : json_decode($content, true);

        if(!is_array($labels)) {
            trigger_error('APP::Invalid service account report labels file: '.$path, EQ_REPORT_WARNING);
            return [];
        }

        return $labels;
    };

    $parts_labels_path = sprintf(
        '%s/packages/sale/i18n/%s/serviceaccount/_parts',
        EQ_BASEDIR,
        $lang
    );

    return array_merge(
        $default_labels,
        $readLabels($parts_labels_path.'/header.json'),
        $readLabels($parts_labels_path.'/footer.json'),
        $readLabels($view_labels_path)
    );
};

$labels = $getLabels($lang, $labels_path, $default_labels);

$report = Report::id($params['id'])
    ->read([
        'id',
        'created',
        'date',
        'status',
        'date_from',
        'name',
        'total_points',
        'total_credits',
        'balance_old',
        'balance_new',
        'service_account_id' => [
            'id',
            'name',
            'customer_id' => [
                'name',
                'customer_external_ref',
                'ref_account',
                'address_street',
                'address_dispatch',
                'address_zip',
                'address_city',
                'address_country',
                'has_vat',
                'vat_number',
                'organization_id' => [
                    'name',
                    'legal_name',
                    'registration_number',
                    'has_vat',
                    'vat_number',
                    'address_street',
                    'address_dispatch',
                    'address_zip',
                    'address_city',
                    'address_country',
                    'phone',
                    'email',
                    'website',
                    'image_document_id' => ['type', 'data']
                ]
            ],
            'contract_type_id' => ['name']
        ],
        'service_account_entries_ids' => [
            'date',
            'start',
            'end',
            'pause',
            'on_site',
            'description',
            'contact',
            'points',
            'calculation_log',
            'creator'       => ['firstname', 'lastname'],
            'employee_id'   => ['name', 'firstname', 'lastname'],
            'role_id'       => ['name', 'code'],
            'receivable_id' => [
                'origin_object_class',
                'origin_object_id',
                'name',
                'description',
                'time_entry_id' => ['origin', 'project_id' => ['name'], 'ticket_id', 'reference', 'description', 'detailed_description']
            ]
        ]
    ], $lang)
    ->first();

if(empty($report)) {
    throw new Exception('unknown_report', EQ_ERROR_UNKNOWN_OBJECT);
}

$timezone = Setting::get_value('core', 'locale', 'time.zone', constant('L10N_TIMEZONE'));

$format_time = function($value) {
    $hours = floor($value / 3600);
    $minutes = floor(($value % 3600) / 60);
    return sprintf('%02d:%02d', $hours, $minutes);
};

$format_month = function($timestamp) use($lang, $timezone) {
    if(class_exists(IntlDateFormatter::class)) {
        try {
            $formatter = new IntlDateFormatter(
                $lang,
                IntlDateFormatter::NONE,
                IntlDateFormatter::NONE,
                $timezone,
                IntlDateFormatter::GREGORIAN,
                'MMMM yyyy'
            );
            $formatted = $formatter->format($timestamp);
            if($formatted !== false) {
                return ucfirst($formatted);
            }
        }
        catch(Throwable $throwable) {
            // Fall through to the language-independent PHP fallback.
        }
    }
    return date('F Y', $timestamp);
};

$map_tt_lines = [];
$map_cc_lines = [];

foreach($report['service_account_entries_ids'] as $line) {
    $receivable = $line['receivable_id'] ?? [];

    if($line['points'] < 0) {
        $firstname = $line['creator']['firstname'] ?? '';
        $lastname = $line['creator']['lastname'] ?? '';
        $who = trim($firstname.' '.(strlen($lastname) ? substr($lastname, 0, 1).'.' : ''));
        $description = trim($line['description'] ?? '');

        $map_cc_lines[] = [
            'date'        => date('d/m/Y', (int) $line['date']),
            'who'         => $who,
            'description' => ucfirst(substr(str_replace(['<p>', '</p>', '<br />'], ['', '', ' ; '], $description), 0, 128)),
            'points'      => number_format((float) round($line['points'], 2), 2, '.', '')
        ];
    }
    elseif(($receivable['origin_object_class'] ?? null) === 'timetrack\TimeEntry') {
        $time_entry = $receivable['time_entry_id'] ?? [];
        $ticket_id = $time_entry['ticket_id'] ?? null;
        $parent_id = $ticket_id
            ? 'ticket-'.$ticket_id
            : 'time-entry-'.($receivable['origin_object_id'] ?? 0);

        $description = $time_entry['description']
            ?? $time_entry['reference']
            ?? $line['description']
            ?? '';

        if(strlen($description) <= 0) {
            $description = $ticket_id
                ? $labels['report.fallback.ticket'].' '.$ticket_id
                : $labels['report.fallback.time_entry'];
        }

        $contact = (string) ($line['contact'] ?? '');
        if(!isset($map_tt_lines[$parent_id])) {
            $map_tt_lines[$parent_id] = [
                'description' => substr($description, 0, 175),
                'contact'     => $contact,
                'lines'       => []
            ];
        }
        else {
            if(strlen($map_tt_lines[$parent_id]['contact']) <= 0 && strlen($contact) > 0) {
                $map_tt_lines[$parent_id]['contact'] = $contact;
            }
            if(strlen($map_tt_lines[$parent_id]['description']) <= 0 && strlen($description) > 0) {
                $map_tt_lines[$parent_id]['description'] = $description;
            }
        }

        $start = (int) ($line['start'] ?? 0);
        $end = (int) ($line['end'] ?? 0);

        $firstname = $line['employee_id']['firstname'] ?? '';
        $lastname = $line['employee_id']['lastname'] ?? '';

        $line_description = ($time_entry['detailed_description'] ?? $line['description'] ?? $description);
        $calculation_log = ($line['calculation_log'] ?? '');

        $map_tt_lines[$parent_id]['lines'][] = [
            'date'        => date('d/m/Y', (int) $line['date']),
            'start'       => $format_time($start - strtotime('midnight', $start)),
            'end'         => $format_time($end - strtotime('midnight', $end)),
            'pause'       => $format_time(floor(abs((float) $line['pause']) * 60) * 60),
            'on_site'     => !empty($line['on_site']) ? $labels['report.common.yes'] : $labels['report.common.no'],
            'who'         => trim($firstname . ' ' . (strlen($lastname) ? substr($lastname, 0, 1) . '.' : '')),
            'role'        => $line['role_id']['code'] ?? 'DEV',
            'type'        => $time_entry['origin'] ?? '',
            'project'     => $time_entry['project_id']['name'],
            'points'      => number_format((float) round(-$line['points'], 2), 2, '.', ''),
            'description' => ucfirst(substr(str_replace('<br />', ' ; ', strip_tags($line_description)), 0, 450)) . '[…]',
            'log'         => str_replace('<br />', ' ; ', $calculation_log)
        ];
    }
    else {
        $description = trim($receivable['description'] ?? $receivable['name'] ?? '');

        $description = trim(strip_tags(str_replace(
            ['<br>', '<br/>', '<br />', '</p>'],
            [' ; ', ' ; ', ' ; ', ' '],
            $description
        )));

        $firstname = $line['employee_id']['firstname'] ?? '';
        $lastname = $line['employee_id']['lastname'] ?? '';

        $map_tt_lines[] = [
            'description' => ucfirst(substr($description, 0, 128)),
            'contact'     => '',
            'lines'       => [[
                'date'        => date('d/m/Y', (int) $line['date']),
                'start'       => '',
                'end'         => '',
                'pause'       => '',
                'who'         => trim($firstname . ' ' . (strlen($lastname) ? substr($lastname, 0, 1) . '.' : '')),
                'role'        => '',
                'project'     => '',
                'type'        => '',
                'points'      => number_format((float) round(-$line['points'], 2), 2, '.', ''),
                'description' => $description,
                'log'         => ''
            ]]
        ];
    }
}

$service_account = $report['service_account_id'] ?? null;
$customer = $service_account['customer_id'] ?? null;
$customer_name = $customer['name'] ?? '';
$customer_ref = $customer['customer_external_ref'] ?? ($customer['ref_account'] ?? '');
$customer_label = $customer_name.(strlen($customer_ref) ? ' ['.$customer_ref.']' : '');
$recipient_data = [
    'name'             => $customer_name,
    'address_street'   => $customer['address_street'] ?? '',
    'address_dispatch' => $customer['address_dispatch'] ?? '',
    'address_zip'      => $customer['address_zip'] ?? '',
    'address_city'     => $customer['address_city'] ?? '',
    'address_country'  => $customer['address_country'] ?? '',
    'has_vat'          => (bool) ($customer['has_vat'] ?? false),
    'vat_number'       => $customer['vat_number'] ?? ''
];

$organization_data = [];
$organization = $customer['organization_id'] ?? null;

if($organization) {
    $organization_name = (string) ($organization['legal_name'] ?? '');
    if(strlen($organization_name) <= 0) {
        $organization_name = (string) ($organization['name'] ?? '');
    }

    $organization_data = [
        'name'                => $organization_name,
        'registration_number' => $organization['registration_number'] ?? '',
        'has_vat'             => (bool) ($organization['has_vat'] ?? false),
        'vat_number'          => $organization['vat_number'] ?? '',
        'address_street'      => $organization['address_street'] ?? '',
        'address_dispatch'    => $organization['address_dispatch'] ?? '',
        'address_zip'         => $organization['address_zip'] ?? '',
        'address_city'        => $organization['address_city'] ?? '',
        'address_country'     => $organization['address_country'] ?? '',
        'phone'               => $organization['phone'] ?? '',
        'email'               => $organization['email'] ?? '',
        'website'             => $organization['website'] ?? '',
        'logo'                => ''
    ];

    $logo_document = $organization['image_document_id'] ?? null;
    $logo_type = $logo_document['type'] ?? '';
    $logo_data = $logo_document['data'] ?? '';

    if(stripos($logo_type, 'image/') === 0 && strlen($logo_data) > 0) {
        $organization_data['logo'] = sprintf('data:%s;base64,%s', $logo_type, base64_encode($logo_data));
    }
}

$values = [
    'lang'            => $lang,
    'labels'          => $labels,
    'name'            => $report['name'],
    'service_account' => $service_account['name'] ?? '',
    'balance_old'     => (($report['balance_old'] >= 0) ? '+' : '').number_format((float) round($report['balance_old'], 2), 2, '.', ''),
    'balance_new'     => (($report['balance_new'] >= 0) ? '+' : '').number_format((float) round($report['balance_new'], 2), 2, '.', ''),
    'customer'        => $customer_label,
    'recipient'       => $recipient_data,
    'account_type'    => $service_account['contract_type_id']['name'] ?? '',
    'emission_date'   => date('d/m/Y', $report['created']),
    'period'          => $format_month($report['date']),
    'period_dates'    => date('d/m/Y', $report['date_from']).' - '.date('d/m/Y', $report['date']),
    'tickets'         => $map_tt_lines,
    'credits'         => $map_cc_lines,
    'total_tickets'   => number_format((float) round($report['total_points'], 2), 2, '.', ''),
    'total_credits'   => (($report['total_credits'] >= 0) ? '+' : '').number_format((float) round($report['total_credits'], 2), 2, '.', ''),
    'show_details'    => (bool) $params['details'],
    'show_logs'       => (bool) $params['logs'],
    'organization'    => $organization_data
];

try {
    $loader = new TwigFilesystemLoader([
        EQ_BASEDIR.'/packages/sale/views/serviceaccount/_parts',
        EQ_BASEDIR.'/packages/sale/views/serviceaccount'
    ]);
    $twig = new TwigEnvironment($loader);
    $template = $twig->load('Report.'.$params['view_id'].'.html');
    $html = $template->render($values);
}
catch(Throwable $throwable) {
    trigger_error('APP::Unable to render service account report - '.$throwable->getMessage(), EQ_REPORT_ERROR);
    throw new Exception('report_rendering_failed', EQ_ERROR_INVALID_CONFIG);
}

$context->httpResponse()
    ->body($html)
    ->send();
