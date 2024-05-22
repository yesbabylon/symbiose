<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2024
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

use core\setting\Setting;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\QrCode;
use equal\data\DataFormatter;
use sale\accounting\invoice\Invoice;
use SepaQr\Data;
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
            'description' => 'Mode in which document has to be rendered: grouped (default) or detailed.',
            'help'        => 'Modes: "simple" displays all lines without groups, "detailed" displays all lines by group and "grouped" displays only groups by vat.',
            'type'        => 'string',
            'selection'   => ['simple', 'grouped', 'detailed'],
            'default'     => 'simple'
        ],

        'view_id' => [
            'description' => 'View id of the template to use.',
            'type'        => 'string',
            'default'     => 'template'
        ],

        'lang' =>  [
            'description' => 'Language in which labels and multilang field have to be returned (2 letters ISO 639-1).',
            'type'        => 'string',
            'default'     => constant('DEFAULT_LANG')
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

$generateInvoiceLines = function($invoice, $mode) {
    $lines = [];

    foreach($invoice['invoice_line_groups_ids'] as $group) {
        if(count($group['invoice_lines_ids']) <= 0) {
            continue;
        }

        if($mode !== 'simple') {
            $lines[] = [
                'name'       => $group['name'] ?? '',
                'price'      => null,
                'total'      => null,
                'unit_price' => null,
                'vat_rate'   => null,
                'qty'        => null,
                'free_qty'   => null,
                'is_group'   => true
            ];
        }

        $group_lines = [];
        foreach($group['invoice_lines_ids'] as $line) {
            foreach($invoice['invoice_lines_ids'] as $i => $l) {
                if($l['id'] === $line['id']) {
                    unset($invoice['invoice_lines_ids'][$i]);
                    break;
                }
            }

            $group_lines[] = [
                'name'       => (strlen($line['description']) > 0) ? $line['description'] : $line['name'],
                'price'      => round(($invoice['invoice_type'] == 'credit_note') ? (-$line['price']) : $line['price'], 2),
                'total'      => round(($invoice['invoice_type'] == 'credit_note') ? (-$line['total']) : $line['total'], 2),
                'unit_price' => $line['unit_price'],
                'vat_rate'   => $line['vat_rate'],
                'qty'        => $line['qty'],
                'discount'   => $line['discount'],
                'free_qty'   => $line['free_qty'],
                'is_group'   => false
            ];
        }

        switch($mode) {
            case 'simple':
            case 'detailed':
                $lines = array_merge($lines, $group_lines);
                break;
            case 'grouped':
                $group_tax_lines = [];

                foreach($group_lines as $line) {
                    $vat_rate = strval(round($line['vat_rate'], 2));

                    if(!isset($group_tax_lines[$vat_rate])) {
                        $group_tax_lines[$vat_rate] = [];
                    }

                    $group_tax_lines[$vat_rate][] = $line;
                }

                $nb_taxes = count(array_keys($group_tax_lines));
                if($nb_taxes == 1) {
                    $pos = count($lines) - 1;
                    foreach($group_tax_lines as $vat_rate => $tax_lines) {
                        $lines[$pos]['qty'] = 1;
                        $lines[$pos]['vat_rate'] = $vat_rate;
                        foreach($tax_lines as $tax_line) {
                            $lines[$pos]['total'] += $tax_line['total'];
                            $lines[$pos]['price'] += $tax_line['price'];
                        }
                    }
                }
                elseif($nb_taxes > 1) {
                    foreach($group_tax_lines as $vat_rate => $tax_lines) {
                        $lines[] = [
                            'name'     => 'VAT '.($vat_rate * 100).'%',
                            'qty'      => 1,
                            'vat_rate' => $vat_rate,
                            'price'    => round(array_sum(array_column($tax_lines, 'price')), 2),
                            'total'    => round(array_sum(array_column($tax_lines, 'total')), 2)
                        ];
                    }
                }
                break;
        }
    }

    foreach($invoice['invoice_lines_ids'] as $line) {
        $lines[] = [
            'name'       => (strlen($line['description']) > 0) ? $line['description'] : $line['name'],
            'price'      => round(($invoice['invoice_type'] == 'credit_note') ? (-$line['price']) : $line['price'], 2),
            'total'      => round(($invoice['invoice_type'] == 'credit_note') ? (-$line['total']) : $line['total'], 2),
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

$getOrganisationLogo = function($invoice) {
    $organisation_logo = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8Xw8AAoMBgDTD2qgAAAAASUVORK5CYII=';
    if(
        isset(
            $invoice['organisation_id']['image_document_id']['type'],
            $invoice['organisation_id']['image_document_id']['data']
        )
    ) {
        if(strpos($invoice['organisation_id']['image_document_id']['type'], 'image/') !== 0) {
            throw new Exception('invalid_organisation_invoice_image', QN_ERROR_INVALID_PARAM);
        }

        $organisation_logo = sprintf(
            'data:%s;base64,%s',
            $invoice['organisation_id']['image_document_id']['type'],
            base64_encode($invoice['organisation_id']['image_document_id']['data'])
        );
    }

    return $organisation_logo;
};

$getLabels = function($lang) {
    return [
        'invoice'                        => Setting::get_value('sale', 'invoice', 'labels.invoice', 'Invoice', 0, $lang),
        'credit_note'                    => Setting::get_value('sale', 'invoice', 'labels.credit-note', 'Credit note', 0, $lang),
        'customer_name'                  => Setting::get_value('sale', 'invoice', 'labels.customer-name', 'Name', 0, $lang),
        'customer_address'               => Setting::get_value('sale', 'invoice', 'labels.customer-address', 'Address', 0, $lang),
        'registration_number'            => Setting::get_value('sale', 'invoice', 'labels.registration-number', 'Registration n°', 0, $lang),
        'vat_number'                     => Setting::get_value('sale', 'invoice', 'labels.vat-number', 'VAT n°', 0, $lang),
        'number'                         => Setting::get_value('sale', 'invoice', 'labels.number', 'N°', 0, $lang),
        'date'                           => Setting::get_value('sale', 'invoice', 'labels.date', 'Date', 0, $lang),
        'status'                         => Setting::get_value('sale', 'invoice', 'labels.status', 'Status', 0, $lang),
        'status_paid'                    => Setting::get_value('sale', 'invoice', 'labels.status-paid', 'Paid', 0, $lang),
        'status_to_pay'                  => Setting::get_value('sale', 'invoice', 'labels.status-to-pay', 'To pay', 0, $lang),
        'status_to_refund'               => Setting::get_value('sale', 'invoice', 'labels.status-to-refund', 'To refund', 0, $lang),
        'columns'                        => [
            'product'      => Setting::get_value('sale', 'invoice', 'labels.product-column', 'Product label', 0, $lang),
            'qty'          => Setting::get_value('sale', 'invoice', 'labels.qty-column', 'Qty', 0, $lang),
            'free'         => Setting::get_value('sale', 'invoice', 'labels.free-column', 'Free', 0, $lang),
            'unit_price'   => Setting::get_value('sale', 'invoice', 'labels.unit-price-column', 'U. price', 0, $lang),
            'discount'     => Setting::get_value('sale', 'invoice', 'labels.discount-column', 'Disc.', 0, $lang),
            'vat'          => Setting::get_value('sale', 'invoice', 'labels.vat-column', 'VAT', 0, $lang),
            'taxes'        => Setting::get_value('sale', 'invoice', 'labels.taxes-column', 'Taxes', 0, $lang),
            'price_ex_vat' => Setting::get_value('sale', 'invoice', 'labels.price-ex-vat-column', 'Price ex. VAT', 0, $lang),
            'price'        => Setting::get_value('sale', 'invoice', 'labels.price-column', 'Price', 0, $lang)
        ],
        'total_ex_vat'                   => Setting::get_value('sale', 'invoice', 'labels.total-ex-vat', 'Total ex. VAT', 0, $lang),
        'total_inc_vat'                  => Setting::get_value('sale', 'invoice', 'labels.total-inc-vat', 'Total inc. VAT', 0, $lang),
        'footer'                         => [
            'registration_number' => Setting::get_value('sale', 'invoice', 'labels.footer-registration-number', 'Registration number', 0, $lang),
            'iban'                => Setting::get_value('sale', 'invoice', 'labels.footer-iban', 'IBAN', 0, $lang),
            'email'               => Setting::get_value('sale', 'invoice', 'labels.footer-email', 'Email', 0, $lang),
            'web'                 => Setting::get_value('sale', 'invoice', 'labels.footer-web', 'Web', 0, $lang),
            'tel'                 => Setting::get_value('sale', 'invoice', 'labels.footer-tel', 'Tel', 0, $lang),
            'fax'                 => Setting::get_value('sale', 'invoice', 'labels.footer-fax', 'Fax', 0, $lang),
        ],
        'balance_of_must_be_paid_before' => Setting::get_value('sale', 'invoice', 'labels.balance-of-must-be-paid-before', 'Balance of %price% must be paid before %due_date%', 0, $lang),
        'communication'                  => Setting::get_value('sale', 'invoice', 'labels.communication', 'Communication', 0, $lang)
    ];
};

$getInvoice = function($id, $lang) {
    $invoice_parties_fields = [
        'name', 'address_street', 'address_dispatch', 'address_zip',
        'address_city', 'address_country', 'has_vat', 'vat_number'
    ];

    $invoice_organisation_fields = [
        'legal_name', 'registration_number', 'bank_account_iban', 'bank_account_bic',
        'website', 'email', 'phone', 'fax', 'has_vat', 'vat_number',
        'image_document_id' => ['type', 'data']
    ];

    $invoice_lines_fields = [
        'name', 'product_id', 'description', 'qty', 'unit_price',
        'discount', 'free_qty', 'vat_rate', 'total', 'price'
    ];

    $invoice_line_groups_fields = [
        'name',
        'invoice_lines_ids' => $invoice_lines_fields
    ];

    return Invoice::id($id)
        ->read(
            [
                'name', 'date', 'due_date', 'status', 'invoice_type', 'payment_reference', 'total', 'price', 'payment_status',
                'organisation_id'         => array_merge($invoice_parties_fields, $invoice_organisation_fields),
                'customer_id'             => $invoice_parties_fields,
                'invoice_lines_ids'       => $invoice_lines_fields,
                'invoice_line_groups_ids' => $invoice_line_groups_fields
            ],
            $lang
        )
        ->first(true);
};

$createInvoicePaymentQrCodeUri = function($invoice) {
    $payment_qr_code_uri = '';

    if(!isset($invoice['payment_reference'])) {
        throw new Exception('no payment ref');
    }

    $payment_reference = DataFormatter::format($invoice['payment_reference'], 'scor');
    try {
        $payment_data = Data::create()
            ->setServiceTag('BCD')
            ->setIdentification('SCT')
            ->setName($invoice['organisation_id']['legal_name'])
            ->setIban(str_replace(' ', '', $invoice['organisation_id']['bank_account_iban']))
            ->setBic(str_replace(' ', '', $invoice['organisation_id']['bank_account_bic']))
            ->setRemittanceReference($payment_reference)
            ->setAmount($invoice['price']);

        $qr_code = new QrCode($payment_data);
        $qr_code->setErrorCorrectionLevel(ErrorCorrectionLevel::MEDIUM()); // required by EPC standard

        $payment_qr_code_uri = $qr_code->writeDataUri();
    } catch(Exception $e) {
        trigger_error("Unable to generate payment QR code: {$e->getMessage()}", EQ_REPORT_WARNING);
    }

    return $payment_qr_code_uri;
};

$formatInvoice = function(&$invoice) {
    $invoice['payment_reference'] = DataFormatter::format($invoice['payment_reference'], 'scor');

    $invoice['organisation_id']['bank_account_iban'] = DataFormatter::format($invoice['organisation_id']['bank_account_iban'], 'iban');
    $invoice['organisation_id']['phone'] = DataFormatter::format($invoice['organisation_id']['phone'], 'phone');
    $invoice['organisation_id']['fax'] = DataFormatter::format($invoice['organisation_id']['fax'], 'phone');
};

$invoice = $getInvoice($params['id'], $params['lang']);
if(empty($invoice)) {
    throw new Exception('invoice_unknown', QN_ERROR_UNKNOWN_OBJECT);
}

$lines = $generateInvoiceLines($invoice, $params['mode']);
$organisation_logo = $getOrganisationLogo($invoice);
$payment_qr_code_uri = $createInvoicePaymentQrCodeUri($invoice);

$formatInvoice($invoice);

$loader = new TwigFilesystemLoader(QN_BASEDIR.'/packages/sale/views/accounting/invoice');
$twig = new TwigEnvironment($loader);

/** @var ExtensionInterface $extension **/
$extension  = new IntlExtension();
$twig->addExtension($extension);

$template = $twig->load($params['view_id'].'.html.twig');

$html = $template->render([
    'invoice'             => $invoice,
    'lines'               => $lines,
    'organisation_logo'   => $organisation_logo,
    'payment_qr_code_uri' => $payment_qr_code_uri,
    'timezone'            => constant('L10N_TIMEZONE'),
    'locale'              => constant('L10N_LOCALE'),
    'date_format'         => Setting::get_value('core', 'locale', 'date_format', 'm/d/Y'),
    'currency'            => $getTwigCurrency(Setting::get_value('core', 'units', 'currency', '€')),
    'labels'              => $getLabels($params['lang'])
]);

$context->httpResponse()
        ->body($html)
        ->send();
