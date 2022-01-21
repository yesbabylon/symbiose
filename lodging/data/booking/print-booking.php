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

use lodging\sale\booking\Booking;
use communication\Template;


list($params, $providers) = announce([
    'description'   => "Returns a view populated with a collection of objects and outputs it as a PDF document.",
    'params'        => [
        'id' => [
            'description'   => 'Identitifier of the object to print.',
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

$entity = 'lodging\sale\booking\Booking';
$parts = explode('\\', $entity);
$package = array_shift($parts);
$class_path = implode('/', $parts);
$parent = get_parent_class($entity);

$file = QN_BASEDIR."/packages/{$package}/views/{$class_path}.{$params['view_id']}.html";

if(!file_exists($file)) {
    throw new Exception("unknown_view_id", QN_ERROR_UNKNOWN_OBJECT);
}


// read booking
$fields = [
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
        'template_category_id',
        'organisation_id' => [
            'id',
            'legal_name',
            'address_street',
            'address_zip',
            'address_city',
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
    'booking_lines_groups_ids' => [
        'name',
        'has_pack',
        'is_locked',
        'pack_id'  => ['label'],
        'qty',
        'unit_price',
        'vat_rate',
        'price',
        'date_from',
        'date_to',
        'nb_pers',
        'booking_lines_ids' => [
            'product_id' => ['label'],
            'qty',
            'unit_price',
            'price',
            'vat_rate',
            'qty_accounting_method',
            'price_adapters_ids' => ['type', 'value', 'is_manual_discount']
        ]
    ]

];


$booking = Booking::id($params['id'])->read($fields)->first();

if(!$booking) {
    throw new Exception("unknown_contract", QN_ERROR_UNKNOWN_OBJECT);
}

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
    'quote_header_html'     => '',    
    'customer_name'         => $booking['customer_id']['partner_identity_id']['display_name'],
    'contact_name'          => '',
    'contact_phone'         => $booking['customer_id']['partner_identity_id']['phone'],
    'contact_email'         => $booking['customer_id']['partner_identity_id']['email'],
    'customer_address1'     => $booking['customer_id']['partner_identity_id']['address_street'],
    'customer_address2'     => $booking['customer_id']['partner_identity_id']['address_zip'].' '.$booking['customer_id']['partner_identity_id']['address_city'],
    'customer_has_vat'      => (int) $booking['customer_id']['partner_identity_id']['has_vat'],
    'customer_vat'          => $booking['customer_id']['partner_identity_id']['vat_number'],
    'member'                => lodging_booking_print_booking_formatMember($booking),
    'date'                  => date('d/m/Y', $booking['modified']),
    'code'                  => sprintf("%03d.%03d", intval($booking['name']) / 1000, intval($booking['name']) % 1000),
    'center'                => $booking['center_id']['name'],
    'center_address1'       => $booking['center_id']['address_street'],
    'center_address2'       => $booking['center_id']['address_zip'].' '.$booking['center_id']['address_city'],
    'center_contact1'       => (isset($booking['center_id']['manager_id']['name']))?$booking['center_id']['manager_id']['name']:'',
    'center_contact2'       => lodging_booking_print_booking_formatPhone($booking['center_id']['phone']).' - '.$booking['center_id']['email'],
    'period'                => 'Du '.date('d/m/Y', $booking['date_from']).' au '.date('d/m/Y', $booking['date_to']),
    'price'                 => $booking['price'],
    'vat'                   => 0,
    'total'                 => 0,
    'company_name'          => $booking['center_id']['organisation_id']['legal_name'],
    'company_address'       => sprintf("%s %s %s", $booking['center_id']['organisation_id']['address_street'], $booking['center_id']['organisation_id']['address_zip'], $booking['center_id']['organisation_id']['address_city']),
    'company_email'         => $booking['center_id']['organisation_id']['email'],
    'company_phone'         => lodging_booking_print_booking_formatPhone($booking['center_id']['organisation_id']['phone']),
    'company_fax'           => lodging_booking_print_booking_formatPhone($booking['center_id']['organisation_id']['fax']),
    'company_website'       => $booking['center_id']['organisation_id']['website'],
    'company_reg_number'    => $booking['center_id']['organisation_id']['registration_number'],
    'company_iban'          => $booking['center_id']['bank_account_iban'],    
    'lines'                 => []
];


/*
    retrieve templates
*/
if($booking['center_id']['template_category_id']) {

    $template = Template::search([ 
                            ['category_id', '=', $booking['center_id']['template_category_id']], 
                            ['code', '=', 'quote'], 
                            ['type', '=', 'quote'] 
                        ])
                        ->read(['parts_ids' => ['name', 'value']])
                        ->first();

    foreach($template['parts_ids'] as $part_id => $part) {
        if($part['name'] == 'header') {
            $values['quote_header_html'] = $part['value'].$booking['center_id']['organisation_id']['signature'];
        }
        /*
        else if($part['name'] == 'notice') {

        }
        */
    }

}


/*
    feed lines
*/
$lines = [];


foreach($booking['booking_lines_groups_ids'] as $booking_line_group) {

    $group_label = $booking_line_group['name'].' : ';

    if($booking_line_group['date_from'] == $booking_line_group['date_to']) {
        $group_label .= date('d/m/y', $booking_line_group['date_from']);
    }
    else {
        $group_label .= date('d/m/y', $booking_line_group['date_from']).' - '.date('d/m/y', $booking_line_group['date_to']);
    }

    $group_label .= ' - '.$booking_line_group['nb_pers'].' p.';

    if($booking_line_group['has_pack'] && $booking_line_group['is_locked']) {
        $group_is_pack = true;

        $price_vat_e = $booking_line_group['price'] / (1 + $booking_line_group['vat_rate']);
        $values['total'] += $price_vat_e;
        $values['vat'] += $booking_line_group['price'] - $price_vat_e;

        $line = [
            'name'          => $group_label,
            'description'   => $booking_line_group['pack_id']['label'],
            'price'         => $booking_line_group['price'],
            'price_excl'    => $price_vat_e,
            'unit_price'    => $booking_line_group['unit_price'],
            'vat_rate'      => $booking_line_group['vat_rate'],
            'qty'           => $booking_line_group['qty'],
            'is_group'      => true,
            'is_pack'       => true
        ];
        $lines[] = $line;
    }
    else {
        $group_is_pack = false;
        $line = [
            'name'          => $group_label,
            'price'         => 0,
            'unit_price'    => 0,
            'vat_rate'      => 0,
            'qty'           => 0,
            'is_group'      => true,
            'is_pack'       => false
        ];
        $lines[] = $line;
    }

    foreach($booking_line_group['booking_lines_ids'] as $booking_line) {

        $price_vat_e = $booking_line['price'] / (1 + $booking_line['vat_rate']);

        $disc_value = 0;
        $disc_percent = 0;
        $free_qty = 0;
        foreach($booking_line['price_adapters_ids'] as $aid => $adata) {
            if($adata['is_manual_discount']) {
                if($adata['type'] == 'amount') {
                    $disc_value += $adata['value'];
                }
                else if($adata['type'] == 'percent') {
                    $disc_percent += $adata['value'];
                }
                else if($adata['type'] == 'freebie') {
                    $free_qty += $adata['value'];
                }
            }
            // auto granted freebies are displayed as manual discounts
            else {
                if($adata['type'] == 'freebie') {
                    $free_qty += $adata['value'];
                }
            }
        }
        // convert discount value to a percentage
        $disc_value = $disc_value / (1 + $booking_line['vat_rate']);
        $price = $booking_line['unit_price'] * $booking_line['qty'];
        $disc_value_perc = ($price) ? ($price - $disc_value) / $price : 0;
        $disc_percent += (1-$disc_value_perc);

        $line = [
            'name'          => $booking_line['product_id']['label'],
            'price'         => $booking_line['price'],
            'price_excl'    => $price_vat_e,
            'unit_price'    => $booking_line['unit_price'],
            'vat_rate'      => $booking_line['vat_rate'],
            'qty'           => $booking_line['qty'],
            'is_group'      => false,
            'group_is_pack' => $group_is_pack,
            'free_qty'      => $free_qty,
            'discount'      => $disc_percent
        ];

        if(!$group_is_pack) {
            $values['total'] += $price_vat_e;
            $values['vat'] += $booking_line['price'] - $price_vat_e;
        }

        $lines[] = $line;
    }
}



$values['lines'] = $lines;

foreach($booking['contacts_ids'] as $contact) {
    if(strlen($contact_name) == 0 || $contact['type'] == 'booking') {
        // overwrite data of customer with contact info
        $values['contact_name'] = str_replace(["Dr", "Ms", "Mrs", "Mr","Pr"], ["Dr","Melle", "Mme","Mr","Pr"], $contact['partner_identity_id']['title']).' '.$contact['partner_identity_id']['name'];
        $values['contact_phone'] = $contact['partner_identity_id']['phone'];
        $values['contact_email'] = $contact['partner_identity_id']['email'];
    }
}

/*
    Inject all values into the template
*/

try {
    $loader = new TwigFilesystemLoader(QN_BASEDIR."/packages/{$package}/views/");

    $twig = new TwigEnvironment($loader);
    /**  @var ExtensionInterface **/
    $extension  = new IntlExtension();
    $twig->addExtension($extension);
    
    $template = $twig->load("{$class_path}.{$params['view_id']}.html");
    
    
    $html = $template->render($values);    
}
catch(Exception $e) {
    throw new Exception("template_parsing_issue", QN_ERROR_INVALID_CONFIG);    
}


/*
    Convert HTML to PDF 
*/


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



function lodging_booking_print_booking_formatMember($booking) {
    $id = $booking['customer_id']['partner_identity_id']['id'];
    $code = ltrim(sprintf("%3d.%03d.%03d", intval($id) / 1000000, (intval($id) / 1000) % 1000, intval($id)% 1000), '0');
    return $code.' - '.$booking['customer_id']['partner_identity_id']['display_name'];
}

/*
    +32489532419  12    +32 489 53 24 19
    +3286434407   11    +32 86 43 44 07
    +326736276    10    +32 673 62 76
    +32488100      9    +32 48 81 00
*/
function lodging_booking_print_booking_formatPhone($phone) {
    // invalid number
    if(strlen($phone) < 6) return '';

    // normalize number, with BE prefix
    if(substr($phone, 0, 2) == '32') {
        $phone = '+'.$phone;
    }
    if(substr($phone, 0, 1) == '0') {
        $phone = '+32'.substr($phone, 1);
    }
    if(substr($phone, 0, 1) != '+') {
        $phone = '+32'.$phone;
    }

    switch(strlen($phone)) {
        case 12:
            $to = sprintf("%s %s %s %s %s",
                  substr($phone, 0, 3),
                  substr($phone, 3, 3),
                  substr($phone, 6, 2),
                  substr($phone, 8, 2),
                  substr($phone, 10));
            break;
        case 11:
            $to = sprintf("%s %s %s %s %s",
                  substr($phone, 0, 3),
                  substr($phone, 3, 2),
                  substr($phone, 5, 2),
                  substr($phone, 7, 2),
                  substr($phone, 9));
            break;
        case 10:
            $to = sprintf("%s %s %s %s",
                  substr($phone, 0, 3),
                  substr($phone, 3, 3),
                  substr($phone, 6, 2),
                  substr($phone, 8));
        case 8:
        default:
            $to = sprintf("%s %s %s %s",
                  substr($phone, 0, 3),
                  substr($phone, 3, 2),
                  substr($phone, 5, 2),
                  substr($phone, 7));
    }
    return $to;
}