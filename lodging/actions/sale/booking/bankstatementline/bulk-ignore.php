<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

use lodging\sale\booking\BankStatement;
use lodging\sale\booking\BankStatementLine;

// announce script and fetch parameters values
list($params, $providers) = announce([
    'description'	=>	"Mark a selection of BankStatementLine as ignored.",
    'params' 		=>	[
        'ids' => [
            'description'       => 'List of BankStatementLine identifiers  of the order for which the tree is requested.',
            'type'              => 'one2many',
            'foreign_object'    => BankStatementLine::getType(),
            'required'          => true
        ]
    ],
    'access' => [
        'visibility'        => 'protected',
        'groups'            => ['sale.default.user'],
    ],
    'response' => [
        'content-type'      => 'application/json',
        'charset'           => 'utf-8',
        'accept-origin'     => '*'
    ],
    'providers' => ['context']
]);

list($context) = [$providers['context']];

BankStatementLine::ids($params['ids'])->update(['message' => 'test', 'status' => 'ignored']);

$context->httpResponse()
        ->status(204)
        ->send();