<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace lodging\sale\customer;

class Customer extends \sale\Customer {

    public static function getColumns() {

        return [

            'bookings_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'lodging\sale\booking\Booking',
                'foreign_field'     => 'customer_id',
                'description'       => "The bookings history of the customer.",
            ]

        ];
    }
}