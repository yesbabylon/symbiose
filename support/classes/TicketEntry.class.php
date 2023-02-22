<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace support;
use equal\orm\Model;

class TicketEntry extends Model {

    public static function getColumns() {

        return [
            'status' => [
                'type'              => 'string',
                'selection'         => [
                    'draft',
                    'sent'
                ],
                'default'           => 'draft'
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
                'ondelete'          => 'cascade'
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
}