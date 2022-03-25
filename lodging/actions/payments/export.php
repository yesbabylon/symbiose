<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

use lodging\identity\CenterOffice;
use lodging\sale\booking\BankStatement;
use lodging\sale\booking\BankStatementLine;
use sale\customer\Customer;

list($params, $providers) = announce([
    'description'   => "Export a reconciled bank statement file for importing in an external accounting software.",
    'params'        => [
        'id' =>  [
            'description'   => 'identifier of the BankStatement to export.',
            'type'          => 'integer',
            'required'      => true
        ]
    ],
    'access' => [
        'visibility'        => 'public',
        'groups'            => ['sale.default.user'],
    ],
    'response'      => [
        'content-type'        => 'text/plain',
        'content-disposition' => 'attachment; filename="export.coda"',
        'charset'             => 'utf-8',
        'accept-origin'       => '*'
    ],
    'providers'     => ['context', 'orm', 'auth']
]);

list($context, $orm, $auth) = [$providers['context'], $providers['orm'], $providers['auth']];

$statement = BankStatement::id($params['id'])
                          ->read(['raw_data', 'status', 'statement_lines_ids' => ['account_iban', 'customer_id' => ['id', 'ref_account'] ]])
                          ->first();

if(!$statement) {
  throw new Exception('unknown_statement', QN_ERROR_INVALID_PARAM);
}

if($statement['status'] != 'reconciled') {
  throw new Exception('statement_not_ready', QN_ERROR_NOT_ALLOWED);
}

$coda = $statement['raw_data'];

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

$context->httpResponse()
        ->body($coda)
        ->send();