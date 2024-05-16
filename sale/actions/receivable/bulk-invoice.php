<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2024
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

use sale\receivable\Receivable;

list($params, $providers) = announce([
    'description'   => 'Invoice all pending receivables.',
    'params'        => [],
    'response'      => [
        'content-type'  => 'application/json',
        'charset'       => 'utf-8',
        'accept-origin' => '*'
    ],
    'providers'     => ['context']
]);

/** @var \equal\php\Context $context */
$context = $providers['context'];

$pending_receivables_ids = Receivable::search(['status', '=', 'pending'])->ids();

eQual::run('do', 'sale_receivable_invoice', ['ids' => $pending_receivables_ids]);

$context->httpResponse()
        ->status(204)
        ->send();
