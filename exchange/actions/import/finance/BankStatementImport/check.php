<?php
/*
    Developed by Yesbabylon - https://yesbabylon.com
    (c) 2025-2026 Yesbabylon SA
    Licensed under the GNU AGPL v3 License - https://www.gnu.org/licenses/agpl-3.0.html
*/

use exchange\import\finance\BankStatementImport;

[$params, $providers] = eQual::announce([
    'description'   => 'Check a bank statement import document.',
    'params'        => [
        'id' => [
            'type'              => 'many2one',
            'foreign_object'    => 'exchange\import\finance\BankStatementImport',
            'required'          => true
        ]
    ],
    'access'        => ['visibility' => 'protected'],
    'response'      => ['accept-origin' => '*', 'content-type' => 'application/json'],
    'providers'     => ['context']
]);

['context' => $context] = $providers;

$import = BankStatementImport::id($params['id'])->read(['status', 'document_id'])->first();
if(!$import) {
    throw new Exception('unknown_data_import', EQ_ERROR_UNKNOWN_OBJECT);
}
if($import['status'] !== 'pending') {
    throw new Exception('wrong_status', EQ_ERROR_UNKNOWN);
}
if(!$import['document_id']) {
    throw new Exception('missing_import_document', EQ_ERROR_INVALID_PARAM);
}

try {
    $validation = eQual::run('get', 'exchange_import_finance_BankStatementImport_validate', ['id' => $params['id']]);
    $result = [
        'errors'    => $validation['errors'],
        'warnings'  => $validation['warnings'],
        'logs'      => $validation['logs']
    ];
}
catch(Throwable $e) {
    $result = [
        'errors'    => 1,
        'warnings'  => 0,
        'logs'      => ['ERR - '.$e->getMessage()]
    ];
}

$context->httpResponse()->body($result)->send();
