<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace sale\booking;
use equal\orm\Model;

class BookingFollowup extends Model {

    public static function getName() {
        return "Followup";
    }

    public static function getDescription() {
        return "Followup entries are notes created by users to ease the internal communication related to bookings.";
    }

    public static function getColumns() {
        return [

            'creator' => [
                'type'              => 'many2one',
                'foreign_object'    => 'identity\User',
                'description'       => 'User who created the entry.',
            ],
            'message' => [
                'type'              => 'text',
                'description'       => "Communication regarding the booking."
            ],

        ];
    }

}