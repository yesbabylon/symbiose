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

use equal\data\DataFormatter;

use lodging\sale\booking\Booking;
use communication\Template;


list($params, $providers) = announce([
    'description'   => "Generates a list of arrival data as a PDF document, given a center and a date range.",
    'params'        => [
        'view_id' =>  [
            'description'   => 'The identifier of the view <type.name>.',
            'type'          => 'string',
            'default'       => 'print.default'
        ],
        'date_from' => [
            'type'              => 'date',
            'description'       => "Date interval lower limit.",
            'default'           => strtotime('first day of previous week')
        ],
        'date_to' => [
            'type'              => 'date',
            'description'       => 'Date interval Upper limit.',
            'default'           => strtotime('last day of next week')
        ],
        'center_id' => [
            'type'              => 'many2one',
            'foreign_object'    => 'lodging\identity\Center',
            'description'       => "The center to which the booking relates to.",
            'required'      => true
        ],
        'lang' =>  [
            'description'   => 'Language in which labels and multilang field have to be returned (2 letters ISO 639-1).',
            'type'          => 'string',
            'default'       => constant('DEFAULT_LANG')
        ]
    ],
    'constants'             => ['DEFAULT_LANG'],
    'access' => [
        'visibility'        => 'protected',
        'groups'            => ['booking.default.user'],
    ],
    'response'      => [
        'content-type'      => 'application/pdf',
        'accept-origin'     => '*'
    ],
    'providers'     => ['context', 'orm']
]);


list($context, $orm) = [$providers['context'], $providers['orm']];



// retrieve the listing of confirmed Booking within the date range

$bookings_ids = Booking::search([ ['center_id', '=', $params['center_id']], ['status', 'in', ['confirmed', 'validated']], ['date_from', '>=', $params['date_from']], ['date_to', '<=', $params['date_to']] ])->ids();


if(!count($bookings_ids)) {
    throw new Exception("no_match", QN_ERROR_UNKNOWN_OBJECT);
}

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
    'status',
    'date_from',
    'date_to',
    'customer_identity_id' => [
        'id',
        'display_name',
        'phone',
        'email'
    ],
    'customer_id' => [
        'partner_identity_id' => [
            'id',
            'display_name',
            'type',
            'address_street', 'address_dispatch', 'address_city', 'address_zip', 'address_country',
            'type',
            'phone',
            'email',
            'has_vat',
            'vat_number'
        ]
    ],
    'center_id' => [
        'name',
        'manager_id' => ['name']
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
        'qty',
        'date_from',
        'date_to',
        'nb_pers',
        'booking_lines_ids' => [
            'name',
            'product_id' => ['label'],
            'description',
            'qty',
        ]
    ]
];


$bookings = Booking::ids($bookings_ids)->read($fields)->get(true);

if(!$bookings) {
    throw new Exception("unknown_contract", QN_ERROR_UNKNOWN_OBJECT);
}

print_r($bookings);
die();

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
    'quote_notice_html'     => '',
    'status'                => ($booking['status'] == 'quote')?'Devis':'Option',

    'is_price_tbc'          => $booking['is_price_tbc'],
    'customer_name'         => substr($booking['customer_id']['partner_identity_id']['display_name'], 0, 66),
    'attn_name'             => ($booking['customer_id']['partner_identity_id']['id'] == $booking['customer_identity_id']['id'])?'':substr($booking['customer_identity_id']['display_name'], 0, 33),
    'contact_name'          => '',
    'contact_phone'         => $booking['customer_id']['partner_identity_id']['phone'],
    'contact_email'         => $booking['customer_id']['partner_identity_id']['email'],
    'customer_address1'     => $booking['customer_id']['partner_identity_id']['address_street'],
    'customer_address2'     => $booking['customer_id']['partner_identity_id']['address_zip'].' '.$booking['customer_id']['partner_identity_id']['address_city'].(($booking['customer_id']['partner_identity_id']['address_country'] != 'BE')?(' - '.$booking['customer_id']['partner_identity_id']['address_country']):''),    
    'customer_country'      => $booking['customer_id']['partner_identity_id']['address_country'],
    'customer_has_vat'      => (int) $booking['customer_id']['partner_identity_id']['has_vat'],
    'customer_vat'          => $booking['customer_id']['partner_identity_id']['vat_number'],
    'member'                => lodging_booking_print_booking_formatMember($booking),
    'date'                  => date('d/m/Y', $booking['modified']),
    'code'                  => sprintf("%03d.%03d", intval($booking['name']) / 1000, intval($booking['name']) % 1000),
    'center'                => $booking['center_id']['name'],
    'center_address1'       => $booking['center_id']['address_street'],
    'center_address2'       => $booking['center_id']['address_zip'].' '.$booking['center_id']['address_city'],
    'center_contact1'       => (isset($booking['center_id']['manager_id']['name']))?$booking['center_id']['manager_id']['name']:'',
    'center_contact2'       => DataFormatter::format($booking['center_id']['phone'], 'phone').' - '.$booking['center_id']['email'],

    // by default, we use center contact details (overridden in case Center has a management Office, see below)
    'center_phone'          => DataFormatter::format($booking['center_id']['phone'], 'phone'),
    'center_email'          => $booking['center_id']['email'],
    'center_signature'      => $booking['center_id']['organisation_id']['signature'],

    'period'                => 'Du '.date('d/m/Y', $booking['date_from']).' au '.date('d/m/Y', $booking['date_to']),
    'price'                 => $booking['price'],
    'total'                 => $booking['total'],

    'company_name'          => $booking['center_id']['organisation_id']['legal_name'],
    'company_address'       => sprintf("%s %s %s", $booking['center_id']['organisation_id']['address_street'], $booking['center_id']['organisation_id']['address_zip'], $booking['center_id']['organisation_id']['address_city']),
    'company_email'         => $booking['center_id']['organisation_id']['email'],
    'company_phone'         => DataFormatter::format($booking['center_id']['organisation_id']['phone'], 'phone'),
    'company_fax'           => DataFormatter::format($booking['center_id']['organisation_id']['fax'], 'phone'),
    'company_website'       => $booking['center_id']['organisation_id']['website'],
    'company_reg_number'    => $booking['center_id']['organisation_id']['registration_number'],
    'company_has_vat'       => $booking['center_id']['organisation_id']['has_vat'],
    'company_vat_number'    => $booking['center_id']['organisation_id']['vat_number'],


    // by default, we use organisation payment details (overridden in case Center has a management Office, see below)
    'company_iban'          => DataFormatter::format($booking['center_id']['organisation_id']['bank_account_iban'], 'iban'),
    'company_bic'           => DataFormatter::format($booking['center_id']['organisation_id']['bank_account_bic'], 'bic'),

    'lines'                 => [],
    'tax_lines'             => [],

    'benefit_lines'         => []
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
                            ['code', '=', $booking['status']],
                            ['type', '=', $booking['status']]
                        ])
                        ->read(['parts_ids' => ['name', 'value']], $params['lang'])
                        ->first();

    foreach($template['parts_ids'] as $part_id => $part) {
        if($part['name'] == 'header') {
            $value = $part['value'].$values['center_signature'];
            $value = str_replace('{center}', $booking['center_id']['name'], $value);
            $value = str_replace('{date_from}', date('d/m/Y', $booking['date_from']), $value);
            $value = str_replace('{date_to}', date('d/m/Y', $booking['date_to']), $value);
            $values['quote_header_html'] = $value;
        }
        else if($part['name'] == 'notice') {
            $values['invoice_notice_html'] = $part['value'];
        }
    }

}


/*
    feed lines
*/
$lines = [];

// all lines are stored in groups
foreach($booking['booking_lines_groups_ids'] as $booking_line_group) {

    // generate group details
    $group_details = '';

    if($booking_line_group['date_from'] == $booking_line_group['date_to']) {
        $group_details .= date('d/m/y', $booking_line_group['date_from']);
    }
    else {
        $group_details .= date('d/m/y', $booking_line_group['date_from']).' - '.date('d/m/y', $booking_line_group['date_to']);
    }

    $group_details .= ' - '.$booking_line_group['nb_pers'].'p.';

    if($booking_line_group['has_pack'] && $booking_line_group['is_locked']) {
        // group is a product pack (bundle) with own price
        $group_is_pack = true;

        $line = [
            'name'          => $booking_line_group['name'],
            'details'       => $group_details,
            'description'   => $booking_line_group['pack_id']['label'],
            'price'         => $booking_line_group['price'],
            'total'         => $booking_line_group['total'],
            'unit_price'    => $booking_line_group['unit_price'],
            'vat_rate'      => $booking_line_group['vat_rate'],
            'qty'           => $booking_line_group['qty'],
            'free_qty'      => $booking_line_group['free_qty'],
            'discount'      => $booking_line_group['discount'],
            'is_group'      => true,
            'is_pack'       => true
        ];
        $lines[] = $line;

        if($params['mode'] == 'detailed') {
            foreach($booking_line_group['booking_lines_ids'] as $booking_line) {
                $line = [
                    'name'          => $booking_line['name'],
                    'qty'           => $booking_line['qty'],
                    'price'         => null,
                    'total'         => null,
                    'unit_price'    => null,
                    'vat_rate'      => null,
                    'discount'      => null,
                    'free_qty'      => null,
                    'is_group'      => false,
                    'is_pack'       => false
                ];
                $lines[] = $line;
            }
        }
    }
    else {

        // group is a pack with no own price
        $group_is_pack = false;

        if($params['mode'] == 'grouped') {
            $line = [
                'name'          => $booking_line_group['name'],
                'details'       => $group_details,
                'price'         => $booking_line_group['price'],
                'total'         => $booking_line_group['total'],
                'unit_price'    => $booking_line_group['total'],
                'vat_rate'      => (floatval($booking_line_group['price'])/floatval($booking_line_group['total']) - 1.0),
                'qty'           => 1,
                'free_qty'      => 0,
                'discount'      => 0,
                'is_group'      => true,
                'is_pack'       => false
            ];
        }
        else {
            $line = [
                'name'          => $booking_line_group['name'],
                'details'       => $group_details,
                'price'         => null,
                'total'         => null,
                'unit_price'    => null,
                'vat_rate'      => null,
                'qty'           => null,
                'free_qty'      => null,
                'discount'      => null,
                'is_group'      => true,
                'is_pack'       => false
            ];
        }
        $lines[] = $line;


        $group_lines = [];

        foreach($booking_line_group['booking_lines_ids'] as $booking_line) {

            if($params['mode'] == 'grouped') {
                $line = [
                    'name'          => (strlen($booking_line['description']) > 0)?$booking_line['description']:$booking_line['name'],
                    'price'         => null,
                    'total'         => null,
                    'unit_price'    => null,
                    'vat_rate'      => null,
                    'qty'           => $booking_line['qty'],
                    'discount'      => null,
                    'free_qty'      => null,
                    'is_group'      => false,
                    'is_pack'       => false
                ];
            }
            else {
                $line = [
                    'name'          => (strlen($booking_line['description']) > 0)?$booking_line['description']:$booking_line['name'],
                    'price'         => $booking_line['price'],
                    'total'         => $booking_line['total'],
                    'unit_price'    => $booking_line['unit_price'],
                    'vat_rate'      => $booking_line['vat_rate'],
                    'qty'           => $booking_line['qty'],
                    'discount'      => $booking_line['discount'],
                    'free_qty'      => $booking_line['free_qty'],
                    'is_group'      => false,
                    'is_pack'       => false
                ];
            }

            $group_lines[] = $line;
        }
        if($params['mode'] == 'detailed' || $params['mode'] == 'grouped') {
            foreach($group_lines as $line) {
                $lines[] = $line;
            }
        }
        // mode is 'simple' : group lines by VAT rate
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
}

$values['lines'] = $lines;


/*
    compute fare benefit detail
*/
$values['benefit_lines'] = [];

foreach($booking['booking_lines_groups_ids'] as $group) {
    if($group['fare_benefit'] == 0) {
        continue;
    }
    $index = $group['rate_class_id']['description'];
    if(!isset($values['benefit_lines'][$index])) {
        $values['benefit_lines'][$index] = [
            'name'  => $index,
            'value' => $group['fare_benefit']
        ];
    }
    else {
        $values['benefit_lines'][$index]['value'] += $group['fare_benefit'];
    }
}



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


// retrieve contact details
foreach($booking['contacts_ids'] as $contact) {
    if(strlen($values['contact_name']) == 0 || $contact['type'] == 'booking') {
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
    $current_locale = setlocale(LC_ALL, 0);
    // use localisation prefs for rendering
    if(defined('L10N_LOCALE')) {
        $res = setlocale(LC_ALL, constant('L10N_LOCALE'));
        if($res) {
            trigger_error("QN_DEBUG_PHP::set locale to ".constant('L10N_LOCALE'), QN_REPORT_DEBUG);
        }
        else {
            trigger_error("QN_DEBUG_PHP::unknown locale ".constant('L10N_LOCALE'), QN_REPORT_WARNING);
        }
    }

    $loader = new TwigFilesystemLoader(QN_BASEDIR."/packages/{$package}/views/");

    $twig = new TwigEnvironment($loader);
    /**  @var ExtensionInterface **/
    $extension  = new IntlExtension();
    $twig->addExtension($extension);

    $template = $twig->load("{$class_path}.{$params['view_id']}.html");

    $html = $template->render($values);
    // restore original locale
    setlocale(LC_ALL, $current_locale);
}
catch(Exception $e) {
    trigger_error("QN_DEBUG_ORM::error while parsing template - ".$e->getMessage(), QN_REPORT_DEBUG);
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