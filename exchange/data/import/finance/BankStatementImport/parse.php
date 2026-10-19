<?php
/*
    Developed by Yesbabylon - https://yesbabylon.com
    (c) 2025-2026 Yesbabylon SA
    Licensed under the GNU AGPL v3 License - https://www.gnu.org/licenses/agpl-3.0.html
*/

use exchange\import\finance\BankStatementImport;

[$params, $providers] = eQual::announce([
    'description'   => 'Parse a bank statement import document into normalized statements.',
    'params'        => [
        'id' => [
            'type'              => 'many2one',
            'foreign_object'    => 'exchange\import\finance\BankStatementImport',
            'required'          => true
        ]
    ],
    'access'        => ['visibility' => 'protected'],
    'response'      => [
        'accept-origin' => '*',
        'content-type'  => 'application/json',
        'charset'       => 'utf-8'
    ],
    'providers'     => ['context']
]);

['context' => $context] = $providers;

$import = BankStatementImport::id($params['id'])
    ->read(['document_id' => ['name', 'content_type']])
    ->first();

if(!$import || !$import['document_id']) {
    throw new Exception('missing_import_document', EQ_ERROR_INVALID_PARAM);
}

$extension = strtolower(pathinfo($import['document_id']['name'], PATHINFO_EXTENSION));
if(in_array($extension, ['cod', 'coda', 'txt'], true)) {
    $parser = 'parse-coda';
}
elseif($extension === 'xlsx') {
    $parser = 'parse-xlsx';
}
else {
    throw new Exception('invalid_import_document_type', EQ_ERROR_INVALID_PARAM);
}

$result = eQual::run(
    'get',
    'exchange_import_finance_BankStatementImport_'.$parser,
    ['id' => $params['id']]
);

$context->httpResponse()
    ->body($result)
    ->send();
