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
                'description'       => 'Mnemo for the group.',
                'default'           => ''
            ],

            'order' => [
                'type'              => 'integer',
                'description'       => 'Order of the group in the list.',
                'default'           => 1
            ],

            'date_from' => [
                'type'              => 'date',
                'description'       => "Day of arrival.",
                'default'           => time()
            ],

            'date_to' => [
                'type'              => 'date',
                'description'       => "Day of departure.",
                'default'           => time()
            ],

            'nb_nights' => [
                'type'              => 'computed',
                'result_type'       => 'integer',
                'description'       => 'Amount of nights of the sojourn.',
                'function'          => 'sale\booking\BookingLineGroup::getNbNights',
                'store'             => true
            ],

            'nb_pers' => [
                'type'              => 'integer',
                'description'       => 'Amount of persons this group is about.',
                'default'           => 1
            ],

            /* a booking can be split into several groups on which distinct rate classes apply, by default the rate_class of the customer is used */
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
                'description'       => 'Booking lines that belong to the group.',
                'ondetach'          => 'delete'
            ],

            'consumptions_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'sale\booking\Consumption',
                'foreign_field'     => 'booking_line_group_id',
                'description'       => 'Consumptions related to the group.',
                'ondetach'          => 'delete'
            ],

            'price_adapters_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'sale\booking\BookingPriceAdapter',
                'foreign_field'     => 'booking_line_group_id',
                'description'       => 'Price adapters that apply to all lines of the group (based on group settings).'
            ],

            'booking_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\booking\Booking',
                'description'       => 'Booking the line relates to (for consistency, lines should be accessed using the group they belong to).',
                'required'          => true,
                'ondelete'          => 'cascade'        // delete group when parent booking is deleted
            ],

            'price' => [
                'type'              => 'computed',
                'result_type'       => 'float',
                'description'       => 'Final (computed) price for all lines.',
                'function'          => 'sale\booking\BookingLineGroup::getPrice'
            ]

        ];
    } 

    public static function getNbNights($om, $oids, $lang) {
        $result = [];
        $groups = $om->read(__CLASS__, $oids, ['date_from', 'date_to']);
        foreach($groups as $gid => $group) {
            $result[$gid] = floor( ($group['date_to'] - $group['date_from']) / (60*60*24) );
        }
        return $result;
    }

    public static function getPrice($om, $oids, $lang) {
        $result = [];
        $groups = $om->read(__CLASS__, $oids, ['booking_lines_ids']);

        if($groups > 0 && count($groups)) {
            foreach($groups as $gid => $group) {
                $result[$gid] = 0.0;

                $lines = $om->read('sale\booking\BookingLine', $group['booking_lines_ids'], ['price', 'payment_mode']);
                if($lines > 0 && count($lines)) {
                    foreach($lines as $line) {
                        if($line['payment_mode'] != 'free') {
                            $result[$gid] += $line['price'];
                        }
                    }
                    $result[$gid] = round($result[$gid], 2);
                }

            }
        }
        return $result;
    }

   
}