<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader;


list($params, $providers) = announce([
    'description'   => "Imports the composition (hosts listing) for a given booking. If a composition already exists, it is reset.",
    'params'        => [
        'data' =>  [
            'description'   => 'XLSX file holding the data to import as composition.',
            'type'          => 'file',
            'required'      => true
        ],
        'booking_id' =>  [
            'description'   => 'Identifier of the booking to which the composition relates.',
            'type'          => 'integer',
            'required'      => true
        ]
    ],
    'access' => [
        'visibility'        => 'public',		// 'public' (default) or 'private' (can be invoked by CLI only)		
        'groups'            => ['booking.default.user'],// list of groups ids or names granted 
    ],
    'response'      => [
        'content-type'  => 'application/json',
        'charset'       => 'utf-8',
        'accept-origin' => '*'
    ],
    'providers'     => ['context', 'orm', 'auth'] 
]);


list($context, $orm, $auth) = [$providers['context'], $providers['orm'], $providers['auth']];

$user_id = $auth->userId();

if($user_id <= 0) {
    // non permitted for unidentified users
    throw new Exception('unknown_user', QN_ERROR_NOT_ALLOWED);
}

$content = $params['data'];
$size = strlen($content);

// retrieve content_type from MIME
$finfo = new finfo(FILEINFO_MIME);    
$content_type = explode(';', $finfo->buffer($content))[0];

if($content_type != "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet") {
    throw new Exception('unknown_content_type', QN_ERROR_INVALID_PARAM);
}

$filename = 'bin/'.uniqid(rand(), true).'.xlsx';
file_put_contents($filename, $content);

/** @var string */
$filetype = IOFactory::identify($filename);
/** @var Reader */
$reader = IOFactory::createReader($filetype);
$spreadsheet = $reader->load($filename);
$worksheetData = $reader->listWorksheetInfo($filename);


$objects = [];

foreach ($worksheetData as $worksheet) {

    $sheetname = $worksheet['worksheetName'];
    
    $reader->setLoadSheetsOnly($sheetname);
    $spreadsheet = $reader->load($filename);
    
    $worksheet = $spreadsheet->getActiveSheet();
    $data = $worksheet->toArray();

    for($i = 0; $i < 17; ++$i) {
        array_shift($data) ;
    }

    $columns = [
        0 => 'id',
        1 => 'lastname',
        2 => 'firstname',
        3 => 'date_of_birth',
        4 => 'gender',
        5 => 'nationality',
        6 => 'registration_number',
        7 => 'address',
        8 => 'address_zip',   
        9 => 'address_city',
        10 => 'phone',
        11 => 'email',
        12 => 'remainder'
    ];

    foreach($data as $index => $raw) {

        $line = array_combine($columns, $raw);

        if(strlen($line['lastname']) == 0) {
            continue;
        }

        unset($line['id']);
        unset($line['remainder']);

        // force country (no column)
        $line['country'] = 'BE';

        // adapt dates
        if(isset($line['date_of_birth'])) {
            $date_parts = explode('/', $line['date_of_birth']);
            if(count($date_parts) >= 3) {
                $line['date_of_birth'] = strtotime(sprintf("%04d-%02d-%02d", $date_parts[2], $date_parts[0], $date_parts[1]));
            }
        }

        $objects[] = $line;
    }
}



// delete temp file
unlink($filename);

// get classes listing
$json = run('do', 'lodging_composition_generate', ['booking_id' => $params['booking_id'], 'data' => $objects]);
$data = json_decode($json, true);

// relay error if any
if(isset($data['errors'])) {
    foreach($data['errors'] as $name => $message) throw new Exception($message, qn_error_code($name));
}

$context->httpResponse()
        // ->status(204)
        ->status(200)
        ->body($objects)
        ->send();