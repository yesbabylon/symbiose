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

            'name' => [
                'type'              => 'computed',
                'result_type'       => 'string',
                'description'       => "Code to serve as reference (might not be unique)",
                'function'          => 'lodging\sale\booking\Booking::getDisplayName',
                'store'             => true,
                'readonly'          => true
            ],

            'customer_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\customer\Customer',
                'domain'            => ['relationship', '=', 'customer'],
                'description'       => "The customer to whom the booking relates.",
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

            'contracts_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'lodging\sale\booking\Contract',
                'foreign_field'     => 'booking_id',
                'description'       => 'List of contacts related to the booking, if any.'
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
            ],

            'rental_unit_assignments_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'lodging\sale\booking\BookingLineRentalUnitAssignement',
                'foreign_field'     => 'booking_id',
                'description'       => 'Rental units assignments related to the booking.'
            ],

            'price' => [
                'type'              => 'computed',
                'result_type'       => 'float',
                'usage'             => 'amount/money',
                'function'          => 'lodging\sale\booking\Booking::getPrice',
                'description'       => 'Total price (vat incl.) of the booking.'
            ]

        ];
    }

    public static function getDisplayName($om, $oids, $lang) {
        $result = [];

        $bookings = $om->read(__CLASS__, $oids, ['center_id.group_code'], $lang);

        foreach($bookings as $oid => $booking) {

            $settings_ids = $om->search('core\Setting', [
                ['name', '=', 'booking.sequence.'.$booking['center_id.group_code']],
                ['package', '=', 'sale'],
                ['section', '=', 'booking']
            ]);

            if($settings_ids < 0 || !count($settings_ids)) {
                // unexpected error : misconfiguration (setting is missing)
                $result[$oid] = 0;
                continue;
            }

            $settings = $om->read('core\SettingValue', $settings_ids, ['value']);
            if($settings < 0 || count($settings) != 1) {
                // unexpected error : misconfiguration (no value for setting)
                $result[$oid] = 0;
                continue;
            }

            $setting = array_pop($settings);
            $sequence = (int) $setting['value'];
            $om->write('core\SettingValue', $settings_ids, ['value' => $sequence + 1]);

            $result[$oid] = ((string) $booking['center_id.group_code']) . ((string) $sequence);

        }
        return $result;
    }


    public static function getPrice($om, $oids, $lang) {
        $result = [];
        $bookings = $om->read(__CLASS__, $oids, ['booking_lines_groups_ids']);
        if($bookings > 0 && count($bookings)) {
            foreach($bookings as $bid => $booking) {
                $groups = $om->read('lodging\sale\booking\BookingLineGroup', $booking['booking_lines_groups_ids'], ['price']);
                $result[$bid] = 0.0;
                if($groups > 0 && count($groups)) {
                    foreach($groups as $group) {
                        $result[$bid] += $group['price'];
                    }
                    $result[$bid] = round($result[$bid], 2);                    
                }
            }
        }
        return $result;
    }

    public static function onchangeCustomerId($om, $oids, $lang) {
        // force immediate recomputing of the name/reference
        $om->write(__CLASS__, $oids, ['name' => null]);
        $bookings = $om->read(__CLASS__, $oids, ['name', 'booking_lines_groups_ids', 'customer_id.partner_identity_id.description']);

        if($bookings > 0 && count($bookings) > 0) {
            foreach($bookings as $bid => $booking) {
                $booking_lines_groups_ids = $booking['booking_lines_groups_ids'];
                if($booking_lines_groups_ids > 0 && count($booking_lines_groups_ids)) {
                    BookingLineGroup::_updatePriceAdapters($om, $booking_lines_groups_ids, $lang);
                }
                $om->write(__CLASS__, $oids, ['description' => $booking['customer_id.partner_identity_id.description']]);
            }
        }
    }

    public static function onchangeCenterId($om, $oids, $lang) {
        $bookings = $om->read(__CLASS__, $oids, ['booking_lines_ids']);

        if($bookings > 0 && count($bookings) > 0) {
            foreach($bookings as $bid => $booking) {
                $booking_lines_ids = $booking['booking_lines_ids'];
                if($booking_lines_ids > 0 && count($booking_lines_ids)) {
                    BookingLine::_updatePriceId($om, $booking_lines_ids, $lang);
                }
            }
        }
    }

}