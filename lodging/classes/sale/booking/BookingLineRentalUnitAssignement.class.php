<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace lodging\sale\booking;
use equal\orm\Model;

class BookingLineRentalUnitAssignement extends Model {

    public static function getName() {
        return "Rental Unit Assignement";
    }

    /*
        Assignements are created while selecting the services for a booking.
        Each product line that targets a product configured to relate to a rental unit (or catogory) is assigned to one or more rental units.
    */

    public static function getColumns() {
        return [

            'booking_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'lodging\sale\booking\Booking',
                'description'       => 'The booking the line relates to (for consistency, lines should be accessed using the group they belong to).',
                'ondelete'          => 'cascade'         // delete assignment when parent booking is deleted
            ],

            'qty' => [
                'type'              => 'integer',
                'description'       => 'Number of persons assigned to the rental unit for related booking line.',
                'default'           => 1
            ],

            'rental_unit_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'lodging\realestate\RentalUnit',
                'description'       => 'Rental unit assigned to booking line.',
                'ondelete'          => 'null'
            ],

            'booking_line_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'lodging\sale\booking\BookingLine',
                'description'       => 'Booking Line the assignment relates to.',
                'required'          => true,
                'ondelete'          => 'cascade'
            ],

            'booking_line_group_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'lodging\sale\booking\BookingLineGroup',
                'description'       => 'Booking lines Group the assignment relates to.',
                'required'          => true,
                'ondelete'          => 'cascade'
            ],

            'is_accomodation' => [
                'type'              => 'boolean',
                'description'       => 'The related rental unit is an accomodation (having at least one bed).',
                'default'           => true
            ]

        ];
    }

    public function getUnique() {
        return [
            ['booking_line_id', 'rental_unit_id']
        ];
    }

}