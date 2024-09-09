<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2024
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

use sale\accounting\invoice\Invoice;
use sale\order\Funding;
list($params, $providers) = eQual::announce([
    'description'   => 'Cancel given invoices, can keep or cancel linked receivables.',
    'params'        => [
        'id' =>  [
            'type'              => 'integer',
            'description'       => 'Identifier of the targeted invoice.'
        ],
    ],
    'response'      => [
        'content-type'  => 'application/json',
        'charset'       => 'utf-8',
        'accept-origin' => '*'
    ],
    'providers'     => ['context']
]);

$context = $providers['context'];

if(!isset($params['id']) || $params['id'] <= 0) {
    throw new Exception('invoice_invalid_id', QN_ERROR_INVALID_PARAM);
}

$invoice = Invoice::id($params['id'])
    ->read(['id','status','invoice_type', 'is_downpayment', 'funding_id','fundings_ids'])
    ->first(true);

if($invoice['status'] != 'proforma') {
    throw new Exception("incompatible_status", QN_ERROR_INVALID_PARAM);
}

Funding::ids($invoice['fundings_ids'])->update(['invoice_id' => null]);

if($invoice['is_downpayment']) {
    Funding::id($invoice['funding_id'])->update(['funding_type' => 'installment']);
}

Invoice::id($invoice['id'])->delete(true);

$context->httpResponse()
        ->status(204)
        ->send();
