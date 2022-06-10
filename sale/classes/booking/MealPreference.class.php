<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace sale\booking;
use equal\orm\Model;

class MealPreference extends Model {

    public static function getName() {
        return "Meal preference";
    }

    public static function getDescription() {
        return "Sojourns can be assigned one or more meal preferences that apply on each meal of the sojourn.";
    }

    public static function getColumns() {

        return [

            'booking_line_group_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\booking\BookingLineGroup',
                'description'       => 'Group the line relates to (in turn, groups relate to their booking).',
                'ondelete'          => 'cascade',
                'required'          => true
            ],

            // #todo - there should be one line for each age_range defined in the group
            'age_range_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\customer\AgeRange',
                'description'       => 'Age range assigned to the preference.'
            ],

            'type' => [
                'type'              => 'string',
                'selection'         => [
                    '2_courses',
                    '3_courses'
                ],
                'default'           => '2_courses',
                'description'       => 'Type of menu (amount of courses).'
            ],

            'pref' => [
                'type'              => 'string',
                'selection'         => [
                    'regular',              // omnivorous
                    'veggie',               // vegetarian, vegan
                    'allergen_free'
                ],
                'default'           => 'regular',
                'description'       => "Meal preference or indication (allergy, aversion, intolerance, beliefs)."
            ],

            'qty' => [
                'type'              => 'integer',
                'description'       => 'Quantity of people assigned to this preference.',
                'default'           => 0
            ]

        ];
    }


    public function getUnique() {
        return [
        // #todo - to adapt once the structure will be final
        // ['booking_line_group_id', 'type', 'pref']
        ];
    }

}