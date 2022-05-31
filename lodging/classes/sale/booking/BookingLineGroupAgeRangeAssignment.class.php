<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace lodging\sale\booking;
use equal\orm\Model;

class BookingLineGroupAgeRangeAssignment extends Model {

    public static function getName() {
        return "Age Range Assignment";
    }

    /*
        Assignments are created while selecting the hosts details/composition for a booking group.
        Each group is assigned to one or more age ranges.
    */

    public static function getColumns() {
        return [

            'booking_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'lodging\sale\booking\Booking',
                'description'       => 'The booking the line relates to (for consistency, lines should be accessed using the group they belong to).',
                'ondelete'          => 'cascade'
            ],

            'qty' => [
                'type'              => 'integer',
                'description'       => 'Number of persons assigned to the age range for related booking group.',
                'default'           => 1
            ],

            'age_range_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\customer\AgeRange',
                'description'       => 'Age range assigned to booking group.',
                'ondelete'          => 'null'
            ],

            'booking_line_group_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'lodging\sale\booking\BookingLineGroup',
                'description'       => 'Booking lines Group the assignment relates to.',
                'required'          => true,
                'ondelete'          => 'cascade'
            ]

        ];
    }

    public function getUnique() {
        return [
            ['booking_line_group_id', 'age_range_id']
        ];
    }

}