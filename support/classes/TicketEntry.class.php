<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace support;
use equal\orm\Model;
use equal\email\Email;
use core\Mail;

class TicketEntry extends Model {

    public static function getColumns() {

        return [
            'status' => [
                'type'              => 'string',
                'selection'         => [
                    'draft',
                    'sent'
                ],
                'default'           => 'draft',
                'onupdate'          => 'onupdateStatus'
            ],

            'description' => [
                'type'              => 'text',
                'usage'             => 'plain/text',
                'description'       => "Message of the entry.",
                'onupdate'          => 'onupdateDescription'
            ],

            'environment' => [
                'type'              => 'string',
                'usage'             => 'plain/text',
                'description'       => "Auto-filled description of the original user environment."
            ],

            'excerpt' => [
                'type'              => 'string',
                'description'       => "Excerpt of the Message (for display)."
            ],

            'type' => [
                'type'              => 'string',
                'selection'         => [
                    'request',
                    'response'
                ],
                'default'           => 'response',
                'description'       => "The type implies the next action required for the ticket.",
                'help'              => "The first entry of a ticket is always a request. Afterward, responses and request for infomration details can be mixed. The last entry of a ticket should be a response."
            ],

            'ticket_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'support\Ticket',
                'description'       => "Ticket the entry relates to.",
                'ondelete'          => 'delete'
            ],

            'attachments_ids' => [
                'type'              => 'one2many',
                'foreign_field'     => 'ticket_entry_id',
                'foreign_object'    => 'support\TicketAttachment',
                'description'       => 'Documents assigned to the ticket.',
                'ondetach'          => 'delete'
            ]

        ];
    }


    public static function onupdateDescription($om, $oids, $values, $lang) {
        // update excerpt
        $tickets = $om->read(self::getType(), $oids, ['description']);
        if($tickets > 0 && count($tickets)) {
            foreach($tickets as $tid => $ticket) {
                // create a first ticket entry by duplicating the description
                $om->update(self::getType(), $tid, [
                        'excerpt'       => substr(strip_tags($ticket['description']), 0, 100).'...'
                    ]);
            }
        }
        // update environment
        $context = $om->getContainer()->get('context');
        $request = $context->getHttpRequest();
        $om->update(self::getType(), $oids, ['environment' => $request->getHeader('User-Agent')]);
    }

    /**
     * Handler for field status.
     * Used to intercept ticket submission and create a first entry.
     */
    public static function onupdateStatus($om, $oids, $values, $lang) {
        $entries = $om->read(self::getType(), $oids, ['status', 'creator', 'ticket_id', 'ticket_id.name', 'ticket_id.type', 'ticket_id.priority', 'ticket_id.creator', 'ticket_id.creator.login', 'ticket_id.assignee_id.login']);
        if($entries > 0 && count($entries)) {
            foreach($entries as $eid => $entry) {
                // if ticket status just changed to 'open',
                if($entry['status'] == 'sent') {
                    $address = 'support@yesbabylon.com';
                    $title = 'Nouveau ticket de support ['.$entry['ticket_id.priority'].' - '.$entry['ticket_id.type'].']';
                    $body = 'Un nouveau ticket a été soumis';
                    if($entry['creator'] == $entry['ticket_id.creator']) {
                        if($entry['ticket_id.assignee_id.login'] && strlen($entry['ticket_id.assignee_id.login'])) {
                            $address = $entry['ticket_id.assignee_id.login'];
                            $title = 'Nouvelle réponse au ticket de support';
                            $body = 'L\'utilisateur a soumis un nouveau message sur le ticket';
                        }
                    }
                    else {
                        if(!$entry['ticket_id.creator.login'] || strlen($entry['ticket_id.creator.login']) <= 0) {
                            // ignore invalid emails
                            continue;
                        }
                        $address = $entry['ticket_id.creator.login'];
                        $title = 'Réponse à votre ticket de support';
                        $body = 'Une réponse a été donnée à votre message';
                    }
                    // create message
                    $link = \config\constant('ROOT_APP_URL').str_replace('object.id', $entry['ticket_id'], Ticket::getLink());
                    $message = new Email();
                    $message
                        ->setTo($address)
                        ->setSubject($title)
                        ->setContentType("text/html")
                        ->setBody(
                            sprintf("$body : <a href=\"%s\">%s</a>", $link, $entry['ticket_id.name'].' ['.$entry['ticket_id'].']')
                        );
                    // send message
                    Mail::queue($message);
                }
            }
        }
    }
}