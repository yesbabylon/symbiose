<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace sale\receivable;
use \equal\orm\Model;

class ReceivableQueue extends Model {

    public static function getDescription() {
        return "A Receivable Queue is created for each Customer and represent the list of items (receivables) that are waiting to be put on an invoice.";
    }

    public static function getColumns() {
        return [
            'customer_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\customer\Customer',
                'description'       => 'The Customer the queue refers to.',
                'required'          => true
            ],

            'receivables_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'sale\receivable\Receivable',
                'foreign_field'     => 'receivable_queue_id',
                'description'       => 'The Receivables attached to the queue.'
            ]
        ];
    }

}