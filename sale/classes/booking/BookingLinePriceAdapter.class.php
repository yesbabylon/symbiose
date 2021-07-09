<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace sale\booking;
use equal\orm\Model;

class BookingLinePriceAdapter extends Model {

    public static function getName() {
        return "Price Adapter";
    }

    public static function getDescription() {
        return "Adapters allow to get the final price of a booking line, either by performing a direct computation, or by using a discount definition.";
    }
    
    public static function getColumns() {
        return [

            'booking_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\booking\Booking',
                'description'       => 'Booking the adapter relates to.',
                'required'          => true
            ],

            'booking_line_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\booking\BookingLine',
                'description'       => 'Booking Line the adapter relates to.',
                'required'          => true
            ],
            
            'is_manual_discount' => [
                'type'              => 'boolean',
                'description'       => "Flag to set the adapter as manual or related to a discount.",
                'default'           => true
            ],

            'type' => [
                'type'              => 'string',
                'selection'         => ['percent', 'amount'],         
                'description'       => 'Type of manual discount (fixed amount or percentage of the price).',
                'visible'           => ['is_manual_discount', '=', true]
            ],

            'value' => [
                'type'              => 'float',                
                'description'       => "Value of the discount (monetary amount or percentage).",
                'visible'           => ['is_manual_discount', '=', true]
            ],

            'discount_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\booking\Discount',
                'description'       => 'Discount related to the adapter, if any.',
                'visible'           => ['is_manual_discount', '=', false]                
            ]


        ];
    }

}