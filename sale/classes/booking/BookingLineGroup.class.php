<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace sale\booking;
use equal\orm\Model;

class BookingLineGroup extends Model {

    public static function getName() {
        return "Booking line group";
    }

    public static function getDescription() {
        return "Booking line groups are related to a booking and describe one or more sojourns and their related consumptions.";
    }

    public static function getColumns() {
        return [
            'name' => [
                'type'              => 'string',
                'description'       => 'Mnemo for the group.'
            ],

            'order' => [
                'type'              => 'integer',
                'description'       => 'Order of the group in the list.',
                'default'           => 1
            ],

            'has_pack' => [
                'type'              => 'boolean',
                'description'       => 'Does the group relates to a pack?',
                'default'           => false
            ],

            'pack_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\catalog\Product',
                'description'       => 'Pack (product) the group relates to, if any.',
                'visible'           => ['has_pack', '=', true]
            ],

            'is_locked' => [
                'type'              => 'boolean',
                'description'       => 'Are modifications disabled for the group?',
                'default'           => false
            ],            

            'date_from' => [
                'type'              => 'datetime',
                'description'       => "Time of arrival."
            ],

            'date_to' => [
                'type'              => 'datetime',
                'description'       => "Time of departure."
            ],

            'sojourn_type' => [
                'type'              => 'string',
                'selection'         => ['GA', 'GG'],
                'default'           => 'GG',
                'description'       => 'The kind of sojourn the group is about.',
            ],

            'nb_pers' => [
                'type'              => 'integer',
                'description'       => 'Amount of persons this group is about.',
                'default'           => 1
            ],

            /* a booking can be split into several groups on which distinct rate classes apply */
            'rate_class_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\customer\RateClass',
                'description'       => "The rate class that applies to the group.",
                'required'          => true
            ],

            'booking_lines_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'sale\booking\BookingLine',
                'foreign_field'     => 'booking_line_group_id',
                'description'       => 'Booking lines that belong to the group.'
            ],

            'booking_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\booking\Booking',
                'description'       => 'Booking the line relates to (for consistency, lines should be accessed using the group they belong to).',
                'required'          => true
            ]






        ];
    }

}