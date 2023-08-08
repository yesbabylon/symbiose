<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace inventory\sale\receivable;

class ReceivablesQueue extends \sale\receivable\ReceivablesQueue {

    public static function getColumns() {
        return [
            'customer_id' => [
                'type'              => 'computed',
                'result_type'       => 'many2one',
                'foreign_object'    => 'inventory\sale\customer\Customer',
                'description'       => 'The Customer to who refers the item (from ReceivableQueue).',
                'store'             => true,
                'function'          => 'calcCustomerId'
            ],
        ];
    }

}