<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace sale\booking;

class Contact extends \identity\Partner {

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
            ],

            'email' => [
                'type'              => 'computed',
                'function'          => 'sale\booking\Contact::getEmail',
                'result_type'       => 'string',
                'usage'             => 'email',
                'description'       => 'Email of the contact (from Identity).'
            ],

            'phone' => [
                'type'              => 'computed',
                'function'          => 'sale\booking\Contact::getPhone',
                'result_type'       => 'string',
                'usage'             => 'phone',
                'description'       => 'Phone number of the contact (from Identity).'
            ]
     
        ];
    }

    public static function getEmail($om, $oids, $lang) {
        $result = [];
        $partners = $om->read(__CLASS__, $oids, ['partner_identity_id.email'], $lang);
        foreach($partners as $oid => $partner) {
            $result[$oid] = '';
            if(isset($partner['partner_identity_id.email'])) {
                $result[$oid] = $partner['partner_identity_id.email'];
            }
        }
        return $result;
    }


    public static function getPhone($om, $oids, $lang) {
        $result = [];
        $partners = $om->read(__CLASS__, $oids, ['partner_identity_id.phone'], $lang);
        foreach($partners as $oid => $partner) {
            $result[$oid] = '';
            if(isset($partner['partner_identity_id.phone'])) {
                $result[$oid] = $partner['partner_identity_id.phone'];
            }
        }
        return $result;
    }
}