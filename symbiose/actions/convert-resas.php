<?php
/*
    This file is part of the eQual framework <http://www.github.com/cedricfrancoys/equal>
    Some Rights Reserved, Cedric Francoys, 2010-2021
    Licensed under GNU LGPL 3 license <http://www.gnu.org/licenses/>
*/
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader;


use core\User;

list($params, $providers) = announce([
    'description'   => "Generate CSV files for pricelists, prices, product_models and products based on articles exports from Hestia.",
    'params'        => [
    ],
    'response'      => [
        'accept-origin' => '*'
    ],
    'providers'     => ['context', 'orm', 'auth']
]);


list($context, $orm, $auth) = [$providers['context'], $providers['orm'], $providers['auth']];



$next_available_identity_id = 15030001;
$next_available_partner_id = 81;
$next_available_address_id = 51;

// resulting objects maps
$partners  = [];        // either partners or customers (inherited class)
$identities = [];
$addresses = [];

$CustomerNatures = [];

$create_identity_individual = function($firstname, $lastname, $gender, $title, $phone, $email='') use (&$next_available_identity_id, &$identities){

    $identity = [
        'id'                 => '',
        'type'              => 'I',
        'firstname'         => $firstname,
        'lastname'          => $lastname,
        'gender'            => $gender,
        'title'             => $title,
        'email'             => $email,
        'legal_name'        => '',
        'address_street'    => '',
        'address_dispatch'  => '',        
        'address_zip'       => '',
        'address_city'      => '',
        'address_country'   => '',
        'phone'             => $phone,        
        'vat_number'        => ''
    ];

    $id = $next_available_identity_id;
    $identity['id'] = $id;
    $identities[$id] = $identity;
    ++$next_available_identity_id;
    return $id;
};

$create_customer = function($partner_identity_id, $nature_id, $type_id, $rate_class_id) use (&$partners, &$next_available_partner_id) {

    $owner_identity_id = 1;
    $relationship = 'customer';

    foreach($partners as $pid => $partner) {
        if($partner['owner_identity_id'] == $owner_identity_id
        && $partner['partner_identity_id'] == $partner_identity_id
        && $partner['relationship'] == $relationship) {
            echo "skipping $partner_identity_id found at $pid".PHP_EOL;
            return $pid;
        }
    }

    $id = $next_available_partner_id;

    $partner = [
       'id'                     => $id,
       'owner_identity_id'      => 1,
       'partner_identity_id'    => $partner_identity_id,
       'relationship'           => $relationship,
       'customer_nature_id'     => $nature_id,
       'customer_type_id'       => $type_id,
       'rate_class_id'          => $rate_class_id
    ];

    $partners[] = $partner;
    ++$next_available_partner_id;
    return $id;
};

$create_partner = function($owner_identity_id, $partner_identity_id, $relationship, $partner_position = '') use(&$partners, &$next_available_partner_id) {

    foreach($partners as $pid => $partner) {
        if($partner['owner_identity_id'] == $owner_identity_id 
        && $partner['partner_identity_id'] == $partner_identity_id
        && $partner['relationship'] == $relationship) {
            echo "skipping $partner_identity_id found at $pid".PHP_EOL;
            return $pid;
        }
    }

    $id = $next_available_partner_id;

    $partner = [
       'id'                     => $id,
       'owner_identity_id'      => $owner_identity_id,
       'partner_identity_id'    => $partner_identity_id,
       'relationship'           => $relationship,
       'customer_nature_id'     => '',
       'customer_type_id'       => '',
       'rate_class_id'          => ''
    ];

    $partners[] = $partner;
    ++$next_available_partner_id;
    return $id;
};

// create identity from R_Client
$create_identity_from_R_Client = function ($identity_id) use (&$R_Client, &$partners, &$identities, &$addresses, &$CustomerNatures, &$create_identity_from_R_Client, &$create_customer, &$create_partner, &$create_identity_individual, &$next_available_address_id) {

    $line = $R_Client[$identity_id];

    // create identity for Code_Payeur if any
    if($line['Cle_Client'] != $line['Code_Payeur']) {

        $create_identity_from_R_Client($line['Code_Payeur']);
        $partner_id = $create_partner($line['Cle_Client'], $line['Code_Payeur'], 'payer');
    }

    // create identity for Cle_Client
    if(!isset($identities[$identity_id])) {

        $identity = [];


        // entry is actually a Contact
        if(strlen($line['Societe_Client']) > 0) {

            $partner_position = $line['Codif_Fonction_Client'];

            $title = 'Mr';
            $gender = 'M';

            if(in_array($line['Titre_Client'], ['Fam', 'FAM.' , 'M.', 'M', 'Her', 'Herr' ])) {
                $title = 'Mr';
                $gender = 'M';
            }
            else if(in_array($line['Titre_Client'], ['Mme', 'Mevr', 'M&me'])) {
                $title = 'Mrs';
                $gender = 'F';
            }

            $firstname = $line['Prenom_Client'];
            $lastname = $line['Nom_Client'];
            $phone = $line['Tel_Mob_Client'];

            if(strlen($phone) <= 0) {
                $phone = $line['Tel_Client'];
            }

            $contact_identity_id = 0;
            foreach($identities as $c_id => $c_identity) {
                if(strcasecmp($c_identity['firstname'], $firstname) == 0 && strcasecmp($c_identity['lastname'], $lastname) == 0) {
                    $contact_identity_id = $c_id;
                    break;
                }
            }

            if(!$contact_identity_id) {
                $contact_identity_id = $create_identity_individual($firstname, $lastname, $gender, $title, $phone);
            }            
            $partner_id = $create_partner($line['Cle_Client'], $contact_identity_id, 'contact');

            $identity = [
                'id'                => '',
                'type'              => 'C',
                'firstname'         => '',
                'lastname'          => '',
                'gender'            => '',
                'title'             => '',
                'email'             => '',
                'legal_name'        => $line['Societe_Client'],
                'address_street'    => $line['Adr2_Client'],
                'address_dispatch'  => '',
                'address_zip'       => $line['CP_Client'],
                'address_city'      => $line['Ville_Client'],
                'address_country'   => $line['Pays_Client'],
                'phone'             => $line['Tel_Client'],
                'vat_number'        => $line['Libre3'],
            ];

            if(strlen($line['Adr3_Client'])) {
                if(strlen($identity['address_street']) <= 0) {
                    $identity['address_street'] = $line['Adr3_Client'];
                }
                else {
                    $identity['address_dispatch'] = $line['Adr3_Client'];
                }
            }

            if(strlen($line['Adr4_Client'])) {
                if(strlen($identity['address_street']) <= 0) {
                    $identity['address_street'] = $line['Adr4_Client'];
                }
                else {
                    $identity['address_dispatch'] = $line['Adr4_Client'];
                }
            }

            $nature_index = array_search($line['Nature_Client'], array_column($CustomerNatures, 'Nature_Client'));
            $nature = $CustomerNatures[$nature_index];

            $nature_id = $nature['id'];
            $type_id = $nature['customer_type_id'];
            $rate_class_id = $nature['Cat_Tarif'];

            $identity['id'] = $identity_id;
            $identities[$identity_id] = $identity;
            $partner_id = $create_customer($identity_id, $nature_id, $type_id, $rate_class_id);
        }
        else {
            // create invoicing address
            if(strlen($line['Societe_Payeur']) > 0) {

                $address = [
                    'id'                => '',
                    'identity_id'       => $line['Cle_Client'],
                    'role'              => 'invoice',
                    'address_dispatch'  => $line['Societe_Payeur'] ,
                    'address_street'    => $line['Adr3_Payeur'],
                    'address_zip'       => $line['CP_Payeur'],
                    'address_city'      => $line['Ville_Payeur'],
                    'address_country'   => $line['Pays_Payeur']
                ];

                $skip = false;

                if(strlen($address['address_street']) == 0) {
                    $address['address_street'] = $line['Adr2_Payeur'];
                }
                if(strlen($address['address_street']) == 0) {
                    $address['address_street'] = $line['Adr1_Payeur'];                    
                }
                if(strlen($address['address_street']) == 0) {
                    $skip = true;
                }
                else {
                    if(strlen($address['address_zip']) == 0) {
                        $skip = true;
                    }
                }

                if(!$skip) {
                    foreach($addresses as $oid => $addr) {
                        if($addr['identity_id'] == $line['Cle_Client']                
                        && $addr['role'] == 'invoice') {
                            echo "skipping address for {$line['Cle_Client']} found at $oid".PHP_EOL;
                            $skip = true;
                            break;
                        }
                    }    
                }

                if(!$skip) {
                    $address_id = $next_available_address_id;
                    $address['id'] = $address_id;
                    $addresses[$address_id] = $address;
                    ++$next_available_address_id;
                }
            }
            else {
                // simple identity

                $title = 'Mr';
                $gender = 'M';

                if(in_array($line['Titre_Client'], ['Fam', 'FAM.' , 'M.', 'M', 'Her', 'Herr' ])) {
                    $title = 'Mr';
                    $gender = 'M';
                }
                else if(in_array($line['Titre_Client'], ['Mme', 'Mevr', 'M&me'])) {
                    $title = 'Mrs';
                    $gender = 'F';
                }

                $phone = $line['Tel_Client'];
                if(strlen($line['Tel_Mob_Client']) > 0) {
                    $phone = $line['Tel_Mob_Client'];
                }

                $identity = [
                    'id'                => '',
                    'type'              => 'I',
                    'firstname'         => $line['Prenom_Client'],
                    'lastname'          => $line['Nom_Famille_Client'],
                    'gender'            => $gender,
                    'title'             => $title,
                    'email'             => $line['Email1_Client'],
                    'legal_name'        => '',
                    'address_street'    => $line['Adr1_Client'],
                    'address_dispatch'  => '',
                    'address_zip'       => $line['CP_Client'],
                    'address_city'      => $line['Ville_Client'],
                    'address_country'   => $line['Pays_Client'],
                    'phone'             => $phone,
                    'vat_number'        => ''
                ];

                if( !filter_var($identity['email'], FILTER_VALIDATE_EMAIL)  ) {
                    if(strlen($identity['phone']) == 0 && is_numeric($identity['email'])) {
                        $identity['phone'] = $identity['email'];
                    }                    
                    $identity['email'] = '';
                }

                if(strlen($line['Adr3_Client'])) {
                    if(strlen($identity['address_street']) <= 0) {
                        $identity['address_street'] = $line['Adr3_Client'];
                    }
                    else {
                        $identity['address_dispatch'] = $line['Adr3_Client'];
                    }
                }

                if(strlen($line['Adr4_Client'])) {
                    if(strlen($identity['address_street']) <= 0) {
                        $identity['address_street'] = $line['Adr4_Client'];
                    }
                    else {
                        $identity['address_dispatch'] = $line['Adr4_Client'];
                    }
                }

                // we ignore any additional contact info (Email2_Client)

                $nature_index = array_search($line['Nature_Client'], array_column($CustomerNatures, 'Nature_Client'));
                $nature = $CustomerNatures[$nature_index];

                $nature_id = $nature['id'];
                $type_id = $nature['customer_type_id'];
                $rate_class_id = $nature['Cat_Tarif'];

                $identity['id'] = $identity_id;
                $identities[$identity_id] = $identity;

                $partner_id = $create_customer($identity_id, $nature_id, $type_id, $rate_class_id);

            }

        }


    }

};













// charger tous les fichiers R_Articles.csv

$path = "packages/symbiose/test/";






// read R_client.csv file
$rows_clients = loadCsvFile($path.'GiGr_R_Client.csv');

// mapped on Cle_Client
$R_Client = [];
foreach($rows_clients as $odata) {
    $R_Client[$odata['Cle_Client']] = $odata;
}

// load CustomerNatures
$CustomerNatures = loadXlsFile($path.'P_NatCli.csv');


$list = glob($path.'*_M_Resa.csv');

foreach($list as $file) {
    // read x_M_Resa.csv file
    $rows = loadXlsFile($file);
    // parse rows from R_Resa
    foreach($rows as $row) {

        // Cle_Client and Cle_Client_Payeur are distinct
        if($row['Cle_Client'] != $row['Cle_Client_Payeur']) {
            echo "create identity for the payer".PHP_EOL;
            /*
            Cle_Client is the client
            Cle_client_Payeur is Partner of Cle_client (relationship = 'payer')
            */

            // create a relationship between Client and Payer
            $create_identity_from_R_Client($row['Cle_Client_Payeur']);
            $partner_id = $create_partner($row['Cle_Client'], $row['Cle_Client_Payeur'], 'payer');


            // create an identity for the Client
            $nature_index = array_search($row['Nature_Client'], array_column($CustomerNatures, 'Nature_Client'));
            $nature = $CustomerNatures[$nature_index];
            $nature_id = $nature['id'];
            $type_id = $nature['customer_type_id'];
            $rate_class_id = $nature['Cat_Tarif'];

            // create an identity
            $create_identity_from_R_Client($row['Cle_Client']);
            // create partner relationship
            $create_customer($row['Cle_Client'], $nature_id, $type_id, $rate_class_id);
        }
        // Cle_Client and Cle_Client_Payeur are the same
        else {
            if(strlen($row['Societe_Payeur']) > 0) {
                // Cle_Client is the Client AND Cle_Client_Payeur is a Contact
                echo "entry is a contact".PHP_EOL;

                $partner_position = $row['Codif_Fonction_Payeur'];

                $title = 'Mr';
                $gender = 'M';

                if(in_array($row['Titre_Payeur'], ['Fam', 'FAM.' , 'M.', 'M', 'Her', 'Herr' ])) {
                    $title = 'Mr';
                    $gender = 'M';
                }
                else if(in_array($row['Titre_Payeur'], ['Mme', 'Mevr', 'M&me'])) {
                    $title = 'Mrs';
                    $gender = 'F';
                }

                $firstname = $row['Prenom_Payeur'];
                $lastname = $row['Nom_Famille_Payeur'];
                $phone = $row['Tel_Mob_Payeur'];

                if(strlen($phone) <= 0) {
                    $phone = $row['Tel_Payeur'];
                }

                $contact_identity_id = 0;
                foreach($identities as $c_id => $c_identity) {
                    if(strcasecmp($c_identity['firstname'], $firstname) == 0 && strcasecmp($c_identity['lastname'], $lastname) == 0) {
                        $contact_identity_id = $c_id;
                        break;
                    }
                }
                if(!$contact_identity_id) {
                    $contact_identity_id = $create_identity_individual($firstname, $lastname, $gender, $title, $phone);                    
                }

                $partner_id = $create_partner($row['Cle_Client_Payeur'], $contact_identity_id, 'contact');

                $identity = [
                    'id'                => '',
                    'type'              => 'C',
                    'firstname'         => '',
                    'lastname'          => '',
                    'gender'            => '',
                    'title'             => '',
                    'email'             => '',
                    'legal_name'        => $row['Societe_Payeur'],
                    'address_street'    => $row['Adr2_Payeur'],
                    'address_dispatch'  => '',
                    'address_zip'       => $row['CP_Payeur'],
                    'address_city'      => $row['Ville_Payeur'],
                    'address_country'   => $row['Pays_Payeur'],
                    'phone'             => $row['Tel_Payeur'],
                    'vat_number'        => $row['Libre3']
                ];
                

                if(strlen($row['Adr3_Payeur'])) {
                    if(strlen($identity['address_street']) <= 0) {
                        $identity['address_street'] = $row['Adr3_Payeur'];
                    }
                    else {
                        $identity['address_dispatch'] = $row['Adr3_Payeur'];
                    }
                }

                if(strlen($row['Adr4_Payeur'])) {
                    if(strlen($identity['address_street']) <= 0) {
                        $identity['address_street'] = $row['Adr4_Payeur'];
                    }
                    else {
                        $identity['address_dispatch'] = $row['Adr4_Payeur'];
                    }
                }

                $nature_index = array_search($row['Nature_Client'], array_column($CustomerNatures, 'Nature_Client'));
                if($nature_index === false) {
                    echo "unknown nature {$row['Nature_Client']}".PHP_EOL;
                    die();
                }

                $nature = $CustomerNatures[$nature_index];

                $nature_id = $nature['id'];
                $type_id = $nature['customer_type_id'];
                $rate_class_id = $nature['Cat_Tarif'];

                $identity['id'] = $row['Cle_Client'];
                $identities[$row['Cle_Client']] = $identity;
                $partner_id = $create_customer($row['Cle_Client'], $nature_id, $type_id, $rate_class_id);
            }
            else {
                echo "entry is an address".PHP_EOL;
                // record is an address : either a duplicate of the original, or an additional invoice address

                $create_identity_from_R_Client($row['Cle_Client']);

                $address = [
                    'id'                => '',
                    'role'              => 'invoice',
                    'identity_id'       => $row['Cle_Client'],
                    'address_street'    => $row['Adr3_Payeur'],
                    'address_zip'       => $row['CP_Payeur'],
                    'address_city'      => $row['Ville_Payeur'],
                    'address_country'   => $row['Pays_Payeur']
                ];

                $skip = false;

                if(strlen($address['address_street']) == 0) {
                    $address['address_street'] = $row['Adr2_Payeur'];
                }
                if(strlen($address['address_street']) == 0) {
                    $address['address_street'] = $row['Adr1_Payeur'];                    
                }
                if(strlen($address['address_street']) == 0) {
                    $skip = true;
                }
                else {
                    if(strlen($address['address_zip']) == 0) {
                        $skip = true;
                    }
                }

                if(!$skip) {

                    foreach($addresses as $oid => $addr) {
                        if($addr['identity_id'] == $row['Cle_Client']                
                        && $addr['role'] == 'invoice') {
                            echo "skipping address for {$row['Cle_Client']} found at $oid".PHP_EOL;
                            $skip = true;
                            break;
                        }
                    }
                }

                if(!$skip) {
                    $address_id = $next_available_address_id;
                    $address['id'] = $address_id;
                    $addresses[$address_id] = $address;
                    ++$next_available_address_id;
                }

            }
        }


    }

}






echo "finished !".PHP_EOL;


// append lines to related files

file_put_contents("result_partners.csv", "\xEF\xBB\xBF".'"id";"owner_identity_id";"partner_identity_id";"relationship";"customer_nature_id";"customer_type_id";"rate_class_id"'.PHP_EOL);
foreach($partners as $partner) {
    $line = '"'.implode('";"', $partner).'"'.PHP_EOL;
    file_put_contents($path."result_partners.csv", $line, FILE_APPEND);
}

file_put_contents("result_identities.csv", "\xEF\xBB\xBF".'"id";"type";"firstname";"lastname";"gender";"title";"email";"legal_name";"address_street";"address_dispatch";"address_zip";"address_city";"address_country";"phone";"vat_number"'.PHP_EOL);
foreach($identities as $identity) {
    $line = '"'.implode('";"', $identity).'"'.PHP_EOL;
    file_put_contents($path."result_identities.csv", $line, FILE_APPEND);
}

file_put_contents("result_addresses.csv", "\xEF\xBB\xBF".'"id";"role";"identity_id";"address_street";"address_zip";"address_city";"address_country"'.PHP_EOL);
foreach($addresses as $address) {
    $line = '"'.implode('";"', $address).'"'.PHP_EOL;
    file_put_contents($path."result_addresses.csv", $line, FILE_APPEND);
}



function loadCsvFile($file='') {
    if(!file_exists($file)) {
        echo "file $file not found".PHP_EOL;
        die();
    }

    $result = [];
    $lines = [];

    if (($handle = fopen($file, "r")) !== FALSE) {

        while (($raw_string = fgets($handle)) !== false) {
            $line = str_getcsv($raw_string, ";");
            $lines[] = $line;
        }
        fclose($handle);
    }
    $header = array_shift($lines);


    if(substr($header[0], 0, 3)==chr(hexdec('EF')).chr(hexdec('BB')).chr(hexdec('BF'))){
        $header[0] = trim(substr($header[0], 3), '"');
    }

    foreach($lines as $index => $raw) {
        if(count($header) != count($raw)) {
            echo "error distinct counts for $file at $index".PHP_EOL;
            die();
        }
        $line = array_combine($header, $raw);
        $result[] = $line;
    }

    return $result;
}



function loadXlsFile($file='') {
    echo "loading $file".PHP_EOL;

    if(!file_exists($file)) {
        echo "file $file not found".PHP_EOL;
        die();
    }

    $result = [];

    try {


        $filetype = IOFactory::identify($file);
        /** @var Reader */
        $reader = IOFactory::createReader($filetype);
        $spreadsheet = $reader->load($file);
        $worksheetData = $reader->listWorksheetInfo($file);

        foreach ($worksheetData as $worksheet) {

            $sheetname = $worksheet['worksheetName'];

            $reader->setLoadSheetsOnly($sheetname);
            $spreadsheet = $reader->load($file);

            $worksheet = $spreadsheet->getActiveSheet();
            $data = $worksheet->toArray();

            $header = array_shift($data);

            foreach($data as $raw) {
                $line = array_combine($header, $raw);
                $result[] = $line;
            }
        }
    }
    catch(Exception $e) {
        echo "unexpected error".PHP_EOL;
        echo $e->getMessage();
    }
    return $result;
}