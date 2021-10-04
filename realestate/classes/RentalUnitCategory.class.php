<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace realestate;
use equal\orm\Model;

class RentalUnitCategory extends Model {

    public static function getColumns() {

        return [

            'name' => [
                'type'              => 'string',
                'description'       => "Name of the rental unit category."
            ],

            'description' => [
                'type'              => 'string',
                'description'       => "Reason of the categorization of rental units.",
                'multilang'         => true
            ],

            'rental_units_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'realestate\RentalUnit',
                'foreign_field'     => 'rental_unit_category_id',
                'description'       => 'The rental units that are assigned to the category.'
            ]

        ];
    }

}