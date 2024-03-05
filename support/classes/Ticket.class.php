<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace support;
use equal\orm\Model;


class Ticket extends Model {

    public static function getLink() {
        return "/support/#/ticket/object.id";
    }

    public static function getColumns() {
        return [
            'name' => [
                'type'              => 'string',
                'description'       => "Short description of the support request.",
                'required'          => true
            ],

            'reference' => [
                'type'              => 'string',
                'description'       => "External reference for tracking the ticket."
            ],

            'status' => [
                'type'              => 'string',
                'selection'         => [
                    'draft',
                    'open',
                    'pending',
                    'waiting',
                    'closed'
                ],
                'default'           => 'draft',
                'onupdate'          => 'onupdateStatus'
            ],

            'type' => [
                'type'              => 'string',
                'selection'         => [
                    'incident',
                    'question',
                    'feature_request'
                ],
                'description'       => "The type indicates the nature of action it requires.",
                'required'          => true
            ],

            'priority' => [
                'type'              => 'integer',
                'selection'         => [
                    1       => 'Low',
                    2       => 'Medium',
                    3       => 'High',
                    4       => 'Critical'
                ],
                'description'       => "The priority indicates the criticality of the incident.",
                'default'           => 1
            ],

            'environment' => [
                'type'              => 'string',
                'usage'             => 'plain/text',
                'description'       => "Auto-filled description of the original user environment."
            ],

            'description' => [
                'type'              => 'text',
                'usage'             => 'plain/text',
                'description'       => "Message of the first entry.",
                'onupdate'          => 'onupdateDescription'
            ],

            'ticket_entries_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'support\TicketEntry',
                'foreign_field'     => 'ticket_id',
                'description'       => "Entries that related to this ticket.",
                'ondetach'          => 'delete'
            ],

            'assignee_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'core\User',
                'description'       => 'Person that is handling the ticket (who will provide an answer or perform an action regarding it).',
                'onupdate'          => 'onupdateAssigneeId'
            ],

            'attachments_ids' => [
                'type'              => 'one2many',
                'foreign_field'     => 'ticket_id',
                'foreign_object'    => 'support\TicketAttachment',
                'description'       => 'Documents assigned to the ticket.'
            ]

        ];
    }

    /**
     * Used to intercept ticket submission and create a first entry.
    */
    public static function onupdate($om, $ids, $values, $lang) {
        $tickets = $om->read(self::getType(), $ids, ['creator', 'status', 'description', 'environment', 'attachments_ids']);
        if($tickets > 0 && count($tickets)) {
            foreach($tickets as $tid => $ticket) {
                // if ticket status just changed to 'open',
                if(isset($values['status']) && $values['status'] == 'open' && $ticket['status'] == 'draft') {
                    // create a first ticket entry by duplicating the description
                    $entry_id = $om->create(TicketEntry::getType(), [
                            'creator'       => $ticket['creator'],
                            'type'          => 'request',
                            'ticket_id'     => $tid,
                            'description'   => $ticket['description'],
                            'environment'   => $ticket['environment']
                        ]);
                    // link attachments to first entry
                    $om->update(TicketAttachment::getType(), $ticket['attachments_ids'], ['ticket_entry_id' => $entry_id]);
                    // mark the entry as sent (will trigger email notification)
                    $om->update(TicketEntry::getType(), $entry_id, ['status' => 'sent']);
                }
            }
        }
    }

    public static function onupdateAssigneeId($om, $oids, $values, $lang) {
    }

    /**
     * Upon description update, we store the User-Agent header from the request into the `environment` field.
     */
    public static function onupdateDescription($om, $oids, $values, $lang) {
        $context = $om->getContainer()->get('context');
        $request = $context->getHttpRequest();
        $om->update(self::getType(), $oids, ['environment' => $request->getHeader('User-Agent')]);
    }

    public static function getConstraints() {
        return [
            'name' =>  [
                'too_short' => [
                    'message'       => 'Title must be 10 chars. min.',
                    'function'      => function ($name, $values) {
                        return strlen($name) > 10;
                    }
                ]
            ],
            'type' =>  [
                'mandatory' => [
                    'message'       => 'Type is mandatory.',
                    'function'      => function ($type, $values) {
                        return strlen($type) > 0;
                    }
                ]
            ]
        ];
    }
}