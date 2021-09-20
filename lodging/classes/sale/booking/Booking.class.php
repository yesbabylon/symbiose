<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace lodging\sale\booking;


class Booking extends \sale\booking\Booking {


    public static function getColumns() {
        return [

            'customer_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\customer\Customer',
                'domain'            => ['relationship', '=', 'customer'],
                'description'       => "The customer to whom the booking relates to.",
                'required'          => true,
                'onchange'          => 'lodging\sale\booking\Booking::onchangeCustomerId'
            ],

            'center_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'lodging\identity\Center',
                'description'       => "The center to which the booking relates to.",
                'required'          => true,
                'onchange'          => 'lodging\sale\booking\Booking::onchangeCenterId'
            ],

            'booking_lines_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'lodging\sale\booking\BookingLine',
                'foreign_field'     => 'booking_id',
                'description'       => 'Detailed consumptions of the booking.' 
            ],

            'booking_lines_groups_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'lodging\sale\booking\BookingLineGroup',
                'foreign_field'     => 'booking_id',
                'description'       => 'Grouped consumptions of the booking.' 
            ]
            

            
        ];
    }

    public static function onchangeCustomerId($om, $oids, $lang) {
        // force immediate recomputing of the name/reference
        $om->write(__CLASS__, $oids, ['name' => null]);
        $booking_lines_groups_ids = $om->read(__CLASS__, $oids, ['name', 'booking_lines_groups_ids']);
        if($booking_lines_groups_ids > 0 && count($booking_lines_groups_ids)) {
            BookingLineGroup::_updatePriceAdapters($om, $booking_lines_groups_ids, $lang);
        }
    }

    public static function onchangeCenterId($om, $oids, $lang) {
        $booking_lines_ids = $om->read(__CLASS__, $oids, ['booking_lines_ids']);
        if($booking_lines_ids > 0 && count($booking_lines_ids)) {
            BookingLine::_updatePriceId($om, $booking_lines_ids, $lang);
        }
    }

}