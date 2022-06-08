<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace lodging\identity;
use equal\orm\Model;

class CenterCategory extends Model {

    public static function getName() {
        return 'Center category';
    }

    public static function getColumns() {

        return [

            'name' => [
                'type'              => 'string',
                'description'       => 'Category name.'
            ],

            'centers_ids' => [
                'type'              => 'many2many',
                'foreign_object'    => 'lodging\identity\Center',
                'foreign_field'     => 'categories_ids',
                'rel_table'         => 'lodging_identity_rel_center_category',
                'rel_foreign_key'   => 'center_id',
                'rel_local_key'     => 'category_id',
                'description'       => 'List of categories the center belgons to, if any.'
            ],

            /*
            // #memo - center categories are just an indication for centers, but do no apply on RU (RU can be GA or GG)
            'rental_units_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'lodging\realestate\RentalUnit',
                'foreign_field'     => 'center_category_id',
                'description'       => 'List of rental units related to the category.'
            ],
            */

            /*
            'accounting_rules_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'lodging\finance\accounting\AccountingRule',
                'foreign_field'     => 'center_category_id'
            ]
            */
        ];
    }
}