<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace sale\booking;
use equal\orm\Model;

class Consumption extends Model {

    public static function getName() {
        return 'Consumption';
    }

    public static function getDescription() {
        return "A Consumption is a service delivery that can be scheduled and is related to a booking.";
    }

    public static function getColumns() {
        return [
            'name' => [
                'type'              => 'computed',
                'function'          => 'sale\booking\Consumption::getDisplayName',
                'result_type'       => 'string',
                'store'             => true
            ],

            'description' => [
                'type'              => 'string',
                'description'       => 'Additional note about the consumption, if any.'
            ],

            'booking_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\booking\Booking',
                'description'       => 'The booking the comsumption relates to.'
            ],

            'booking_line_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\booking\BookingLine',
                'description'       => 'The booking line the consumption relates to.'
            ],

            'date' => [
                'type'              => 'date',
                'description'       => 'Date at which the service delivery is planed.'
            ],

            'schedule' => [
                'type'              => 'time',
                'description'       => 'Moment of the day at which the service delivery is planed.'
            ],

            'type' => [
                'type'              => 'string',
                'selection'         => [ 
                    'book',          // consumption relates to a booking
                    'ooo',           // out-of-order
                ],
                'description'       => 'The reason the unit is reserved.'
            ],

            'product_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\catalog\Product',
                'description'       => "The Product this Attribute belongs to.",
                'required'          => true
            ],

            'rental_unit_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'realestate\RentalUnit',
                'description'       => "The rental unit the person is assigned to.",
                'required'          => true
            ],

            'disclaimed' => [
                'type'              => 'boolean',
                'description'       => 'Delivery is planed by the customer has explicitely renounced to it.',
                'default'           => false
            ],

        ];
    }


    public static function getDisplayName($om, $oids, $lang) {
        $result = [];
        $consumptions = $om->read(__CLASS__, $oids, ['product_id.name', 'date', 'schedule']);
        foreach($consumptions as $oid => $odata) {
            $result[$oid] = "{$odata['product_id.name']} ({$odata['date']}) @ {$odata['schedule']}";
        }
        return $result;
    }
}