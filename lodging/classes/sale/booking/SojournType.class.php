<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace lodging\sale\booking;
use equal\orm\Model;

class SojournType extends Model {

    public static function getName() {
        return 'Center category';
    }

    public static function getColumns() {

        return [

            'name' => [
                'type'              => 'string',
                'description'       => 'Sojour type name.',
                'required'          => true
            ],

            'description' => [
                'type'              => 'string',
                'usage'             => 'text/plain',
                'description'       => 'Comments detailing the use cases of the type.',
                'multilang'         => true
            ],

            'centers_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'lodging\identity\Center',
                'foreign_field'     => 'sojourn_type_id',
                'description'       => 'List of centers using the sojourn type by default.'
            ],

            'rental_units_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'lodging\realestate\RentalUnit',
                'foreign_field'     => 'sojourn_type_id',
                'description'       => 'List of rental units using the sojourn type by default.'
            ],

            'bookings_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'lodging\sale\booking\Booking',
                'foreign_field'     => 'sojourn_type_id',
                'description'       => 'List of bookings set to the sojourn type.'
            ]

        ];
    }
}