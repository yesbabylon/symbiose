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
use lodging\identity\Center;
use lodging\identity\User;

/*
    We use this controller as a "print" controller, for printing the result of a "Checkin" Consumption search.
    We ignore domain, ids and controller and entity : we deal with Bookin entity and fetch relevant booking without additional "data" controller.
*/
list($params, $providers) = announce([
    'description'   => "Generates a list of arrival data as a PDF document, given a center and a date range.",
    'params'        => [
        'view_id' =>  [
            'description'   => 'The identifier of the view <type.name>.',
            'type'          => 'string',
            'default'       => 'print.arrivals'
        ],
        /*
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
        */
        'params' => [
            'description'   => 'Additional params to use for rerieving data.',
            'type'          => 'array',
            'default'       => []
        ],
        'lang' =>  [
            'description'   => 'Language in which labels and multilang field have to be returned (2 letters ISO 639-1).',
            'type'          => 'string',
            'default'       => constant('DEFAULT_LANG')
        ]
    ],
    'constants'             => ['DEFAULT_LANG', 'L10N_LOCALE'],
    'access' => [
        'visibility'        => 'protected',
        'groups'            => ['booking.default.user'],
    ],
    'response'      => [
        'content-type'      => 'application/pdf',
        'accept-origin'     => '*'
    ],
    'providers'     => ['context', 'orm', 'auth']
]);


list($context, $orm, $auth) = [$providers['context'], $providers['orm'], $providers['auth']];


// check query consistency
if(!isset($params['params']['date_from'])) {
    $params['params']['date_from'] = time();
    if(date('N') == 1) {
        $params['params']['date_from'] = strtotime('last Monday');
    }
}

if(!isset($params['params']['date_to'])) {
    $params['params']['date_to'] = strtotime('+7 days');
}

// if no center is provided, fallback to curent users' default
if(!isset($params['params']['center_id']) || $params['params']['center_id'] == 0) {
    $user = User::id($auth->userId())->read(['centers_ids'])->first(true);
    if(count($user['centers_ids']) <= 0) {
        throw new Exception("center_id", QN_ERROR_MISSING_PARAM);
    }
    $params['params']['center_id'] = reset($user['centers_ids']);
}

// inject retrieved vars as regular params
$params['center_id'] = $params['params']['center_id'];
$params['date_from'] = $params['params']['date_from'];
$params['date_to'] = $params['params']['date_to'];

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
    'description',
    'modified',
    'status',
    'date_from',
    'date_to',
    'time_from',
    'time_to',
    'price',
    'customer_identity_id' => [
        'id',
        'name',
        'type',
        'address_street', 'address_dispatch', 'address_city', 'address_zip', 'address_country',
        'type',
        'phone',
        'email',
        'has_vat',
        'vat_number'
    ],
    'type_id' => ['code', 'name'],
    'fundings_ids' => [
        'due_amount',
        'paid_amount',
        'due_date'
    ],
    'contacts_ids' => [
        'type',
        'partner_identity_id' => [
            'name',
            'phone',
            'mobile',
            'email',
            'title'
        ]
    ],
    'booking_lines_groups_ids' => [
        'name',
        'nb_pers',
        'is_sojourn',
        'date_from',
        'date_to',
        'sojourn_product_models_ids' => [
            'is_accomodation',
            'rental_unit_assignments_ids' => [
                'rental_unit_id' => [
                    'name',
                    'code',
                    'description'
                ],
                'qty'
            ]
        ]
    ]
];

$bookings = Booking::ids($bookings_ids)->read($fields)->get(true);

if(!$bookings) {
    throw new Exception("unknown_contract", QN_ERROR_UNKNOWN_OBJECT);
}


// retrieve Center details
$center = Center::id($params['center_id'])->read([
        'name',
        'manager_id' => ['name'],
        'address_street',
        'address_city',
        'address_zip',
        'phone',
        'email',
        'bank_account_iban',
        'template_category_id',
        'use_office_details',
        'center_office_id' => [
            'name',
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
            'address_street',
            'address_zip',
            'address_city',
            'email',
            'phone',
            'fax',
            'website',
            'registration_number',
            'has_vat',
            'vat_number',
            'bank_account_iban',
            'bank_account_bic',
            'signature'
        ]
    ])
    ->first(true);


$values = [
    'date'          => time(),
    'from'          => $params['date_from'],
    'to'            => $params['date_to'],
    'center'        => $center,
    'bookings'      => []
];

// retrieve contact details
foreach($bookings as $booking) {
    $item = [
        'name'              => lodging_booking_print_booking_formatName($booking),
        'description'       => str_replace(['<p><br></p>', '<p>', '</p>'], ['<br />', '<span>', '</span><br />'], $booking['description']),
        'from'              => date('d/m/Y @ H:i', $booking['date_from'] + $booking['time_from']),
        'to'                => date('d/m/Y @ H:i', $booking['date_to'] + $booking['time_to']),
        'member'            => lodging_booking_print_booking_formatMember($booking),
        'contacts'          => [],
        'sojourns'          => [],
        'customer'          => $booking['customer_identity_id'],
        'price'             => $booking['price'],
        'paid_amount'       => 0.0,
        'due_amount'        => 0.0
    ];

    // retrieve paid amount based on fundings
    foreach($booking['fundings_ids'] as $funding) {
        $item['paid_amount'] += $funding['paid_amount'];
        if($funding['due_date'] <= time()) {
            $item['due_amount'] += ($funding['due_amount'] - $funding['paid_amount']);
        }
    }

    // gather relevant contact details
    foreach($booking['contacts_ids'] as $contact) {
        if($contact['type'] == 'booking') {
            // overwrite data of customer with contact info
            $item['contacts'][] = [
                'name'  => str_replace(["Dr", "Ms", "Mrs", "Mr","Pr"], ["Dr","Melle", "Mme","Mr","Pr"], $contact['partner_identity_id']['title']).' '.$contact['partner_identity_id']['name'],
                'phone' => (strlen($contact['partner_identity_id']['mobile']))?$contact['partner_identity_id']['mobile']:$contact['partner_identity_id']['phone'],
                'email' => $contact['partner_identity_id']['email']
            ];
        }
    }

    foreach($booking['booking_lines_groups_ids'] as $group) {
        if($group['is_sojourn']) {
            $sojourn = [
                'name'          => $group['name'],
                'date_from'     => $group['date_from'],
                'date_to'       => $group['date_to'],
                'nb_pers'       => $group['nb_pers'],
                'rental_units'  => []
            ];

            foreach($group['sojourn_product_models_ids'] as $spm) {
                if($spm['is_accomodation']) {
                    foreach($spm['rental_unit_assignments_ids'] as $assignment) {
                        $sojourn['rental_units'][] = [
                            'name'   => $assignment['rental_unit_id']['code'],
                            'qty'    => $assignment['qty']
                        ];
                    }
                }
            }
            $item['sojourns'][] = $sojourn;
        }
    }

    $values['bookings'][] = $item;
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
    // #todo - temp workaround against LOCALE mixups
    $filter = new \Twig\TwigFilter('format_money', function ($value) {
        return number_format((float)($value),2,",",".").' €';
    });
    $twig->addFilter($filter);

    $template = $twig->load("{$class_path}.{$params['view_id']}.html");

    // use localisation prefs for rendering
    setlocale(LC_ALL, constant('L10N_LOCALE'));
    // render template
    $html = $template->render($values);
    // restore original locale
    setlocale(LC_ALL, 0);
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
    $id = $booking['customer_identity_id']['id'];
    return ltrim(sprintf("%3d.%03d.%03d", intval($id) / 1000000, (intval($id) / 1000) % 1000, intval($id)% 1000), '0');
}

function lodging_booking_print_booking_formatName($booking) {
    $code = (string) $booking['name'];
    return substr($code, 0, 3).'.'.substr($code, 3, 3);
}