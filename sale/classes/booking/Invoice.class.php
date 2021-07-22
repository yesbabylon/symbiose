<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace sale\booking;

class Invoice extends \finance\accounting\Invoice {
    
    public static function getName() {
        return "Invoice";
    }

    public static function getDescription() {
        return "An invoice is a legal document that relates to a booking, and is part of the accounting system.";
    }

    public static function getColumns() {

        return [

            'booking_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\booking\Booking',
                'description'       => 'Booking the invoice relates to.',
                'required'          => true
            ]

        ];
    }    

}