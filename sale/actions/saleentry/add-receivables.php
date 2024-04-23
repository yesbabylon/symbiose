<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2024
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

use sale\SaleEntry;

list($params, $providers) = announce([
    'description'   => 'Create receivables from given sale entries.',
    'help'          => 'If specific queue given then all sale entries must be for the same customer.',
    'params'        => [
        'ids' =>  [
            'description'    => 'Identifier of the targeted sale entries.',
            'type'           => 'one2many',
            'foreign_object' => 'sale\SaleEntry',
            'required'       => true
        ],

        'receivables_queue_id' => [
            'type'           => 'many2one',
            'foreign_object' => 'sale\receivable\ReceivablesQueue',
            'description'    => 'Default receivable queue used if left empty.'
        ]
    ],
    'response'      => [
        'content-type'  => 'application/json',
        'charset'       => 'utf-8',
        'accept-origin' => '*'
    ],
    'providers'     => ['context']
]);

/** @var \equal\php\Context $context */
$context = $providers['context'];

$checkAllEntriesForSameCustomer = function($sale_entries) {
    $customer_id = null;
    foreach($sale_entries as $sale_entry) {
        if(is_null($customer_id)) {
            $customer_id = $sale_entry['customer_id'];
        }
        elseif($customer_id !== $sale_entry['customer_id']) {
            throw new Exception('sale_entries_must_be_for_same_customer', QN_ERROR_INVALID_PARAM);
        }
    }
};

if(empty($params['ids'])) {
    throw new Exception('empty_ids_param', QN_ERROR_INVALID_PARAM);
}

$sale_entries = SaleEntry::ids($params['ids'])
    ->read(['customer_id', 'is_billable', 'has_receivable'])
    ->toArray();

if(count($sale_entries) !== count($params['ids'])) {
    throw new Exception('unknown_saleentry', QN_ERROR_UNKNOWN_OBJECT);
}

if(isset($params['receivables_queue_id'])) {
    $checkAllEntriesForSameCustomer($sale_entries);
}

foreach($sale_entries as $sale_entry) {
    eQual::run('do', 'sale_saleentry_add-receivable', [
        'id'                   => $sale_entry['id'],
        'receivables_queue_id' => $params['receivables_queue_id'] ?? null
    ]);
}

$context->httpResponse()
        ->status(204)
        ->send();
