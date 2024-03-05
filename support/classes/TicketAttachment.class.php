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
}