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

use lodging\sale\booking\Booking;
use lodging\sale\booking\Contract;
use sale\booking\Funding;
use communication\Template;
use communication\TemplatePart;
use communication\TemplateAttachment;
use equal\data\DataFormatter;


list($params, $providers) = announce([
    'description'   => "Returns a view populated with a collection of objects and outputs it as a PDF document.",
    'params'        => [
        'id' => [
            'description'   => 'Identitifier of the contract to print.',
            'type'          => 'integer',
            'required'      => true
        ],
        'view_id' =>  [
            'description'   => 'The identifier of the view <type.name>.',
            'type'          => 'string',
            'default'       => 'print.default'
        ],
        'lang' =>  [
            'description'   => 'Language in which labels and multilang field have to be returned (2 letters ISO 639-1).',
            'type'          => 'string',
            'default'       => DEFAULT_LANG
        ]
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

$entity = 'lodging\sale\booking\Contract';
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

// read contract
$fields = [
    'booking_id' => [
        'name',
        'modified',
        'date_from',
        'date_to',
        'price',
        'customer_id' => [
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
            'organisation_id' => [
                'id',
                'legal_name',
                'address_street', 'address_zip', 'address_city',
                'email',
                'phone',
                'fax',
                'website',
                'registration_number',
                'signature'
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
        ],
        'fundings_ids' => [
            'due_date', 'is_paid', 'due_amount',
            'payment_reference',
            'payment_deadline_id' => ['name']
        ]
    ],
    'contract_line_groups_ids' => [
        'name',
        'is_pack',
        'description',
        'contract_line_id' => [
            'name',
            'qty',
            'unit_price',
            'price',
            'vat_rate'
        ],
        'contract_lines_ids' => [
            'name',
            'qty',
            'unit_price',
            'price',
            'discount',
            'free_qty',
            'vat_rate'
        ]
    ]
];


$contract = Contract::id($params['id'])->read($fields)->first();

if(!$contract) {
    throw new Exception("unknown_contract", QN_ERROR_UNKNOWN_OBJECT);
}


/*
    extract required data and compose the $value map for the twig template
*/

$booking = $contract['booking_id'];


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
    'contract_header_html'  => '',
    'contract_notice_html'  => '',
    'customer_name'         => $booking['customer_id']['partner_identity_id']['display_name'],
    'contact_name'          => '',
    'contact_phone'         => $booking['customer_id']['partner_identity_id']['phone'],
    'contact_email'         => $booking['customer_id']['partner_identity_id']['email'],
    'customer_address1'     => $booking['customer_id']['partner_identity_id']['address_street'],
    'customer_address2'     => $booking['customer_id']['partner_identity_id']['address_zip'].' '.$booking['customer_id']['partner_identity_id']['address_city'],
    'customer_has_vat'      => (int) $booking['customer_id']['partner_identity_id']['has_vat'],
    'customer_vat'          => $booking['customer_id']['partner_identity_id']['vat_number'],
    'member'                => lodging_booking_print_contract_formatMember($booking),
    'date'                  => date('d/m/Y', $booking['modified']),
    'code'                  => sprintf("%03d.%03d", intval($booking['name']) / 1000, intval($booking['name']) % 1000),
    'center'                => $booking['center_id']['name'],
    'center_address1'       => $booking['center_id']['address_street'],
    'center_address2'       => $booking['center_id']['address_zip'].' '.$booking['center_id']['address_city'],
    'center_contact1'       => (isset($booking['center_id']['manager_id']['name']))?$booking['center_id']['manager_id']['name']:'',
    'center_contact2'       => DataFormatter::format($booking['center_id']['phone'], 'phone').' - '.$booking['center_id']['email'],
    'period'                => 'Du '.date('d/m/Y', $booking['date_from']).' au '.date('d/m/Y', $booking['date_to']),
    'price'                 => $booking['price'],
    'vat'                   => 0,
    'total'                 => 0,
    'company_name'          => $booking['center_id']['organisation_id']['legal_name'],
    'company_address'       => sprintf("%s %s %s", $booking['center_id']['organisation_id']['address_street'], $booking['center_id']['organisation_id']['address_zip'], $booking['center_id']['organisation_id']['address_city']),
    'company_email'         => $booking['center_id']['organisation_id']['email'],
    'company_phone'         => DataFormatter::format($booking['center_id']['organisation_id']['phone'], 'phone'),
    'company_fax'           => DataFormatter::format($booking['center_id']['organisation_id']['fax'], 'phone'),
    'company_website'       => $booking['center_id']['organisation_id']['website'],
    'company_reg_number'    => $booking['center_id']['organisation_id']['registration_number'],
    'company_iban'          => substr($booking['center_id']['bank_account_iban'], 0, 4).' '.substr($booking['center_id']['bank_account_iban'], 4, 4).' '.substr($booking['center_id']['bank_account_iban'], 8, 4).' '.substr($booking['center_id']['bank_account_iban'], 12),
    'company_bic'           => $booking['center_id']['bank_account_bic'],
    'installment_date'      => '',
    'installment_amount'    => 0,
    'installment_reference' => '',
    'installment_qr_url'    => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8Xw8AAoMBgDTD2qgAAAAASUVORK5CYII=',
    'fundings'              => [],
    'lines'                 => []
];


/*
    retrieve templates
*/
if($booking['center_id']['template_category_id']) {

    $template = Template::search([ 
                            ['category_id', '=', $booking['center_id']['template_category_id']], 
                            ['code', '=', 'contract'], 
                            ['type', '=', 'contract'] 
                        ])
                        ->read(['parts_ids' => ['name', 'value']])
                        ->first();

    foreach($template['parts_ids'] as $part_id => $part) {
        if($part['name'] == 'header') {
            $values['contract_header_html'] = $part['value'].$booking['center_id']['organisation_id']['signature'];
        }
        else if($part['name'] == 'notice') {
            $values['contract_notice_html'] = $part['value'];            
        }
    }

}

/*
    feed lines
*/

$lines = [];

foreach($contract['contract_line_groups_ids'] as $contract_line_group) {

    if($contract_line_group['is_pack']) {
        $group_is_pack = true;

        $price_vat_e = $contract_line_group['contract_line_id']['price'] / (1 + $contract_line_group['contract_line_id']['vat_rate']);
        $values['total'] += $price_vat_e;
        $values['vat'] += $contract_line_group['contract_line_id']['price'] - $price_vat_e;

        $line = [
            'name'          => $contract_line_group['name'],
            'description'   => $contract_line_group['contract_line_id']['name'],
            'price'         => $contract_line_group['contract_line_id']['price'],
            'price_excl'    => $price_vat_e,
            'unit_price'    => $contract_line_group['contract_line_id']['unit_price'],
            'vat_rate'      => $contract_line_group['contract_line_id']['vat_rate'],
            'qty'           => $contract_line_group['contract_line_id']['qty'],
            'is_group'       => true,
            'is_pack'        => true
        ];
        $lines[] = $line;
    }
    else {
        $group_is_pack = false;
        $line = [
            'name'          => $contract_line_group['name'],
            'price'         => 0,
            'unit_price'    => 0,
            'vat_rate'      => 0,
            'qty'           => 0,
            'is_group'       => true,
            'is_pack'        => false
        ];
        $lines[] = $line;
    }

    foreach($contract_line_group['contract_lines_ids'] as $contract_line) {
        if( !empty($contract_line_group['contract_line_id']) && $contract_line['id'] == $contract_line_group['contract_line_id']['id']) {
            continue;
        }

        $price_vat_e = $contract_line['price'] / (1 + $contract_line['vat_rate']);

        $line = [
            'name'          => $contract_line['name'],
            'price'         => $contract_line['price'],
            'price_excl'    => $price_vat_e,
            'unit_price'    => $contract_line['unit_price'],
            'vat_rate'      => $contract_line['vat_rate'],
            'qty'           => $contract_line['qty'],
            'discount'      => $contract_line['discount'],
            'free_qty'      => $contract_line['free_qty'],
            'is_group'      => false,
            'group_is_pack' => $group_is_pack
        ];

        if(!$group_is_pack) {
            $values['total'] += $price_vat_e;
            $values['vat'] += $contract_line['price'] - $price_vat_e;
        }

        $lines[] = $line;
    }
}


$values['lines'] = $lines;

foreach($booking['contacts_ids'] as $contact) {
    if(strlen($values['contact_name']) == 0 || $contact['type'] == 'booking') {
        // overwrite data of customer with contact info
        $values['contact_name'] = str_replace(["Dr", "Ms", "Mrs", "Mr","Pr"], ["Dr","Melle", "Mme","Mr","Pr"], $contact['partner_identity_id']['title']).' '.$contact['partner_identity_id']['name'];
        $values['contact_phone'] = $contact['partner_identity_id']['phone'];
        $values['contact_email'] = $contact['partner_identity_id']['email'];
    }
}

// inject expected fundings and find the first installment 
$installment_date = PHP_INT_MAX;
$installment_amount = 0;
$installment_ref = '';

foreach($booking['fundings_ids'] as $funding) {

    if($funding['due_date'] < $installment_date) {
        $installment_date = $funding['due_date'];
        $installment_amount = $funding['due_amount'];
        $installment_ref = $funding['payment_reference'];
    }
    $line = [
        'name'          => $funding['payment_deadline_id']['name'],
        'due_date'      => date('d/m/Y', $funding['due_date']),
        'due_amount'    => $funding['due_amount'],
        'is_paid'       => $funding['is_paid'],
        'reference'     => $funding['payment_reference']
    ];
    $values['fundings'][] = $line;
}

// no funding found
if($installment_date == PHP_INT_MAX) {
    // set default delay to 20 days
    $installment_date = time() + (60 * 60 *24 * 20);
    // set default amount to 20%
    $installment_amount = $booking['price'] * 0.2;
    // set installment reference    ('+++150/+++' for initial installment)
    $control = ((76*150) + intval($booking['name']) ) % 97;
    $control = ($control == 0)?97:$control;    
    $installment_ref = sprintf("150%04d%03d%02d", intval($booking['name']) / 1000, intval($booking['name']) % 1000, $control);
}

$values['installment_date'] = date('d/m/Y', $installment_date);
$values['installment_amount'] = (float) $installment_amount;
$values['installment_reference'] = '+++'.substr($installment_ref, 0, 3).'/'.substr($installment_ref, 3, 4).'/'.substr($installment_ref, 7, 5).'+++';


// generate a QR code
try {
    $paymentData = Data::create()
        ->setServiceTag('BCD')
        ->setIdentification('SCT')
        ->setName($values['company_name'])
        ->setIban(str_replace(' ', '', $booking['center_id']['bank_account_iban']))
        ->setBic(str_replace(' ', '', $booking['center_id']['bank_account_bic']))
        ->setRemittanceReference($values['installment_reference'])
        ->setAmount($values['installment_amount']);

    $result = Builder::create()
        ->data($paymentData)
        ->errorCorrectionLevel(new ErrorCorrectionLevelMedium()) // required by EPC standard
        ->build();

    $dataUri = $result->getDataUri();
    $values['installment_qr_url'] = $dataUri;

}
catch(Exception $exception) {
    // unknown error
}

/*
    Inject all values into the template
*/
$html = $twigTemplate->render($values);



// instantiate and use the dompdf class
$options = new DompdfOptions();
$options->set('isRemoteEnabled', true);
$dompdf = new Dompdf($options);

$dompdf->setPaper('A4', 'portrait');
$dompdf->loadHtml((string) $html);
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



function lodging_booking_print_contract_formatMember($booking) {
    $id = $booking['customer_id']['partner_identity_id']['id'];
    $code = ltrim(sprintf("%3d.%03d.%03d", intval($id) / 1000000, (intval($id) / 1000) % 1000, intval($id)% 1000), '0');
    return $code.' - '.$booking['customer_id']['partner_identity_id']['display_name'];
}