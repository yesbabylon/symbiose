<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

use sale\receivable\Receivable;

list($params, $providers) = announce([
    'description'   => 'Create invoices for all pending receivables',
    'params'        => [],
    'response'      => [
        'content-type'  => 'application/json',
        'charset'       => 'utf-8',
        'accept-origin' => '*'
    ],
    'providers'     => [ 'context', 'orm' ]
]);

list($context, $orm) = [$providers['context'], $providers['orm']];

$receivable_ids = Receivable::search(['status', '=', 'pending'])
    ->ids();

if (!empty($receivable_ids)) {
    $result = eQual::run(
        'do',
        'sale_receivable_add-invoice',
        ['ids' => $receivable_ids]
    );
}

$context->httpResponse()
        ->body($result)
        ->send();
