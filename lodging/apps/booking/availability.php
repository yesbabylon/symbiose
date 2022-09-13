<?php

use lodging\sale\booking\Booking;
use lodging\sale\booking\Consumption;

list($params, $providers)    =  announce([
    'description'            => 'Retrieves all booked dates for a specific center and shows their availability/non-availability in a calendar.',
    'params'                 => [
        //  'center_id'      => [
        //     'description' => 'Identifier of the Booking.',
        //     'type'        => 'integer'
        // ],
        'date_from'          => [
            'description'    => 'Starting date of the booking.',
            'type'           => 'string',
        ],
        'date_to'            => [
            'description'    => 'End date of the booking.',
            'type'           => 'string'
        ],
        'slug'               => [
            'description'    => 'Name of the center.',
            'type'           => 'string'
        ]
    ],
    'response'               => [
        'content-type'       => 'text/plain',
        'charset'            => 'utf-8',
        'accept-origin'      => '*'
    ],
    'providers'              => ['context', 'orm', 'auth']
]);


// slugs (name of the centers associated with their ids)

$slugs =  [
    "arbrefontaine-ecole"         => 3,
    "arbrefontaine-la-ruche"      => 4,
    "arbrefontaine-petite-maison" => 4,
    "basseilles-1"                => 5,
    "basseilles-2"                => 5,
    "bastogne"                    => 6,
    "bohan"                       => 7,
    "bruly-presbytere"            => 8,
    "bruly-ecole"                 => 9,
    "chassepierre"                => 10,
    "cornimont"                   => 11,
    "daverdisse"                  => 12,
    "hastiere"                    => 13,
    "haus-stockem-eupen"          => 14,
    "la-reid"                     => 15,
    "latour"                      => 16,
    "lesse-redu"                  => 18,
    "maboge"                      => 19,
    "mormont-grande"              => 20,
    "mormont-petite"              => 20,
    "vergnies"                    => 21,
    "werbomont"                   => 23,
    "eupen"                       => 24,
    "villers-sainte-gertrude-2"   => 25,
    "villers-sainte-gertrude"     => 25,
    "han-sur-lesse"               => 26,
    "ovifat"                      => 27,
    "louvain-la-neuve"            => 28,
    "rochefort"                   => 29,
    "wanne"                       => 30
];

$center = $slugs[$params["slug"]];

// Discope values
$discopeConsumptions = Consumption::search([
    [
        ["date", ">=", $params["date_from"]],
        ["center_id", "=", $center],
        ["is_accomodation", "=", 1]
    ],
    [
        ["date", "<=", $params["date_to"]],
        ["center_id", "=", $center],
        ["is_accomodation", "=", 1]
    ]
])
    ->read(['date'])
    ->get(true);


// Hestia bookings
$json = run('get', 'model_view', [
    'entity'        => "lodging\\sale\\booking\\Booking",
    'view_id'       => "hestia_json"
]);

$hestia_json = json_decode($json, true);

// Getting the value by key (only dates related to the specific center)
$hestia = $hestia_json[$center];

// merging both array's
$mergedArray = array_merge($hestia, $discopeConsumptions);

// Mapping of all booked dates per center
$discopeMap = [];

// delete unecessary fields & create a new Map
foreach ($mergedArray as $mappedMergedArray) {

    // delete unnecessary fields (for visibility)
    unset($mappedMergedArray['name']);
    unset($mappedMergedArray['state']);
    unset($mappedMergedArray['modified']);
    unset($mappedMergedArray['id']);
    unset($mappedMergedArray['center_id']);

    // Hestia objects (checks through the date field, if we are using a Hestia or Discope object (Discope objects haven't date_from, date_to))
    if (!isset($mappedMergedArray["date"])) {

        // other option to iterate all the selected dates
        // $dates = [strtotime($mappedMergedArray['date_from'])];
        // $date = strtotime($mappedMergedArray['date_from']);

        // while($date < strtotime($mappedMergedArray['date_to'])){
        //     $discopeMap[Date('Y-m-d', $date)] = true;
        //     $date += 86400;
        // }

        $date_from = Date('Y-m-d', strtotime($mappedMergedArray['date_from']));
        $date_to = Date('Y-m-d', strtotime($mappedMergedArray['date_to']));

        // find all dates in between two dates
        $period = new DatePeriod(
            new DateTime($date_from),
            new DateInterval('P1D'),
            new DateTime($date_to)
        );

        // iterate all the dates found and transfer them into an other table
        foreach ($period as $date) {
            $discopeMap[$date->format('Y-m-d')] = true;
        }
    // Discope objects
    } else {
        $discopeMap[Date('Y-m-d', $mappedMergedArray["date"])] = true;
    }
}




// function getHestiaJSON() {
//     return [
//         '4' => [
//             ['date_from' => 'UTC', 'date_to' => 'UTC']
//         ]
//     ];
// }