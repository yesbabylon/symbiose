<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace sale\pos;
use equal\orm\Model;

class OperationLine extends Model {

    public static function getColumns() {

        return [

            'type' => [
                'type'              => 'string',
                'selection'         => [ 
                    'direct',            // operation line is a direct sale of a product
                    'booking',           // operation line relates to a booking line
                ],
                'description'       => 'The kind of operation.'
            ],

            'operation_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\pos\Operation',
                'description'       => 'The operation the payment relates to.',
                'required'          => true
            ],

            'product_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\catalog\Product',
                'description'       => 'The product targeted by the line.',
                'visible'           => [ ['type', '=', 'direct'] ]
            ],

            'booking_line_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\booking\BookingLine',
                'description'       => 'The booking line targeted by the operation line.',
                'visible'           => [ ['type', '=', 'booking'] ]
            ],

        ];
    }

}