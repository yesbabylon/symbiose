<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
use support\Ticket;

list($params, $providers) = announce([
    'description'   => 'Submit a ticket and mark it as \'open\'.',
    'params'        => [
        'id' => [
            'type'          => 'integer',
            'description'   => 'Identifier of the ticket to submit (must be \'draft\').',
            'required'      => true
        ]
    ],
    'response'      => [
        'content-type'  => 'application/json',
        'charset'       => 'utf-8',
        'accept-origin' => '*'
    ],
    'access' => [
        'visibility'    => 'protected'
    ],
    'providers'     => [ 'context', 'report' ]
]);

/**
 * @var \equal\php\Context                $context
 * @var \equal\error\Reporter             $reporter
 */
list($context, $reporter) = [ $providers['context'], $providers['report'] ];


$ticket = Ticket::id($params['id'])->read(['id', 'status'])->first();

if(!$ticket) {
    throw new Exception('unknown_ticket', QN_ERROR_UNKNOWN);
}

if($ticket['status'] != 'draft') {
    throw new Exception('invalid_status', QN_ERROR_INVALID_PARAM);
}

Ticket::id($params['id'])->update(['status' => 'open']);

$context
    ->httpResponse()
    ->status(205)
    ->send();
