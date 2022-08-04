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

$file = "packages/db/actions/test.csv";

$partner_file = "packages/db/actions/identity_partner_test.csv";
// $file = glob($path);
if(!file_exists($file)){
    throw new Exception();
}

if(!file_exists($partner_file)){
    throw new Exception();
}
$partners  = [];        // either partners or customes (inherited class)
$identities = [];

$ids = [];


$data = loadXlsFile($file);

foreach($data as $row) {

    $date_from_array = date_parse($row['Date_Creation']);

    $date_to_array = date_parse($row['Date_Modif']);     
 
    if(strlen($date_from_array['month']) == 1){
        $month = '0'.$date_from_array['month'];
    }

    if(strlen($date_from_array['day']) == 1){
        $day = '0'.$date_from_array['day'];
    }

    if(strlen($date_to_array['day']) == 1){
        $day_to = '0'.$date_to_array['day'];
    }

    if(strlen($date_to_array['month_to']) == 1){
        $month_to = '0'.$date_to_array['month_to'];
    }

    $date_from = strtotime($date_from_array['year'].'-'.$month.'-'.$day);
    $date_to = strtotime($date_to_array['year'].'-'.$month_to.'-'.$day_to);
    $date_last_import = strtotime('2021-08-03');

    
    if(($date_to > $date_last_import || $row['Cle_Client'] > 15012971) && $row['Code_Motif_NPU'] == 0){

        array_push($ids, $row['Client_Cle']);
        // entry is actually a Contact
        if(strlen($row['Societe_Client']) > 0) {
            
            $partner_position = $row['Codif_Fonction_Client'];
            
            $title = 'Mr';
            $gender = 'M';
            
            if(in_array($row['Titre_Client'], ['Fam', 'FAM.' , 'M.', 'M', 'Her', 'Herr' ])) {
                $title = 'Mr';
                $gender = 'M';
            }
            else if(in_array($row['Titre_Client'], ['Mme', 'Mevr', 'M&me'])) {
                $title = 'Mrs';
                $gender = 'F';
            }
            
            $firstname = $row['Prenom_Client'];
            $lastname = $row['Nom_Client'];
            $phone = $row['Tel_Mob_Client'];
            
            if(strlen($phone) <= 0) {
                $phone = $row['Tel_client'];
            }
            
            // $contact_identity_id = create_identity_individual($firstname, $lastname, $gender, $title, $phone, 15030001, $identities);
            // $partner_id = create_partner($row['Cle_Client'], $contact_identity_id, 'contact', $partner_position, $partners);


            $nature_id = 1;
            if(isset($CustomerNatures[$row['Nature_Client']])){
                $custom = $CustomerNatures[$row['Nature_Client']];
                $nature_id = $custom['id'];
                $type = $row['Nature_Client'];
                $type_id = $custom['customer_type_id'];
                $rate_class_id = $custom['rate_class_id'];
            }

            $has_vat = 0;
            if(isset($row['Libre3'])){
                $has_vat = 1;
            }
        
            $identity = [
                'id'                => $row['Cle_Client'],
                'type'              => $type,
                'type_id'           => $type_id,
                'legal_name'        => $row['Societe_Client'],
                'address_street'    => $row['Adr2_Client'],
                'address_zip'       => $row['CP_Client'], 
                'address_city'      => $row['Ville_Client'], 
                'address_country'   => $row['Pays_Client'], 
                'phone'             => $row['Tel_Client'],
                'vat_number'        => $row['Libre3'],
                'has_vat'           => $has_vat,
                'email'             => $row['EMail1_Payeur'],
                'website'           => $row['0.0'],
                'lang'              => $row['Langue_Client'],
                'lang_id'           => $languages[$row['Langue_Client']]
            ];

            if(strlen($row['Adr3_Client'])) {
                if(strlen($identity['address_street']) <= 0) {
                    $identity['address_street'] = $row['Adr3_Client'];
                }
                else {
                    $identity['address_dispatch'] = $row['Adr3_Client'];
                }
            }

            if(strlen($row['Adr4_Client'])) {
                if(strlen($identity['address_street']) <= 0) {
                    $identity['address_street'] = $row['Adr4_Client'];
                }
                else {
                    $identity['address_dispatch'] = $row['Adr4_Client'];
                }                
            }

           
            
            // $nature_index = array_search($row['Nature_Client'], array_column($CustomerNatures, 'code'));

            


            // $nature = $CustomerNatures[$nature_index];
            
            // $nature_id = $nature['id'];
           

            $identities[] = $identity;
            // $identity_id = $row['Cle_Client'];
            $partner_id = create_customer($identity, $nature_id, $type_id, $rate_class_id, $partny, $partners_file_array, 'contact');
        }
        else {
            // create invoicing address
            // if(strlen($row['Societe_Payeur']) > 0) {
                
            //     $address = [
            //         'identity_id'       => $row['Cle_Client'],
            //         'role'              => 'invoice',
            //         'address_dispatch'  => $row['Societe_Payeur'] ,
            //         'address_street'    => $row['Adr3_Payeur'],
            //         'address_zip'       => $row['CP_Payeur'],
            //         'address_city'      => $row['Ville_Payeur'],
            //         'address_country'   => $row['Pays_Payeur'],
            //         'email'             => $row['EMail1_Payeur']
            //     ];
                
            //     $address_id = count($addresses) + 1;
            //     $addresses[$address_id] = $address;
            // }
            // else {
                // simple identity
                
                $title = 'Mr';
                $gender = 'M';
                
                if(in_array($row['Titre_Client'], ['Fam', 'FAM.' , 'M.', 'M', 'Her', 'Herr' ])) {
                    $title = 'Mr';
                    $gender = 'M';
                }
                else if(in_array($row['Titre_Client'], ['Mme', 'Mevr', 'M&me'])) {
                    $title = 'Mrs';
                    $gender = 'F';
                }
                
                $phone = $row['Tel_Client'];
                if(strlen($row['Tel_Mob_Client']) > 0) {
                    $phone = $row['Tel_Mob_Client'];
                }

                $nature_id = 1;
                if(isset($CustomerNatures[$row['Nature_Client']])){
                    $custom = $CustomerNatures[$row['Nature_Client']];
                    $nature_id = $custom['id'];
                    $type = $row['Nature_Client'];
                    $type_id = $custom['customer_type_id'];
                    $rate_class_id = $custom['rate_class_id'];
                }
                                
                $identity = [
                    'id'        => $row['Cle_Client'],
                    'type'      => $type,
                    'type_id'   => $type_id,
                    'firstname' => $row['Prenom_Client'],
                    'lastname'  => $row['Nom_Famille_Client'],
                    'gender'    => $gender,
                    'title'     => $title,
                    'phone'     => $phone,
                    'email'     => $row['Email1_Client'],
                    'website'           => $row['0.0'],
                    'address_dispatch'  => $row['Societe_Payeur'] ,
                    'address_street'    => $row['Adr3_Payeur'],
                    'address_zip'       => $row['CP_Payeur'],
                    'address_city'      => $row['Ville_Payeur'],
                    'address_country'   => $row['Pays_Payeur'],
                    'lang'              => $row['Langue_Client'],
                    'lang_id'           => $languages[$row['Langue_Client']]
                ];
                
                if(strlen($row['Adr3_Client'])) {
                    if(strlen($identity['address_street']) <= 0) {
                        $identity['address_street'] = $row['Adr3_Client'];
                    }
                    else {
                        $identity['address_dispatch'] = $row['Adr3_Client'];
                    }
                }

                if(strlen($row['Adr4_Client'])) {
                    if(strlen($identity['address_street']) <= 0) {
                        $identity['address_street'] = $row['Adr4_Client'];
                    }
                    else {
                        $identity['address_dispatch'] = $row['Adr4_Client'];
                    }
                }

                // we ignore any additional contact info (Email2_Client) 

                // $nature_index = array_search($row['Nature_Client'], array_column($CustomerNatures, 'code'));
                // $nature = $CustomerNatures[$nature_index];
                
                // $nature_id = $nature['id'];
                // $type_id = $nature['customer_type_id'];
                // $rate_class_id = $nature['rate_class_id'];

                
                    
                $identities[] = $identity;
                // $identity_id = $row['Cle_Client'];
                $partner_id = create_customer($identity, $nature_id, $type_id, $rate_class_id, $partners, $partners_file_array, 'customer');

                //else invoice adress
            // }
            
        }
    }
    // if not in db
}
            

        // function create_identity_individual($firstname, $lastname, $gender, $title, $phone, $email='', $next_available_identity_id, $identities) {
        //     // use(&$next_available_identity_id, &$identities)
        //     $identity = [
        //         'type'      => 'I',
        //         'firstname' => $firstname,
        //         'lastname'  => $lastname,
        //         'gender'    => $gender,
        //         'title'     => $title,
        //         'phone'     => $phone,
        //         'email'     => $email
        //     ];
            
        //     $id = $next_available_identity_id;
        //     $identities[$id] = $identity;
        //     ++$next_available_identity_id;
        //     return $id;
        // };

        function create_customer($partner_identity_id, $nature_id, $type_id, $rate_class_id, $partners, $partners_file_array, $relationship)  {

            $owner_identity_id = 1;
            
            // foreach($partners_file_array as $pid => $partner) {
            //     if($partner['owner_identity_id'] == $owner_identity_id && $partner['partner_identity_id'] && $partner['relationship'] == $relationship) {
            //         return $pid;
            //     }
            // }

            $id = count($partners) + count($partners_file_array) + 1;
            
            $partner = [
            'id'                     => $id, 
            'owner_identity_id'      => 1, 
            'partner_identity_id'    => $partner_identity_id['id'],
            'relationship'           => $relationship,
            'customer_nature_id'     => $nature_id,
            'customer_type_id'       => $type_id,
            'rate_class_id'          => $rate_class_id,
            'lang'                   => $partner_identity_id['lang'],
            'lang_id'                => $partner_identity_id['lang_id']
            ];

            
            $partners[] = $partner;
            return $id;
        };
        

            
    //    function create_partner($owner_identity_id, $partner_identity_id, $relationship, $partner_position = '', $partners)  {
    //         foreach($partners as $pid => $partner) {
    //             if($partner['owner_identity_id'] == $owner_identity_id && $partner['partner_identity_id'] && $partner['relationship'] == $relationship) {
    //                 return $pid;
    //             }
    //         }
            
    //         $id = count($partners) + 1;
            
    //         $partner = [
    //         'id'                     => $id, 
    //         'owner_identity_id'      => $owner_identity_id, 
    //         'partner_identity_id'    => $partner_identity_id,
    //         'relationship'           => $relationship
    //         ];
            
    //     return $id;
    //     };

    

        

        






    





    

$context->httpResponse()
        ->body(['ok'])
        ->send();


function loadXlsFile($file='') {
    $result = [];
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
    return $result;
}