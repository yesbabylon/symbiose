<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace sale\booking;

class Contact extends \identity\Partner {

    public static function getName() {
        return "Booking Contact";
    }

    public static function getDescription() {
        return "Booking contacts are persons involved in the organisation of a booking.";
    }

    public function getTable() {
        // force table name to use distinct tables and ID columns
        return 'sale_booking_contact';
    }

    public static function getColumns() {

        return [

            'booking_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\booking\Booking',
                'description'       => 'Booking the contact relates to.',
                'required'          => true
            ],

            'relationship' => [
                'type'              => 'string',
                'default'           => 'contact',
                'description'       => "The partnership should remain 'contact'."
            ],

            'type' => [
                'type'              => 'string',
                'selection'         => [
                    'booking',          // person that is in charge of handling the booking details
                    'invoice',          // person to who the invoice of the booking must be sent
                    'contract',         // person to who the contract(s) must be sent
                    'sojourn'           // person that will be present during the sojourn (beneficiary)
                ],
                'description'       => 'The kind of contact, based on its responsibilities.',
                'default'           => 'booking'
            ]

        ];
    }

    public function getUnique() {
        return [
            ['owner_identity_id', 'partner_identity_id', 'booking_id']
        ];
    }


}