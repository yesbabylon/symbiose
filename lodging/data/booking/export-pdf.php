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
        'accept-origin' => '*'
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


$loader = new TwigFilesystemLoader(QN_BASEDIR."/packages/{$package}/views/");

$twig = new TwigEnvironment($loader);
/**  @var ExtensionInterface **/
$extension  = new IntlExtension();
$twig->addExtension($extension);

$template = $twig->load("{$class_path}.{$params['view_id']}.html");

// dans booking
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
        'email'
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
    'booking_lines_ids' => [
        'product_id' => ['display_name'],
        'qty',
        'unit_price',
        'price',
        'vat_rate',
        'qty_accounting_method'
    ],
/*
    'booking_lines_groups_ids' => [
        'price'
    ]
*/
];


$booking = Booking::id($params['id'])->read($fields)->first();

$values = [
    'customer_name'         => $booking['customer_id']['partner_identity_id']['display_name'],
    'contact_name'          => '',
    'contact_phone'         => $booking['customer_id']['partner_identity_id']['phone'],
    'contact_email'         => $booking['customer_id']['partner_identity_id']['email'],
    'customer_address1'     => $booking['customer_id']['partner_identity_id']['address_street'],
    'customer_address2'     => $booking['customer_id']['partner_identity_id']['address_zip'].' '.$booking['customer_id']['partner_identity_id']['address_city'],
    'customer_has_vat'      => (int) $booking['customer_id']['partner_identity_id']['has_vat'],
    'customer_vat'          => $booking['customer_id']['partner_identity_id']['vat_number'],    
    'member'                => lodging_booking_export_pdf_getMember($booking),
    'date'                  => date('d/m/Y', $booking['modified']),
    'code'                  => sprintf("%03d.%03d", intval($booking['name']) / 1000, intval($booking['name']) % 1000),
    'center'                => $booking['center_id']['name'],
    'center_address1'       => $booking['center_id']['address_street'],
    'center_address2'       => $booking['center_id']['address_zip'].' '.$booking['center_id']['address_city'],
    'center_contact1'       => $booking['center_id']['manager_id']['name'],
    'center_contact2'       => $booking['center_id']['phone'].' '.$booking['center_id']['email'],
    'period'                => 'Du '.date('d/m/Y', $booking['date_from']).' au '.date('d/m/Y', $booking['date_to']),
    'price'                 => $booking['price'],
    'vat'                   => 0,
    'total'                 => 0
];

$lines = [];

foreach($booking['booking_lines_ids'] as $booking_line) {
    $line = [
        'name'          => $booking_line['product_id']['display_name'],
        'price'         => $booking_line['price'],
        'unit_price'    => $booking_line['unit_price'],
        'vat_rate'      => $booking_line['vat_rate'],
        'nb_pers'       => 0,
        'nb_nights'     => 0,
        'qty'           => 0
    ];

    $price_vat_e = $booking_line['price'] / (1 + $booking_line['vat_rate']);
    $values['total'] += $price_vat_e;
    $values['vat'] += $booking_line['price'] - $price_vat_e;


    if($booking_line['qty_accounting_method'] == 'person') {
        $line['nb_pers'] = $booking_line['qty'];
    }
    else if($booking_line['qty_accounting_method'] == 'accomodation') {
        $line['nb_nights'] = $booking_line['qty'];
    }
    else {
        $line['qty'] = $booking_line['qty'];
    }

    $lines[] = $line;
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
$html = $template->render($values);



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
        ->header('Content-Type', 'application/pdf')
        // ->header('Content-Disposition', 'attachment; filename="document.pdf"')
        ->header('Content-Disposition', 'inline; filename="document.pdf"')
        ->body($output)
        ->send();



function lodging_booking_export_pdf_getMember($booking) {
    $id = $booking['customer_id']['partner_identity_id']['id'];
    $code = ltrim(sprintf("%3d.%03d.%03d", intval($id) / 1000000, (intval($id) / 1000) % 1000, intval($id)% 1000), '0');
    return $code.' - '.$booking['customer_id']['partner_identity_id']['display_name'];
}