<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
use lodging\sale\booking\BankStatement;


list($params, $providers) = announce([
    'description'   => "Export a zip archive containing all reconciled bank statements for importing in an external accounting software.",
    'access' => [
        'visibility'        => 'public',
        'groups'            => ['sale.default.user'],
    ],
    'response'      => [
        'content-type'        => 'application/zip',
        'content-disposition' => 'attachment; filename="export.zip"',
        'charset'             => 'utf-8',
        'accept-origin'       => '*'
    ],
    'providers'     => ['context', 'orm', 'auth']
]);

list($context, $orm, $auth) = [$providers['context'], $providers['orm'], $providers['auth']];


$statements = BankStatement::search([['status', '=', 'reconciled'], ['is_exported', '=', false]])
  ->read(['name', 'raw_data', 'status', 'statement_lines_ids' => ['account_iban', 'customer_id' => ['id', 'ref_account'] ]])
  ->get();


if($statements) {

    // create zip archive
    $tmpfile = tempnam(sys_get_temp_dir(), "zip");
    $zip = new ZipArchive();
    $zip->open($tmpfile, ZipArchive::CREATE | ZipArchive::OVERWRITE);


    foreach($statements as $statement) {
        $coda = $statement['raw_data'];
        // adapt account numbers with customers ref_account
        foreach($statement['statement_lines_ids'] as $lid => $line) {
            if( $line['customer_id'] ) {    
                $ref_account = $line['customer_id']['ref_account'];
                if(!$ref_account) {
                    $ref_account = $line['account_iban'];
                    $orm->write('sale\customer\Customer', $line['customer_id']['id'], ['ref_account' => $ref_account]);
                }
                else {
                    $coda = str_replace($line['account_iban'], $ref_account, $coda);
                }    
            }
        }
        // add statement as CODA file to the archive
        $zip->addFromString($statement['name'].'.cod', $coda);
        // mark statement as exported
    BankStatement::id($sattement['id'])->update(['is_exported' => true]);
    }
    $zip->close();
    $data = file_get_contents($tmpfile);
    unlink($tmpfile);   
}

$context->httpResponse()
        ->body($data)
        ->send();