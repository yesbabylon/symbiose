<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace lodging\identity;

class Center extends \identity\Establishment {

    public static function getName() {
        return 'Center';
    }

    public static function getDescription() {
        return "A center is an accommodation establishment providing overnight lodging and holding one or more rental unit(s).";
    }

    public static function getColumns() {

        return [

            'center_group_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'lodging\identity\CenterGroup',
                'description'       => 'Management Group to which the center belongs.'
            ],            

            'code_alpha' => [
                'type'              => 'string',
                'description'       => 'Unique alpha identifier of the center (2 uppercase letters).'
            ],

            'use_group_details' => [
                'type'              => 'boolean',
                'description'       => "Use the Center Group contact details in booking communications (instead of the ones of the center)?",
                'default'           => false
            ],            

            /*
                The manager is stored as part of the Center object.
            */
            'manager_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'identity\Partner',
                'domain'            => ['relationship', '=', 'employee'],
                'description'       => 'Manager of the center, if any.'
            ],


            'price_list_category_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\price\PriceListCategory',
                'description'       => "Price list category used by the center.",
                'required'          => true
            ],

            'discount_list_category_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\discount\DiscountListCategory',
                'description'       => 'Discount list category used by the center.',
                'required'          => true
            ],

            'autosale_list_category_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\autosale\AutosaleListCategory',
                'description'       => 'Autosale list category used by the center.',
                'required'          => true
            ],

            'season_category_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\season\SeasonCategory',
                'description'       => "Category of seasons used by the center.",
                'required'          => true
            ],

            'template_category_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'communication\TemplateCategory',
                'description'       => "Template category used by the center.",
                'required'          => true
            ],

            'categories_ids' => [
                'type'              => 'many2many',
                'foreign_object'    => 'lodging\identity\CenterCategory',
                'foreign_field'     => 'centers_ids',
                'rel_table'         => 'lodging_identity_rel_center_category',
                'rel_foreign_key'   => 'category_id',
                'rel_local_key'     => 'center_id',
                'description'       => 'List of categories the center belgons to, if any.'
            ],

            'rental_units_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'lodging\realestate\RentalUnit',
                'foreign_field'     => 'center_id',
                'description'       => 'List of rental units of the center.'
            ],

            'product_families_ids' => [
                'type'              => 'many2many',
                'foreign_object'    => 'lodging\sale\catalog\Family',
                'foreign_field'     => 'centers_ids',
                'rel_table'         => 'sale_product_family_rel_identity_center',
                'rel_foreign_key'   => 'family_id',
                'rel_local_key'     => 'center_id'
            ],

            'users_ids' => [
                'type'              => 'many2many',
                'foreign_object'    => 'lodging\identity\User',
                'foreign_field'     => 'centers_ids',
                'rel_table'         => 'sale_identity_rel_center_user',
                'rel_foreign_key'   => 'user_id',
                'rel_local_key'     => 'center_id'
            ],

            'product_groups_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'lodging\sale\catalog\Group',
                'foreign_field'     => 'center_id',
                'description'       => "Group targeted by the center.",
                'required'          => true
            ]

        ];
    }

    public function getUnique() {
        return [
            ['code_alpha']
        ];
    }


    public static function getConstraints() {
        return array_merge(parent::getConstraints(), [
            'code_alpha' =>  [
                'invalid' => [
                    'message'       => 'Must be 2 upper case letters.',
                    'function'      => function ($code_alpha, $values) {
                        return (preg_match('/^[A-Z]{2}+$/', (string) $code_alpha));
                    }
                ]
            ]
        ]);
    }
}