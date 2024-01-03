<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
use support\TicketEntry;
use support\Ticket;

list($params, $providers) = announce([
    'description'   => 'Submit a ticket entry and mark it as \'sent\'.',
    'params'        => [
        'id' => [
            'type'          => 'integer',
            'description'   => 'Identifier of the ticket entry to submit (must be \'draft\').',
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
    'providers'     => [ 'context', 'report', 'auth' ]
]);

/**
 * @var \equal\php\Context                $context
 * @var \equal\error\Reporter             $reporter
 * @var \equal\auth\AuthenticationManager $auth
 */
list($context, $reporter, $auth) = [ $providers['context'], $providers['report'], $providers['auth'] ];

// retrieve the user making the submission
$user_id = $auth->userId();

$entry = TicketEntry::id($params['id'])->read(['id', 'creator', 'status', 'ticket_id'])->first();

if(!$entry) {
    throw new Exception('unknown_ticket_entry', QN_ERROR_UNKNOWN);
}

if($entry['status'] != 'draft') {
    throw new Exception('invalid_status', QN_ERROR_INVALID_PARAM);
}

TicketEntry::id($params['id'])->update(['status' => 'sent']);
$ticket = Ticket::id($entry['ticket_id'])->read(['creator', 'assignee_id'])->first();

if($ticket['creator'] == $user_id) {
    Ticket::id($entry['ticket_id'])->update(['status' => 'open']);
}
elseif(!isset($ticket['assignee_id']) || is_null($ticket['assignee_id'])) {
    Ticket::id($entry['ticket_id'])
        ->update(['assignee_id' => $user_id])
        ->update(['status' => 'pending']);
}
elseif($ticket['assignee_id'] == $entry['creator']) {
    Ticket::id($entry['ticket_id'])
        ->update(['status' => 'pending']);
}

$context
    ->httpResponse()
    ->status(205)
    ->send();
