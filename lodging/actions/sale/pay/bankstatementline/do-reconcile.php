<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
use lodging\sale\booking\BankStatementLine;

list($params, $providers) = announce([
    'description'   => "Emit a new invoice from an existing proforma and update related booking, if necessary.",
    'params'        => [
        'id' =>  [
            'description'   => 'Identifier of the BankStatementLine to reconcile.',
            'type'          => 'integer',
            'min'           => 1,
            'required'      => true
        ],
    ],
    'access' => [
        'visibility'        => 'protected',
        'groups'            => ['finance.default.user', 'sale.default.user'],
    ],
    'response'      => [
        'content-type'  => 'application/json',
        'charset'       => 'utf-8',
        'accept-origin' => '*'
    ],
    'providers'     => ['context', 'orm']
]);

/**
 * @var \equal\php\Context                  $context
 * @var \equal\orm\ObjectManager            $orm
 * @var \equal\cron\Scheduler               $cron
 * @var \equal\auth\AuthenticationManager   $auth
 */
list($context, $orm) = [$providers['context'], $providers['orm']];

$orm->call(BankStatementLine::getType(), 'reconcile', (array) $params['id']);

$context->httpResponse()
        ->status(204)
        ->send();