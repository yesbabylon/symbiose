<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace lodging\sale\pos;

class Order extends \sale\pos\Order {

    public static function getColumns() {

        return [

            'has_funding' => [
                'type'              => 'boolean',
                'description'       => 'Does the order relate to a booking funding?',
                'default'           => false
            ],

            'funding_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'lodging\sale\booking\Funding',
                'description'       => 'The booking funding that relates to the order, if any.',
                'visible'           => ['has_funding', '=', true]
            ],

            'invoice_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'lodging\sale\booking\Invoice',
                'description'       => 'The invoice that relates to the order, if any.',
                'visible'           => ['has_invoice', '=', true]
            ],

            'session_id' => [
                'type'              => 'many2one',
                'foreign_object'    => CashdeskSession::getType(),
                'description'       => 'The session the order belongs to.',
                'onupdate'          => 'onupdateSessionId',
                'required'          => true
            ],

            'order_lines_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => OrderLine::getType(),
                'foreign_field'     => 'order_id',
                'ondetach'          => 'delete',
                'onupdate'          => 'onupdateOrderLinesIds',
                'description'       => 'The lines that relate to the order.'
            ],

        ];
    }

}