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
        return "A Consumption is a service delivery that can be scheduled, relates to a booking, and is independant from the fare rate and the invoicing.";
    }

    public static function getColumns() {
        return [
            'name' => [
                'type'              => 'computed',
                'function'          => 'calcName',
                'result_type'       => 'string',
                'store'             => true,
                'readonly'          => true
            ],

            'description' => [
                'type'              => 'string',
                'usage'             => 'text/html',
                'description'       => 'Additional note about the consumption, if any.'
            ],

            'booking_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\booking\Booking',
                'description'       => 'The booking the comsumption relates to.',
                'ondelete'          => 'cascade',        // delete consumption when parent booking is deleted
                'readonly'          => true,
                'visible'           => ['type', '<>', 'ooo']
            ],

            // #memo - this field actually belong to Repair objects, we need it to be able to fetch both kind of consumptions
            'repairing_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\booking\Repairing',
                'description'       => 'The booking the comsumption relates to.',
                'ondelete'          => 'cascade',        // delete repair when parent repairing is deleted
                'readonly'          => true,
                'visible'           => ['type', '=', 'ooo']
            ],

            // #todo - deprecate : relation bewteen consumptions and lines might be indirect
            'booking_line_id' => [
                'type'              => 'many2one',
                'foreign_object'    => BookingLine::getType(),
                'description'       => 'The booking line the consumption relates to.',
                'ondelete'          => 'cascade',        // delete consumption when parent line is deleted
                'readonly'          => true
            ],

            'booking_line_group_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\booking\BookingLineGroup',
                'description'       => 'The booking line group the consumption relates to.',
                'ondelete'          => 'cascade',        // delete consumption when parent group is deleted
                'readonly'          => true
            ],

            'date' => [
                'type'              => 'date',
                'description'       => 'Date at which the event is planed.',
                'readonly'          => true
            ],

            'schedule_from' => [
                'type'              => 'time',
                'description'       => 'Moment of the day at which the events starts.',
                'default'           => 0,
                'readonly'          => true
            ],

            'schedule_to' => [
                'type'              => 'time',
                'description'       => 'Moment of the day at which the event stops, if applicable.',
                'default'           => 24 * 3600,
                'readonly'          => true
            ],

            'type' => [
                'type'              => 'string',
                'selection'         => [
                    'ooo',           // out-of-order (repair & maintenance)
                    'book',          // consumption relates to a booking
                    'link',          // rental unit is a child of another booked unit or cannot be partially booked (i.e. parent unit)
                    'part'           // rental unit is the parent of another booked unit and can partially booked (non-blocking: only for info on the planning)
                ],
                'description'       => 'The reason the unit is reserved (mostly applies to accomodations).',
                'default'           => 'book',
                'readonly'          => true
            ],

            'product_model_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\catalog\ProductModel',
                'description'       => "The Product Model the consumption relates to .",
                'required'          => true
            ],

            // #todo - deprecate : only the rental_unit_id matters, and consumptions are created based on product_model (not products)
            'product_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\catalog\Product',
                'description'       => "The Product the consumption relates to.",
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
                'description'       => "The rental unit the consumption is assigned to.",
                'readonly'          => true,
                'onupdate'          => 'onupdateRentalUnitId'
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
            ],

            'is_accomodation' => [
                'type'              => 'boolean',
                'description'       => 'Does the consumption relate to an accomodation (from rental unit)?',
                'visible'           => ['is_rental_unit', '=', true],
                'default'           => false
            ],

            'qty' => [
                'type'              => 'integer',
                'description'       => "How many times the consumption is booked for.",
                'required'          => true
            ],

            'cleanup_type' => [
                'type'              => 'string',
                'selection'         => [
                    'none',
                    'daily',
                    'full'
                ],
                'visible'           => ['is_accomodation', '=', true],
                'default'           => 'none'
            ],

            'age_range_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\customer\AgeRange',
                'description'       => 'Customers age range the product is intended for.'
            ]

        ];
    }


    public static function calcName($om, $oids, $lang) {
        $result = [];
        $consumptions = $om->read(get_called_class(), $oids, ['booking_id.customer_id.name', 'booking_id.description', 'product_id.name', 'date', 'schedule_from']);
        if($consumptions) {
            foreach($consumptions as $oid => $odata) {
                $datetime = $odata['date'] + $odata['schedule_from'];
                $moment = date("d/m/Y H:i:s", $datetime);
                $result[$oid] = substr("{$odata['booking_id.customer_id.name']} {$odata['product_id.name']} {$moment}", 0, 255);
            }
        }
        return $result;
    }


    public static function onupdateRentalUnitId($om, $oids, $values, $lang) {
        $consumptions = $om->read(get_called_class(), $oids, ['rental_unit_id', 'rental_unit_id.is_accomodation', 'date', 'booking_line_group_id.date_from', 'booking_line_group_id.date_to'], $lang);

        if($consumptions > 0) {
            foreach($consumptions as $cid => $consumption) {
                if($consumption['rental_unit_id']) {
                    $cleanup_type = 'none';
                    if($consumption['rental_unit_id.is_accomodation']) {
                        $cleanup_type = 'daily';
                        if($consumption['booking_line_group_id.date_from'] == $consumption['date']) {
                            // no cleanup the day of arrival
                            $cleanup_type = 'none';
                            continue;
                        }
                        if($consumption['booking_line_group_id.date_to'] == $consumption['date']) {
                            // full cleanup on checkout day
                            $cleanup_type = 'full';
                        }
                    }
                    $om->update(self::getType(), $oids, ['is_rental_unit' => true, 'is_accomodation' => $consumption['rental_unit_id.is_accomodation'], 'cleanup_type' => $cleanup_type]);
                }
            }
        }
    }
}