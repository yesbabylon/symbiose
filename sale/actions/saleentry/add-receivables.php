<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

use sale\SaleEntry;

list($params, $providers) = announce([
    'description'   => 'Create receivables from given sale entries.',
    'params'        => [
        'ids' =>  [
            'description'    => 'Identifier of the targeted reports.',
            'type'           => 'one2many',
            'foreign_object' => 'sale\SaleEntry',
            'required'       => true
        ]
    ],
    'response'      => [
        'content-type'  => 'application/json',
        'charset'       => 'utf-8',
        'accept-origin' => '*'
    ],
    'providers'     => ['context', 'orm']
]);

list($context, $orm) = [$providers['context'], $providers['orm']];

if(empty($params['ids'])) {
    throw new Exception('empty_ids_param', QN_ERROR_INVALID_PARAM);
}

$sale_entries = SaleEntry::ids($params['ids'])->read();
if(count($sale_entries) !== count($params['ids'])) {
    throw new Exception('unknown_saleentry', QN_ERROR_UNKNOWN_OBJECT);
}

foreach($sale_entries as $sale_entry) {
    eQual::run('get', 'sale_saleentry_add-receivable', $sale_entry['id'], true);
}

$context->httpResponse()
        ->status(204)
        ->send();
