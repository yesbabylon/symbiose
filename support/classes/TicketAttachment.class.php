<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace support;

class TicketAttachment extends \documents\Document {

    public static function getColumns() {
        return [

            'category_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'documents\DocumentCategory',
                'description'       => 'Category of the document (default to \'support\')',
                'default'           =>  2
            ],

            'ticket_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'support\Ticket',
                'description'       => 'Ticket of the attachment.',
                'ondelete'          => 'cascade'
            ],

            'ticket_entry_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'support\TicketEntry',
                'description'       => 'Ticket of the attachment.',
                'ondelete'          => 'cascade'
            ]

        ];
    }

    public static function cancreate($self, $values) {
        if(empty($values['ticket_id']) && empty($values['ticket_entry_id'])) {
            return ['ticket_id' => ['missing_ticket' => 'A ticket or ticket entry is required.']];
        }

        return parent::cancreate($self, $values);
    }

    public static function canupdate($self, $values) {
        $self->read(['ticket_id', 'ticket_entry_id']);
        foreach($self as $attachment) {
            $ticket_id = array_key_exists('ticket_id', $values)
                ? $values['ticket_id']
                : $attachment['ticket_id'];
            $ticket_entry_id = array_key_exists('ticket_entry_id', $values)
                ? $values['ticket_entry_id']
                : $attachment['ticket_entry_id'];

            if(empty($ticket_id) && empty($ticket_entry_id)) {
                return ['ticket_id' => ['missing_ticket' => 'A ticket or ticket entry is required.']];
            }
        }

        return parent::canupdate($self, $values);
    }
}
