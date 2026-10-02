<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2024
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
use core\setting\Setting;
use equal\data\DataFormatter;
use sale\accounting\invoice\SaleInvoice;
use Twig\TwigFilter;
use Twig\Environment as TwigEnvironment;
use Twig\Loader\FilesystemLoader as TwigFilesystemLoader;
use Twig\Extra\Intl\IntlExtension;
use Twig\Extension\ExtensionInterface;

list($params, $providers) = eQual::announce([
    'description'   => 'Generate an html view of given invoice.',
    'params'        => [
        'id' => [
            'description' => 'Identifier of the targeted invoice.',
            'type'        => 'integer',
            'min'         => 1,
            'required'    => true
        ],

        'mode' => [
            'deprecated'  => true,
            'description' => 'Mode in which document has to be rendered: grouped (default) or detailed.',
            'help'        => 'Modes: "simple" displays all lines without groups, "detailed" displays all lines by group and "grouped" displays only groups by vat rate.',
            'type'        => 'string',
            'selection'   => ['simple', 'grouped', 'detailed'],
            'default'     => 'simple'
        ],

        'debug' => [
            'type'        => 'boolean',
            'default'     => false
        ],

        'view_id' => [
            'description' => 'View id of the template to use.',
            'type'        => 'string',
            'default'     => 'print.default'
        ],

        'lang' =>  [
            'description' => 'Language in which labels and multilang field have to be returned (2 letters ISO 639-1).',
            'type'        => 'string'
        ]
    ],
    'access'        => [
        'visibility' => 'protected',
        'groups'     => ['sale.default.users'],
    ],
    'response'      => [
        'content-type'  => 'text/html',
        'charset'       => 'utf-8',
        'accept-origin' => '*'
    ],
    'providers'     => ['context'],
    'constants'     => ['L10N_TIMEZONE', 'L10N_LOCALE']
]);

/** @var \equal\php\Context $context */
$context = $providers['context'];

$getInvoiceLines = function($invoice) {
    $lines = [];

    $map_processed_lines_ids = [];

    foreach($invoice['invoice_line_groups_ids'] as $group) {
        if(count($group['invoice_lines_ids']) <= 0) {
            continue;
        }

        $lines[] = [
            'name'        => $group['name'] ?? '',
            'description' => '',
            'total'       => null,
            'unit_price'  => null,
            'vat_rate'    => null,
            'qty'         => null,
            'free_qty'    => null,
            'is_group'    => true
        ];

        $group_lines = [];
        foreach($group['invoice_lines_ids'] as $line) {

            $map_processed_lines_ids[$line['id']] = true;

            $group_lines[] = [
                'name'        => $line['name'],
                'description' => $line['description'],
                'total'       => round($line['total'], 2),
                'unit_price'  => $line['unit_price'],
                'vat_rate'    => $line['vat_rate'],
                'qty'         => $line['qty'],
                'discount'    => $line['discount'],
                'free_qty'    => $line['free_qty'],
                'is_group'    => false,
                'count_lines' => 0
            ];
        }

        // index of the current "group" line
        $pos = count($lines) - 1;

        //if(!$group['is_aggregate']) {
            $lines[$pos]['count_lines'] = count($group_lines);
            $lines = array_merge($lines, $group_lines);
        //}
    }

    foreach($invoice['invoice_lines_ids'] as $line) {
        if(isset($map_processed_lines_ids[$line['id']])) {
            continue;
        }
        $lines[] = [
            'name'       => (strlen($line['description']) > 0) ? $line['description'] : $line['name'],
            'total'      => round($line['total'], 2),
            'unit_price' => $line['unit_price'],
            'vat_rate'   => $line['vat_rate'],
            'qty'        => $line['qty'],
            'discount'   => $line['discount'],
            'free_qty'   => $line['free_qty'],
            'is_group'   => false
        ];
    }

    return $lines;
};

$getTwigCurrency = function($equal_currency) {
    $equal_twig_currency_map = [
        '€'   => 'EUR',
        '£'   => 'GBP',
        'CHF' => 'CHF',
        '$'   => 'USD'
    ];

    return $equal_twig_currency_map[$equal_currency] ?? $equal_currency;
};

$getOrganizationLogo = function($invoice) {
    $result = '';
    try {
        if(!isset($invoice['organization_id']['image_document_id']['type'], $invoice['organization_id']['image_document_id']['data'])) {
            throw new Exception('invalid_image', EQ_ERROR_INVALID_PARAM);
        }
        if(stripos($invoice['organization_id']['image_document_id']['type'], 'image/') !== 0) {
            throw new Exception('invalid_image_type', EQ_ERROR_INVALID_PARAM);
        }
        if(strlen( $invoice['organization_id']['image_document_id']['data']) <= 0) {
            throw new Exception('empty_image', EQ_ERROR_INVALID_PARAM);
        }
        $result = sprintf('data:%s;base64,%s',
                $invoice['organization_id']['image_document_id']['type'],
                base64_encode($invoice['organization_id']['image_document_id']['data'])
            );
    }
    catch(Exception $e) {
        // ignore
    }
    return $result;
};

$getLabels = function($lang) {
    return [
        'invoice'                        => Setting::get_value('sale', 'locale', 'label_invoice', 'Invoice', [], $lang),
        'credit_note'                    => Setting::get_value('sale', 'locale', 'label_credit-note', 'Credit note', [], $lang),
        'customer_name'                  => Setting::get_value('sale', 'locale', 'label_customer-name', 'Name', [], $lang),
        'customer_address'               => Setting::get_value('sale', 'locale', 'label_customer-address', 'Address', [], $lang),
        'registration_number'            => Setting::get_value('sale', 'locale', 'label_registration-number', 'Registration n°', [], $lang),
        'vat_number'                     => Setting::get_value('sale', 'locale', 'label_vat-number', 'VAT n°', [], $lang),
        'number'                         => Setting::get_value('sale', 'locale', 'label_number', 'N°', [], $lang),
        'date'                           => Setting::get_value('sale', 'locale', 'label_date', 'Date', [], $lang),
        'status'                         => Setting::get_value('sale', 'locale', 'label_status', 'Status', [], $lang),
        'status_paid'                    => Setting::get_value('sale', 'locale', 'label_status-paid', 'Paid', [], $lang),
        'status_to_pay'                  => Setting::get_value('sale', 'locale', 'label_status-to-pay', 'To pay', [], $lang),
        'status_to_refund'               => Setting::get_value('sale', 'locale', 'label_status-to-refund', 'To refund', [], $lang),
        'proforma_notice'                => Setting::get_value('sale', 'locale', 'label_proforma-notice', 'This is a proforma and must not be paid.', [], $lang),
        'total_excl_vat'                 => Setting::get_value('sale', 'locale', 'label_total-ex-vat', 'Total VAT excl.', [], $lang),
        'total_incl_vat'                 => Setting::get_value('sale', 'locale', 'label_total-inc-vat', 'Total VAT incl.', [], $lang),
        'balance_of_must_be_paid_before' => Setting::get_value('sale', 'locale', 'label_balance-of-must-be-paid-before', 'Balance of %price% to be paid before %due_date%', [], $lang),
        'communication'                  => Setting::get_value('sale', 'locale', 'label_communication', 'Communication', [], $lang),
        'columns' => [
            'product'                    => Setting::get_value('sale', 'locale', 'label_product-column', 'Product label', [], $lang),
            'qty'                        => Setting::get_value('sale', 'locale', 'label_qty-column', 'Qty', [], $lang),
            'free'                       => Setting::get_value('sale', 'locale', 'label_free-column', 'Free', [], $lang),
            'unit_price'                 => Setting::get_value('sale', 'locale', 'label_unit-price-column', 'U. price', [], $lang),
            'discount'                   => Setting::get_value('sale', 'locale', 'label_discount-column', 'Disc.', [], $lang),
            'vat'                        => Setting::get_value('sale', 'locale', 'label_vat-column', 'VAT', [], $lang),
            'taxes'                      => Setting::get_value('sale', 'locale', 'label_taxes-column', 'Taxes', [], $lang),
            'price_ex_vat'               => Setting::get_value('sale', 'locale', 'label_price-ex-vat-column', 'Price ex. VAT', [], $lang),
            'price'                      => Setting::get_value('sale', 'locale', 'label_price-column', 'Price', [], $lang)
        ],
        'footer' => [
            'registration_number'        => Setting::get_value('sale', 'locale', 'label_footer-registration-number', 'Registration number', [], $lang),
            'iban'                       => Setting::get_value('sale', 'locale', 'label_footer-iban', 'IBAN', [], $lang),
            'email'                      => Setting::get_value('sale', 'locale', 'label_footer-email', 'Email', [], $lang),
            'web'                        => Setting::get_value('sale', 'locale', 'label_footer-web', 'Web', [], $lang),
            'tel'                        => Setting::get_value('sale', 'locale', 'label_footer-tel', 'Tel', [], $lang),
            'fax'                        => Setting::get_value('sale', 'locale', 'label_footer-fax', 'Fax', [], $lang),
        ]
    ];
};

$getInvoicePaymentQrCodeUri = function($invoice) {
    // default to blank image (empty 1x1)
    $result = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8Xw8AAoMBgDTD2qgAAAAASUVORK5CYII=';
    try {
        if(!isset($invoice['payment_reference'])) {
            throw new Exception('missing_payment_reference', EQ_ERROR_INVALID_PARAM);
        }
        $image = eQual::run('get', 'finance_payment_generate-qr-code', [
                'recipient_name'    => $invoice['organization_id']['legal_name'],
                'recipient_iban'    => $invoice['organization_id']['bank_account_iban'],
                'recipient_bic'     => $invoice['organization_id']['bank_account_bic'],
                'payment_reference' => $invoice['payment_reference'],
                'payment_amount'    => $invoice['price']
            ]);
        $result = sprintf('data:%s;base64,%s',
                'image/png',
                base64_encode($image)
            );
    }
    catch(Exception $e) {
        // ignore
        trigger_error('APP::unable to generate QR code:' . $e->getMessage(), EQ_REPORT_WARNING);
    }
    return $result;
};


$lang = $params['lang'] ?? null;

if(!$lang) {
    $invoice = SaleInvoice::id($params['id'])->read(['customer_id' => ['lang_id' => ['code']]])->first();
    $lang = $invoice['customer_id']['lang_id']['code'];
}

$invoice = SaleInvoice::id($params['id'])
    ->read([
            'invoice_number', 'emission_date', 'due_date', 'status', 'invoice_type', 'payment_reference', 'total', 'price', 'tax_lines', 'payment_status',
            'reversed_invoice_id' => ['invoice_number'],
            'organization_id' => [
                'name', 'address_street', 'address_dispatch', 'address_zip',
                'address_city', 'address_country', 'has_vat', 'vat_number',
                'legal_name', 'registration_number', 'bank_account_iban', 'bank_account_bic',
                'website', 'email', 'phone', 'fax', 'has_vat', 'vat_number',
                'image_document_id' => [
                    'type', 'data'
                ]
            ],
            'customer_id' => [
                'name', 'address_street', 'address_dispatch', 'address_zip',
                'address_city', 'address_country', 'has_vat', 'vat_number'
            ],
            'invoice_lines_ids' => [
                'name', 'product_id', 'description', 'qty', 'unit_price',
                'discount', 'free_qty', 'vat_rate', 'total'
            ],
            'invoice_line_groups_ids' => [
                'name',
                'total',
                'is_aggregate',
                'invoice_lines_ids' => [
                    'name', 'product_id', 'description', 'qty', 'unit_price',
                    'discount', 'free_qty', 'vat_rate', 'total'
                ]
            ]
        ], $lang)
    ->first(true);


if(empty($invoice)) {
    throw new Exception('invoice_unknown', EQ_ERROR_UNKNOWN_OBJECT);
}

// adapt specific properties to TXT output
$invoice['payment_reference'] = DataFormatter::format($invoice['payment_reference'], 'scor');
$invoice['organization_id']['bank_account_iban'] = DataFormatter::format($invoice['organization_id']['bank_account_iban'], 'iban');
$invoice['organization_id']['phone'] = DataFormatter::format($invoice['organization_id']['phone'], 'phone');
$invoice['organization_id']['fax'] = DataFormatter::format($invoice['organization_id']['fax'], 'phone');


$values = [
    'invoice'             => $invoice,
    'organization'        => $invoice['organization_id'],
    'customer'            => $invoice['customer_id'],
    'lines'               => $getInvoiceLines($invoice),
    'organization_logo'   => $getOrganizationLogo($invoice),
    'payment_qr_code_uri' => $getInvoicePaymentQrCodeUri($invoice),
    'timezone'            => constant('L10N_TIMEZONE'),
    'locale'              => constant('L10N_LOCALE'),
    'date_format'         => Setting::get_value('core', 'locale', 'date_format', 'm/d/Y'),
    'currency'            => $getTwigCurrency(Setting::get_value('core', 'locale', 'currency', '€')),
    'labels'              => $getLabels($lang),
    'debug'               => $params['debug'],
    'tax_lines'           => [],
];


// Convert tax references to labels used by the invoice template.
$invoice_tax_lines = json_decode($invoice['tax_lines'], true, 512, JSON_THROW_ON_ERROR);
foreach($invoice_tax_lines as $tax_ref => $tax_line) {
    $vat_rate = (float) $tax_ref;
    // #todo - use a translated label
    $tax_label = 'TVA '.strval( intval($vat_rate * 100) ).'%';
    $values['tax_lines'][$tax_label] = $tax_line['tax_amount'];
}

try {
    // generate HTML
    $loader = new TwigFilesystemLoader(EQ_BASEDIR.'/packages/sale/views/accounting/invoice');
    $twig = new TwigEnvironment($loader);

    /** @var ExtensionInterface $extension **/
    $extension  = new IntlExtension();
    $twig->addExtension($extension);

    // #todo - temp workaround against LOCALE mixups
    $twig->addFilter(
            new TwigFilter('format_money', function ($value) {
                return number_format((float) $value, 2, ",", ".").' €';
            })
        );

    $template = $twig->load('invoice.'.$params['view_id'].'.html');
    $html = $template->render($values);
}
catch(Exception $e) {
    trigger_error('APP::Error while rendering template'.$e->getMessage(), EQ_ERROR_INVALID_CONFIG);
    throw new Exception($e->getMessage(), EQ_ERROR_INVALID_CONFIG);
}

$context->httpResponse()
    ->body($html)
    ->send();
