<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace sale\booking;
use equal\orm\Model;

class Contact extends \identity\Identity {

    public function getTable() {
        return str_replace('\\', '_', get_class($this));
    }

    public static function getName() {
        return "Contact";
    }

    public static function getDescription() {
        return "Booking contacts are persons involved in the organisation of a booking.";
    }
    

    public static function getColumns() {

        return [

            'booking_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\booking\Booking',
                'description'       => 'Booking the contact relates to.',
                'required'          => true
            ],

            'type' => [
                'type'              => 'string',
                'selection'         => [ 
                    'booking',          // person that is in charge of handling the booking details
                    'invoice',          // person to who the invoice of the booking must be sent
                    'contract',         // person to who the contract(s) must be sent
                    'sojourn'           // person that will be present during the sojourn (beneficiary)
                ],
                'description'       => 'The kind of contact, based on its responsibilities.'
            ],
     
        ];
    }

}