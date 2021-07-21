<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace lodging\realestate;


class RentalUnit extends \realestate\RentalUnit {

    public static function getDescription() {
        return "A rental unit is a ressource that can be rented to a customer.";
    }

    public static function getColumns() {
        return [

            'center_category_id' => [
                'type'              => 'many2one',
                'description'       => "Center category which current unit belongs to, if any.",
                'foreign_object'    => 'lodging\identity\CenterCategory'
            ],

            'center_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'lodging\identity\Center',
                'description'       => 'The center to which belongs the rental unit.' 
            ],

        ];
    }
}