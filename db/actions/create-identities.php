<?php
/*
    This file is part of the eQual framework <http://www.github.com/cedricfrancoys/equal>
    Some Rights Reserved, Cedric Francoys, 2010-2021
    Licensed under GNU LGPL 3 license <http://www.gnu.org/licenses/>
*/

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader;
use PhpOffice\PhpSpreadsheet\Reader\IReadFilter;


use core\User;


list($params, $providers) = announce([
    'description'   => "Generate CSV files for pricelists, prices, product_models and products based on articles exports from Hestia.",
    'params'        => [],
    'response'      => [
        'accept-origin' => '*'
    ],
    'providers'     => ['context', 'orm', 'auth']
]);


list($context, $orm, $auth) = [$providers['context'], $providers['orm'], $providers['auth']];

$file = "packages/db/actions/GiGr_R_Client.csv";

// $partner_file = "packages/db/actions/identity_partner_test.csv";
// $file = glob($path);
if (!file_exists($file)) {
    throw new Exception();
}

// if(!file_exists($partner_file)){
//     throw new Exception();
// }

// global
global $partners, $identities, $customers;
global $identity_next_available_id;
global $languages, $customer_types;
global $CustomerNatures;

$partners = [];         // identity\Partner
$customers = [];        // sale\customer\Customer (inherits from identity\Partner)
$identities = [];       // lodging\identity\Identity

$identity_next_available_id = 15035000;


// load CustomerNatures
$CustomerNatures = [
    'AA' => ["id" => 1, "rate_class_id" => 3, "customer_type_id" => 1],
    'AC' => ["id" => 2, "rate_class_id" => 4, "customer_type_id" => 5],
    'AD' => ["id" => 3, "rate_class_id" => 3, "customer_type_id" => 1],
    'AL' => ["id" => 4, "rate_class_id" => 1, "customer_type_id" => 4],
    'AM' => ["id" => 5, "rate_class_id" => 4, "customer_type_id" => 1],
    'AN' => ["id" => 6, "rate_class_id" => 3, "customer_type_id" => 1],
    'AR' => ["id" => 7, "rate_class_id" => 2, "customer_type_id" => 4],
    'AS' => ["id" => 8, "rate_class_id" => 4, "customer_type_id" => 4],
    'AT' => ["id" => 9, "rate_class_id" => 3, "customer_type_id" => 1],
    'CC' => ["id" => 10, "rate_class_id" => 4, "customer_type_id" => 4],
    'CE' => ["id" => 11, "rate_class_id" => 1, "customer_type_id" => 4],
    'CH' => ["id" => 12, "rate_class_id" => 2, "customer_type_id" => 4],
    'CP' => ["id" => 13, "rate_class_id" => 1, "customer_type_id" => 5],
    'CS' => ["id" => 14, "rate_class_id" => 2, "customer_type_id" => 4],
    'EC' => ["id" => 15, "rate_class_id" => 5, "customer_type_id" => 4],
    'ED' => ["id" => 16, "rate_class_id" => 1, "customer_type_id" => 4],
    'EG' => ["id" => 17, "rate_class_id" => 4, "customer_type_id" => 4],
    'EM' => ["id" => 18, "rate_class_id" => 7, "customer_type_id" => 4],
    'EN' => ["id" => 19, "rate_class_id" => 4, "customer_type_id" => 3],
    'EP' => ["id" => 20, "rate_class_id" => 5, "customer_type_id" => 4],
    'ES' => ["id" => 21, "rate_class_id" => 5, "customer_type_id" => 4],
    'FA' => ["id" => 22, "rate_class_id" => 4, "customer_type_id" => 1],
    'FM' => ["id" => 23, "rate_class_id" => 4, "customer_type_id" => 4],
    'GA' => ["id" => 24, "rate_class_id" => 3, "customer_type_id" => 1],
    'GG' => ["id" => 25, "rate_class_id" => 3, "customer_type_id" => 1],
    'HA' => ["id" => 26, "rate_class_id" => 1, "customer_type_id" => 4],
    'HE' => ["id" => 27, "rate_class_id" => 4, "customer_type_id" => 4],
    'HO' => ["id" => 28, "rate_class_id" => 1, "customer_type_id" => 4],
    'IN' => ["id" => 30, "rate_class_id" => 4, "customer_type_id" => 1],
    'IP' => ["id" => 31, "rate_class_id" => 1, "customer_type_id" => 4],
    'JE' => ["id" => 33, "rate_class_id" => 1, "customer_type_id" => 4],
    'M3' => ["id" => 34, "rate_class_id" => 4, "customer_type_id" => 4],
    'MJ' => ["id" => 35, "rate_class_id" => 1, "customer_type_id" => 4],
    'MO' => ["id" => 36, "rate_class_id" => 1, "customer_type_id" => 4],
    'MU' => ["id" => 37, "rate_class_id" => 1, "customer_type_id" => 4],
    'OF' => ["id" => 38, "rate_class_id" => 4, "customer_type_id" => 5],
    'OJ' => ["id" => 39, "rate_class_id" => 1, "customer_type_id" => 4],
    'PR' => ["id" => 40, "rate_class_id" => 4, "customer_type_id" => 3],
    'SC' => ["id" => 41, "rate_class_id" => 1, "customer_type_id" => 4],
    'SI' => ["id" => 42, "rate_class_id" => 4, "customer_type_id" => 4],
    'SJ' => ["id" => 43, "rate_class_id" => 1, "customer_type_id" => 5],
    'SP' => ["id" => 45, "rate_class_id" => 5, "customer_type_id" => 4],
    'TC' => ["id" => 46, "rate_class_id" => 3, "customer_type_id" => 1],
    'TO' => ["id" => 47, "rate_class_id" => 6, "customer_type_id" => 3],
    'UC' => ["id" => 48, "rate_class_id" => 4, "customer_type_id" => 4],
    'US' => ["id" => 49, "rate_class_id" => 2, "customer_type_id" => 4],
    'PP' => ["id" => 50, "rate_class_id" => 4, "customer_type_id" => 4],
    'OT' => ["id" => 51, "rate_class_id" => 4, "customer_type_id" => 3],
    'ON' => ["id" => 52, "rate_class_id" => 4, "customer_type_id" => 4],
    'PM' => ["id" => 53, "rate_class_id" => 2, "customer_type_id" => 4]
];



$customer_types = [
    1 => 'I',
    2 => 'SE',
    3 => 'C',
    4 => 'NP',
    5 => 'PA'
];

$languages = [
    'EN' => 1,
    'FR' => 2,
    'NL' => 3
];



$data = loadCsvFile($file);


$date_last_import = strtotime('2021-08-03');

// main loop
foreach ($data as $row) {
    $date_created = adapt_date($row['Date_Creation']);
    $date_modif = adapt_date($row['Date_Modif']);

    if (!($date_created > $date_last_import || intval($row['Cle_Client']) > 15012971) || $row['Code_Motif_NPU'] != 0) {
        continue;
    }

    // entry is actually a Contact
    if (strlen($row['Societe_Client']) > 0) {

        // Create the used properties of the customer
        $nature = isset($CustomerNatures[$row['Nature_Client']]) ? $row['Nature_Client'] : 'IN';
        $nature_id = $CustomerNatures[$nature]['id'];
        $customer_type_id = $CustomerNatures[$nature]['customer_type_id'];
        $type = $customer_types[$customer_type_id];
        if($customer_type_id == 1){
            $customer_type_id = 3;
            $type = 'C';
        }
        $rate_class_id = $CustomerNatures[$nature]['rate_class_id'];
        $has_vat = isset($row['Libre3']) ? 1 : 0;
        $lang = strtolower($row['Langue_Client']);
        $lang_id = isset($languages[$lang]) ? $languages[$lang] : 1;
        $phone = (strlen($row['Tel_Mob_Payeur'])) ? $row['Tel_Mob_Payeur'] : $row['Tel_Payeur'];
        $email_payeur = str_replace("\r\n", "", $row['EMail1_Payeur']);
        // Create Client (Company)
        $customer_identity_id = create_identity(
            $type,
            $customer_type_id,
            $row['Societe_Client'],
            '',
            '',
            '',
            '',
            $phone,
            $email_payeur,
            $row['Adr3_Client'],
            $row['CP_Client'],
            $row['Ville_Client'],
            $row['Pays_Client'],
            $row['Libre3'],
            $has_vat,
            $row['Libre4'],
            $lang_id
        );

        // create customer relation
        create_customer(1, $customer_identity_id, $nature_id, $customer_type_id, $rate_class_id, 'customer', $lang_id);

        $firstname = $row['Prenom_Client'];
        $lastname = $row['Nom_Client'];
        $title = 'Mr';
        $gender = 'M';

        if (in_array($row['Titre_Client'], ['Fam', 'FAM.', 'M.', 'M', 'Her', 'Herr'])) {
            $title = 'Mr';
            $gender = 'M';
        } else if (in_array($row['Titre_Client'], ['Mme', 'Mevr', 'M&me'])) {
            $title = 'Mrs';
            $gender = 'F';
        }

        $phone = (strlen($row['Tel_Mob_Client'])) ? $row['Tel_Mob_Client'] : $row['Tel_Client'];
        $email = str_replace("\r\n", "", $row['Email1_Client']);
        // Create the contact of the Customer & checks if it does really exist
        if (strlen($row['Prenom_Client']) > 0 || strlen($row['Nom_Famille_Client']) > 0) {

            $contact_identity_id = create_identity(
                'I',
                1,
                $row['Prenom_Client'] . ' ' . $row['Nom_Famille_Client'],
                $row['Prenom_Client'],
                $row['Nom_Famille_Client'],
                $gender,
                $title,
                $phone,
                $email,
                '',
                '',
                '',
                'BE',
                '',
                false,
                '',
                $lang_id
            );

            // create contact relation
            create_partner($customer_identity_id, $contact_identity_id, 'contact', $lang_id, $row['Codif_Fonction_Client']);
        }
    }
    // entry is a regular customer
    else {

        $nature = isset($CustomerNatures[$row['Nature_Client']]) ? $row['Nature_Client'] : 'IN';
        $nature_id = $CustomerNatures[$nature]['id'];
        $customer_type_id = $CustomerNatures[$nature]['customer_type_id'];
        $type = $customer_types[$customer_type_id];
        $rate_class_id = $CustomerNatures[$nature]['rate_class_id'];
        $has_vat = isset($row['Libre3']) ? 1 : 0;
        $lang = strtolower($row['Langue_Client']);
        $lang_id = isset($languages[$lang]) ? $languages[$lang] : 1;
        $phone = (strlen($row['Tel_Mob_Client'])) ? $row['Tel_Mob_Client'] : $row['Tel_Client'];
        $email = str_replace("\r\n", "", $row['Email1_Client']);

        $firstname = $row['Prenom_Client'];
        $lastname = $row['Nom_Client'];
        $title = 'Mr';
        $gender = 'M';

        if (in_array($row['Titre_Client'], ['Fam', 'FAM.', 'M.', 'M', 'Her', 'Herr'])) {
            $title = 'Mr';
            $gender = 'M';
        } else if (in_array($row['Titre_Client'], ['Mme', 'Mevr', 'M&me'])) {
            $title = 'Mrs';
            $gender = 'F';
        }

        $phone = (strlen($row['Tel_Mob_Client'])) ? $row['Tel_Mob_Client'] : $row['Tel_Client'];

        if (strlen($row['Prenom_Client']) > 0 || strlen($row['Nom_Famille_Client']) > 0 || strlen($row['Nom_Client']) > 0) {

            $customer_identity_id = create_identity(
                $type,
                $customer_type_id,
                $row['Prenom_Client'] . ' ' . $row['Nom_Famille_Client'],
                $row['Prenom_Client'],
                $row['Nom_Famille_Client'],
                $gender,
                $title,
                $phone,
                $email,
                $row['Adr3_Client'],
                $row['CP_Client'],
                $row['Ville_Client'],
                $row['Pays_Client'],
                '',
                false,
                '',
                $lang_id
            );
            create_customer(1, $customer_identity_id, $nature_id, $customer_type_id, $rate_class_id, 'customer', $lang_id);
        }
    }
}


output_to_file("lodging_identity_Identity.json", "lodging\\identity\\Identity", $identities);
output_to_file("identity_Partner.json", "identity\\Partner", $partners);
output_to_file("sale_customer_Customer.json", "sale\\customer\\Customer", $customers);



function output_to_file($filename, $object, $array)
{


    // $output = $filename;
    // $fp = fopen($output, 'w');

    $json_var = [[
        "name" => $object,
        "lang" => "fr",
        "data" => $array
    ]];


    file_put_contents($filename, json_encode($json_var));

    // $first = reset($array);

    // $columns = array_keys($first);
    // // output BOM
    // fputs($fp, chr(0xEF) . chr(0xBB) . chr(0xBF) );
    // fputcsv($fp, $columns, ';');
    // foreach($array as $row) {
    //     fputcsv($fp, $row, ';');
    // }
    // fclose($fp);
}


$context->httpResponse()
    ->status(204)

    // ->body(['result' => [
    //     'partners'      => $partners,
    //     'identities'    => $identities
    // ]])

    ->send();


function loadCsvFile($file = '')
{

    $header = [];
    $rows = [];
    $row = 1;
    if (($handle = fopen($file, "r")) !== FALSE) {
        while (($data = fgetcsv($handle, 30000, ";")) !== FALSE) {
            if ($row == 1) {
                $data[0] = "Cle_Client";
                $header[] = $data;
            } else {
                $lines = array_combine($header[0], $data);
                $rows[] = $lines;
            }
            $row++;
        }

        fclose($handle);
        return $rows;
    }
}

function adapt_date($raw_date)
{
    $date_from_array = date_parse($raw_date);
    if ($date_from_array) {
        return strtotime(sprintf("%4d-%02d-%02d", intval($date_from_array['year']), intval($date_from_array['month']), intval($date_from_array['day'])));
    }
    return strtotime('2020-01-03');
}

function create_identity($type, $type_id, $legal_name, $firstname, $lastname, $gender, $title, $phone, $email, $address_street, $address_zip, $address_city, $address_country, $vat_number, $has_vat, $website, $lang_id)
{
    global $identities, $identity_next_available_id;

    $short_name = $legal_name;

    $identity = [
        'id'                => $identity_next_available_id,
        'type'              => $type,
        'type_id'           => $type_id,
        'legal_name'        => trim($legal_name),
        'short_name'        => trim($short_name),
        'firstname'         => trim($firstname),
        'lastname'          => trim($lastname),
        'gender'            => $gender,
        'title'             => $title,
        'phone'             => $phone,
        'email'             => trim($email),
        'address_street'    => trim($address_street),
        'address_zip'       => $address_zip,
        'address_city'      => trim($address_city),
        'address_country'   => trim($address_country),
        'vat_number'        => $vat_number,
        'has_vat'           => $has_vat,
        'website'           => trim($website),
        'lang_id'           => $lang_id
    ];

    $identities[] = $identity;

    ++$identity_next_available_id;

    return $identity_next_available_id;
}

function create_partner($owner_identity_id, $partner_identity_id,  $relationship, $lang_id, $partner_position)
{
    global $partners;

    // $customer_nature_id, $customer_type_id, $rate_class_id,

    foreach ($partners as $pid => $partner) {
        if ($partner['owner_identity_id'] == $owner_identity_id && $partner['partner_identity_id'] && $partner['relationship'] == $relationship) {
            return $pid;
        }
    }

    $partner = [
        'owner_identity_id'      => $owner_identity_id,
        'partner_identity_id'    => $partner_identity_id,
        'relationship'           => $relationship,
        'lang_id'                => $lang_id,
        'partner_position'       => $partner_position
    ];

    $partners[] = $partner;

    return $partner;
}

function create_customer($owner_identity_id, $partner_identity_id, $customer_nature_id, $customer_type_id, $rate_class_id, $relationship, $lang_id)
{
    global $customers;

    foreach ($customers as $pid => $customer) {
        if ($customer['partner_identity_id'] == $partner_identity_id) {
            return $pid;
        }
    }

    $customer = [
        'owner_identity_id'      => $owner_identity_id,
        'partner_identity_id'    => $partner_identity_id,
        'relationship'           => $relationship,
        'customer_nature_id'     => $customer_nature_id,
        'customer_type_id'       => $customer_type_id,
        'rate_class_id'          => $rate_class_id,
        'lang_id'                => $lang_id
    ];

    $customers[] = $customer;

    return $customers;
}




// $result = [];
    // $filetype = IOFactory::identify($file);
    // /** @var Reader */
    // $reader = IOFactory::createReader($filetype);
    // $spreadsheet = $reader->load($file);
    // $worksheetData = $reader->listWorksheetInfo($file);

    // foreach ($worksheetData as $worksheet) {

    //     $sheetname = $worksheet['worksheetName'];

    //     $reader->setLoadSheetsOnly($sheetname);
    //     $spreadsheet = $reader->load($file);

    //     $worksheet = $spreadsheet->getActiveSheet();
    //     $data = $worksheet->toArray();

    //     $header = array_shift($data);

    //     foreach($data as $raw) {
    //         $line = array_combine($header, $raw);
    //         $result[] = $line;
    //     }
    // }
    // return $result;
