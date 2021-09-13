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
        return "A Consumption is a service delivery that can be scheduled and relates to a booking.";
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
                'description'       => 'The booking the comsumption relates to.',
                'ondelete'          => 'cascade'        // delete consumption when parent booking is deleted                
            ],

            'booking_line_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\booking\BookingLine',
                'description'       => 'The booking line the consumption relates to.',
                'ondelete'          => 'cascade'        // delete consumption when parent line is deleted
            ],

            'booking_line_group_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\booking\BookingLineGroup',
                'description'       => 'The booking line group the consumption relates to.',
                'ondelete'          => 'cascade'        // delete consumption when parent group is deleted
            ],

            'date' => [
                'type'              => 'date',
                'description'       => 'Date at which the service delivery is planed.'
            ],

            'schedule_from' => [
                'type'              => 'time',
                'description'       => 'Moment of the day at which the service delivery is planed.'
            ],

            'schedule_to' => [
                'type'              => 'time',
                'description'       => 'Moment of the day at which the service delivery is over, if applicable.'
            ],

            'type' => [
                'type'              => 'string',
                'selection'         => [ 
                    'book',          // consumption relates to a booking
                    'ooo',           // out-of-order
                ],                
                'description'       => 'The reason the unit is reserved.',
                'default'           => 'book'
            ],

            'product_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\catalog\Product',
                'description'       => "The Product this Attribute belongs to.",
                'required'          => true
            ],

            'is_rental_unit' => [
                'type'              => 'boolean',
                'description'       => 'Does the consumption relate to a rental unit?',
                'default'           => false
            ],

            'rental_unit_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'realestate\RentalUnit',
                'description'       => "The rental unit the person is assigned to.",
                'visible'           => ['is_rental_unit', '=', true]
            ],

            'disclaimed' => [
                'type'              => 'boolean',
                'description'       => 'Delivery is planed by the customer has explicitely renounced to it.',
                'default'           => false
            ],

            'is_meal' => [
                'type'              => 'boolean',
                'description'       => 'Does the consumption relate to a meal?',
                'default'           => false
            ]

        ];
    }


    public static function getDisplayName($om, $oids, $lang) {
        $result = [];
        $consumptions = $om->read(__CLASS__, $oids, ['booking_id.customer_id.name', 'booking_id.description', 'product_id.name', 'date', 'schedule_from']);
        foreach($consumptions as $oid => $odata) {
            $datetime = $odata['date'] + $odata['schedule_from'];
            $moment = date("d/m/Y H:i:s", $datetime);
            $result[$oid] = substr("{$odata['booking_id.customer_id.name']} {$odata['product_id.name']} {$moment}", 0, 255);
        }
        return $result;
    }
}