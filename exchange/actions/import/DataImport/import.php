<?php
/*
    Developed by Yesbabylon - https://yesbabylon.com
    (c) 2025-2026 Yesbabylon SA
    Licensed under the GNU AGPL v3 License - https://www.gnu.org/licenses/agpl-3.0.html
*/

use exchange\import\DataImport;

[$params, $providers] = eQual::announce([
    'description'   => 'Check the import document data.',
    'params'        => [
        'id' => [
            'type'              => 'many2one',
            'description'       => 'Identifier of the targeted DataImport object.',
            'foreign_object'    => 'identity\DataImport'
        ]
    ],
    'access'        => [
        'visibility'    => 'protected'
    ],
    'response'      => [
        'accept-origin' => '*',
        'content-type'  => 'application/json'
    ],
    'providers'     => ['context']
]);

/**
 * @var \equal\php\Context $context
 */
['context' => $context] = $providers;

$import = DataImport::id($params['id'])
    ->read(['status', 'model'])
    ->first();

if(!$import) {
    throw new Exception('unknown_data_import', EQ_ERROR_UNKNOWN_OBJECT);
}

if(!in_array($import['status'], ['checked', 'import_failed'])) {
    throw new Exception('wrong_status', EQ_ERROR_UNKNOWN);
}

$action = str_replace('\\', '_', $import['model']).'_import';

$result = eQual::run('do', $action, $params);

$context
    ->httpResponse()
    ->body($result)
    ->send();
