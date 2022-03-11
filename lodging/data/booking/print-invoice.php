<?php
/*
    This file is part of the eQual framework <http://www.github.com/cedricfrancoys/equal>
    Some Rights Reserved, Cedric Francoys, 2010-2021
    Licensed under GNU LGPL 3 license <http://www.gnu.org/licenses/>
*/
use Dompdf\Dompdf;
use Dompdf\Options as DompdfOptions;
use Twig\Environment as TwigEnvironment;
use Twig\Loader\FilesystemLoader as TwigFilesystemLoader;
use Twig\Extra\Intl\IntlExtension;
use Twig\Extension\ExtensionInterface;

use SepaQr\Data;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelMedium;

use lodging\sale\booking\Invoice;
use sale\booking\Funding;
use communication\Template;
use communication\TemplatePart;
use communication\TemplateAttachment;
use equal\data\DataFormatter;


list($params, $providers) = announce([
    'description'   => "Returns a view populated with a collection of objects and outputs it as a PDF document.",
    'params'        => [
        'id' => [
            'description'   => 'Identitifier of the invoice to print.',
            'type'          => 'integer',
            'required'      => true
        ],
        'view_id' =>  [
            'description'   => 'The identifier of the view <type.name>.',
            'type'          => 'string',
            'default'       => 'print.default'
        ],
        'mode' =>  [
            'description'   => 'Mode in which document has to be rendered: simple (default) or detailed.',
            'type'          => 'string',
            'selection'     => ['simple', 'detailed'],
            'default'       => 'simple'
        ],
        'lang' =>  [
            'description'   => 'Language in which labels and multilang field have to be returned (2 letters ISO 639-1).',
            'type'          => 'string',
            'default'       => DEFAULT_LANG
        ]
    ],
    'access' => [
        'visibility'        => 'public',		// 'public' (default) or 'private' (can be invoked by CLI only)	
        'groups'            => ['booking.default.administrator'],// list of groups ids or names granted 
    ],
    'response'      => [
        'content-type'      => 'application/pdf',
        'accept-origin'     => '*'
    ],
    'providers'     => ['context', 'orm']
]);


list($context, $orm) = [$providers['context'], $providers['orm']];

/*
    Retrieve the requested template
*/

$entity = 'lodging\sale\booking\Invoice';
$parts = explode('\\', $entity);
$package = array_shift($parts);
$class_path = implode('/', $parts);
$parent = get_parent_class($entity);

$file = QN_BASEDIR."/packages/{$package}/views/{$class_path}.{$params['view_id']}.html";

if(!file_exists($file)) {
    throw new Exception("unknown_view_id", QN_ERROR_UNKNOWN_OBJECT);
}


$loader = new TwigFilesystemLoader(QN_BASEDIR."/packages/{$package}/views/");

$twig = new TwigEnvironment($loader);
/**  @var ExtensionInterface **/
$extension  = new IntlExtension();
$twig->addExtension($extension);

$twigTemplate = $twig->load("{$class_path}.{$params['view_id']}.html");

// read invoice
$fields = [
    'name',
    'created',
    'partner_id' => [
        'partner_identity_id' => [
            'id',
            'display_name',
            'type',
            'address_street', 'address_dispatch', 'address_city', 'address_zip',
            'type',
            'phone',
            'email',
            'has_vat',
            'vat_number'
        ]
    ],
    'booking_id' => [
        'name',
        'modified',
        'date_from',
        'date_to',
        'price',
        'center_id' => [
            'name',
            'manager_id' => ['name'],
            'address_street',
            'address_city',
            'address_zip',
            'phone',
            'email',
            'bank_account_iban',
            'bank_account_bic',
            'template_category_id',
            'use_office_details',
            'center_office_id' => [
                'code',
                'address_street',
                'address_city',
                'address_zip',
                'phone',
                'email',
                'signature',
                'bank_account_iban',
                'bank_account_bic'
            ],
            'organisation_id' => [
                'id',
                'legal_name',
                'address_street', 'address_zip', 'address_city',
                'email',
                'phone',
                'fax',
                'website',
                'registration_number',
                'signature',
                'bank_account_iban',
                'bank_account_bic'
            ]
        ],
        'contacts_ids' => [
            'type',
            'partner_identity_id' => [
                'display_name',
                'phone',
                'email',
                'title'
            ]
        ]
    ],
    'invoice_lines_ids' => [
        'product_id',
        'qty',
        'unit_price',
        'discount',
        'free_qty',
        'vat_rate',
        'total',
        'price'
    ],
    'invoice_line_groups_ids' => [
        'name',
        'invoice_lines_ids' => [
            'product_id',
            'qty',
            'unit_price',
            'discount',
            'free_qty',
            'vat_rate',
            'total',
            'price'
        ]
    ],
    'funding_id' => ['payment_reference', 'due_date'],
    'total',
    'price'
];


$invoice = Invoice::id($params['id'])->read($fields)->first();

if(!$invoice) {
    throw new Exception("unknown_invoice", QN_ERROR_UNKNOWN_OBJECT);
}


/*
    extract required data and compose the $value map for the twig template
*/

$booking = $invoice['booking_id'];


// set header image based on the organisation of the center
$img_path = 'public/assets/img/brand/Kaleo.png';
$img_url = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8Xw8AAoMBgDTD2qgAAAAASUVORK5CYII=';

if($booking['center_id']['organisation_id']['id'] == 2) {
    $img_path = 'public/assets/img/brand/Villers.png';
}
else if($booking['center_id']['organisation_id']['id'] == 3) {
    $img_path = 'public/assets/img/brand/Mozaik.png';
}

if(file_exists($img_path)) {
    $img = file_get_contents($img_path);
    $img_url = "data:image/png;base64, ".base64_encode($img);
}


$values = [
    'header_img_url'        => $img_url,
    'invoice_header_html'  => '',
    'invoice_notice_html'  => '',

    'customer_name'         => $invoice['partner_id']['partner_identity_id']['display_name'],
    'contact_name'          => '',
    'contact_phone'         => $invoice['partner_id']['partner_identity_id']['phone'],
    'contact_email'         => $invoice['partner_id']['partner_identity_id']['email'],
    'customer_address1'     => $invoice['partner_id']['partner_identity_id']['address_street'],
    'customer_address2'     => $invoice['partner_id']['partner_identity_id']['address_zip'].' '.$invoice['partner_id']['partner_identity_id']['address_city'],
    'customer_has_vat'      => (int) $invoice['partner_id']['partner_identity_id']['has_vat'],
    'customer_vat'          => $invoice['partner_id']['partner_identity_id']['vat_number'],
    'date'                  => date('d/m/Y', $invoice['created']),
    'code'                  => $invoice['name'],
    'center'                => $booking['center_id']['name'],
    'center_address1'       => $booking['center_id']['address_street'],
    'center_address2'       => $booking['center_id']['address_zip'].' '.$booking['center_id']['address_city'],
    'center_contact1'       => (isset($booking['center_id']['manager_id']['name']))?$booking['center_id']['manager_id']['name']:'',
    'center_contact2'       => DataFormatter::format($booking['center_id']['phone'], 'phone').' - '.$booking['center_id']['email'],

    // by default, we use center contact details (overridden in case Center has a management Office, see below)
    'center_phone'          => DataFormatter::format($booking['center_id']['phone'], 'phone'),
    'center_email'          => $booking['center_id']['email'],
    'center_signature'      => $booking['center_id']['organisation_id']['signature'],

    'period'                => 'du '.date('d/m/Y', $booking['date_from']).' au '.date('d/m/Y', $booking['date_to']),
    'price'                 => $invoice['price'],
    'total'                 => $invoice['total'],

    'company_name'          => $booking['center_id']['organisation_id']['legal_name'],
    'company_address'       => sprintf("%s %s %s", $booking['center_id']['organisation_id']['address_street'], $booking['center_id']['organisation_id']['address_zip'], $booking['center_id']['organisation_id']['address_city']),
    'company_email'         => $booking['center_id']['organisation_id']['email'],
    'company_phone'         => DataFormatter::format($booking['center_id']['organisation_id']['phone'], 'phone'),
    'company_fax'           => DataFormatter::format($booking['center_id']['organisation_id']['fax'], 'phone'),
    'company_website'       => $booking['center_id']['organisation_id']['website'],
    'company_reg_number'    => $booking['center_id']['organisation_id']['registration_number'],

    // by default, we use organisation payment details (overridden in case Center has a management Office, see below)
    'company_iban'          => DataFormatter::format($booking['center_id']['organisation_id']['bank_account_iban'], 'iban'),
    'company_bic'           => DataFormatter::format($booking['center_id']['organisation_id']['bank_account_bic'], 'bic'),

    'payment_deadline'      => '',
    'payment_reference'     => '',
    'payment_qr_uri'        => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8Xw8AAoMBgDTD2qgAAAAASUVORK5CYII=',

    'lines'                 => [],
    'tax_lines'             => []
];


/*
    override contact and payment details with center's office, if set
*/
if($booking['center_id']['use_office_details']) {
    $office = $booking['center_id']['center_office_id'];
    $values['company_iban'] = DataFormatter::format($office['bank_account_iban'], 'iban');
    $values['company_bic'] = DataFormatter::format($office['bank_account_bic'], 'bic');
    $values['center_phone'] = DataFormatter::format($office['phone'], 'phone');
    $values['center_email'] = $office['email'];
    $values['center_signature'] = $booking['center_id']['organisation_id']['signature'];    
}



/*
    retrieve templates
*/
if($booking['center_id']['template_category_id']) {

    $template = Template::search([
                            ['category_id', '=', $booking['center_id']['template_category_id']],
                            ['code', '=', 'invoice'],
                            ['type', '=', 'invoice']
                        ])
                        ->read(['parts_ids' => ['name', 'value']])
                        ->first();
    if($template && count($templace) > 0) {
        foreach($template['parts_ids'] as $part_id => $part) {
            if($part['name'] == 'header') {
                $values['invoice_header_html'] = $part['value'].$values['center_signature'];
            }
            else if($part['name'] == 'notice') {
                $values['invoice_notice_html'] = $part['value'];
            }
        }

    }

}

/*
    feed lines
*/

$lines = [];

// display lines by groups, when present
foreach($invoice['invoice_line_groups_ids'] as $invoice_line_group) {

    $line = [
        'name'          => $invoice_line_group['name'],
        'price'         => null,
        'price'         => null,        
        'unit_price'    => null,
        'vat_rate'      => null,
        'qty'           => null,
        'free_qty'      => null,
        'is_group'      => true
    ];
    $lines[] = $line;

    $group_lines = [];

    foreach($invoice_line_group['invoice_lines_ids'] as $invoice_line) {

        // prevent displaying same line twice
        if(isset($invoice['invoice_lines_ids'][$invoice_line['id']])) {
            unset($invoice['invoice_lines_ids'][$invoice_line['id']]);
        }

        $line = [
            'name'          => $invoice_line['name'],
            'price'         => $invoice_line['price'],
            'total'         => $invoice_line['total'],
            'unit_price'    => $invoice_line['unit_price'],
            'vat_rate'      => $invoice_line['vat_rate'],
            'qty'           => $invoice_line['qty'],
            'discount'      => $invoice_line['discount'],
            'free_qty'      => $invoice_line['free_qty'],
            'is_group'      => false
        ];

        $group_lines[] = $line;
    }
    if($params['mode'] == 'detailed') {
        foreach($group_lines as $line) {
            $lines[] = $line;
        }
    }
    // group lines by VAT rate
    else {
        $group_tax_lines = [];
        foreach($group_lines as $line) {
            $vat_rate = strval($line['vat_rate']);
            if(!isset($group_tax_lines[$vat_rate])) {
                $group_tax_lines[$vat_rate] = 0;
            }
            $group_tax_lines[$vat_rate] += $line['total'];
        }

        if(count(array_keys($group_tax_lines)) <= 1) {
            $pos = count($lines)-1;
            foreach($group_tax_lines as $vat_rate => $total) {
                $lines[$pos]['qty'] = 1;
                $lines[$pos]['vat_rate'] = $vat_rate;
                $lines[$pos]['total'] = $total;
                $lines[$pos]['price'] = $total * (1 + $vat_rate);
            }
        }
        else {
            foreach($group_tax_lines as $vat_rate => $total) {
                $line = [
                    'name'      => 'Services avec TVA '.($vat_rate*100).'%',                    
                    'qty'       => 1,
                    'vat_rate'  => $vat_rate,
                    'total'     => $total,
                    'price'     => $total * (1 + $vat_rate)
                ];
                $lines[] = $line;
            }
        }
    }
}

// process remainging stand-alone lines 
foreach($invoice['invoice_lines_ids'] as $invoice_line) {
    $line = [
        'name'          => $invoice_line['name'],
        'price'         => $invoice_line['price'],
        'total'         => $invoice_line['total'],
        'unit_price'    => $invoice_line['unit_price'],
        'vat_rate'      => $invoice_line['vat_rate'],
        'qty'           => $invoice_line['qty'],
        'discount'      => $invoice_line['discount'],
        'free_qty'      => $invoice_line['free_qty'],
        'is_group'      => false
    ];

    $lines[] = $line;
}


$values['lines'] = $lines;

/*
    retrieve final VAT and group by rate
*/
foreach($lines as $line) {
    $vat_rate = $line['vat_rate'];
    $tax_label = 'TVA '.strval( intval($vat_rate * 100) ).'%';
    $vat = $line['price'] - $line['total'];
    if(!isset($values['tax_lines'][$tax_label])) {
        $values['tax_lines'][$tax_label] = 0;
    }
    $values['tax_lines'][$tax_label] += $vat;
}


foreach($booking['contacts_ids'] as $contact) {
    if(strlen($values['contact_name']) == 0 || $contact['type'] == 'invoice') {
        // overwrite data of customer with contact info
        $values['contact_name'] = str_replace(["Dr", "Ms", "Mrs", "Mr","Pr"], ["Dr","Melle", "Mme","Mr","Pr"], $contact['partner_identity_id']['title']).' '.$contact['partner_identity_id']['name'];
        $values['contact_phone'] = $contact['partner_identity_id']['phone'];
        $values['contact_email'] = $contact['partner_identity_id']['email'];
    }
}


// add payment terms
if(isset($invoice['funding_id']['due_date'])) {
    $values['payment_deadline'] = date('d/m/Y', $invoice['funding_id']['due_date']);
}


// generate a QR code
try {
    if(!isset($invoice['funding_id']['payment_reference'])) {
        throw new Exception('no payment ref');
    }
    $values['payment_reference'] = DataFormatter::format($invoice['funding_id']['payment_reference'], 'scor');
    $paymentData = Data::create()
        ->setServiceTag('BCD')
        ->setIdentification('SCT')
        ->setName($values['company_name'])
        ->setIban(str_replace(' ', '', $values['company_iban']))
        ->setBic(str_replace(' ', '', $values['company_bic']))
        ->setRemittanceReference($values['payment_reference'])
        ->setAmount($values['total']);

    $result = Builder::create()
        ->data($paymentData)
        ->errorCorrectionLevel(new ErrorCorrectionLevelMedium()) // required by EPC standard
        ->build();

    $dataUri = $result->getDataUri();
    $values['payment_qr_uri'] = $dataUri;

}
catch(Exception $exception) {
    // unknown error
}


/*
    Inject all values into the template
*/
$html = $twigTemplate->render($values);


/*
    Convert HTML to PDF 
*/

// instantiate and use the dompdf class
$options = new DompdfOptions();
$options->set('isRemoteEnabled', true);
$dompdf = new Dompdf($options);

$dompdf->setPaper('A4', 'portrait');
$dompdf->loadHtml((string) $html, 'UTF-8');
$dompdf->render();

$canvas = $dompdf->getCanvas();
$font = $dompdf->getFontMetrics()->getFont("helvetica", "regular");
$canvas->page_text(530, $canvas->get_height() - 35, "p. {PAGE_NUM} / {PAGE_COUNT}", $font, 9, array(0,0,0));
// $canvas->page_text(40, $canvas->get_height() - 35, "Export", $font, 9, array(0,0,0));


// get generated PDF raw binary
$output = $dompdf->output();

$context->httpResponse()
        // ->header('Content-Disposition', 'attachment; filename="document.pdf"')
        ->header('Content-Disposition', 'inline; filename="document.pdf"')
        ->body($output)
        ->send();