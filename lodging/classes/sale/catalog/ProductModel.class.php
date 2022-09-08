<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace lodging\sale\catalog;


class ProductModel extends \sale\catalog\ProductModel {

    /*
        This class extends the ProductModel with fields specific to property rental.
    */

    public static function getColumns() {

        return [
            'qty_accounting_method' => [
                'type'              => 'string',
                'description'       => 'The way the product quantity has to be computed (per unit [default], per person, or per accomodation [resource]).',
                'selection'         => [
                    'person',           // depends on the number of people
                    'accomodation',     // depends on the number of nights
                    'unit'              // only depends on quantity
                ],
                'default'           => 'unit'
            ],

            'booking_type_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\booking\BookingType',
                'description'       => "The kind of booking it is about.",
                'default'           => 1                // default to 'general public'
            ],

            'is_accomodation' => [
                'type'              => 'boolean',
                'description'       => 'Model relates to a rental unit that is an accomodation.',
                'visible'           => [ ['type', '=', 'service'], ['is_rental_unit', '=', true] ]
            ],

            'is_rental_unit' => [
                'type'              => 'boolean',
                'description'       => 'Is the product a rental_unit?',
                'default'           => false,
                'visible'           => [ ['type', '=', 'service'], ['is_meal', '=', false] ]
            ],

            'is_meal' => [
                'type'              => 'boolean',
                'description'       => 'Is the product a meal? (meals might be part of the board / included services of the stay).',
                'default'           => false,
                'visible'           => [ ['type', '=', 'service'], ['is_rental_unit', '=', false] ]
            ],

            'rental_unit_assignement' => [
                'type'              => 'string',
                'description'       => 'The way the product is assigned to a rental unit (a specific unit, a specific category, or based on capacity match).',
                'selection'         => [
                    'unit',             // only one specific rental unit can be assigned to the products
                    'category',         // only rental units of the specified category can be assigned to the products
                    'auto'              // rental unit assignement is based on required qty/capacity (best match first)
                ],
                'default'           => 'category',
                'visible'           => [ ['is_rental_unit', '=', true] ]
            ],

            'has_duration' => [
                'type'              => 'boolean',
                'description'       => 'Does the product have a specific duration.',
                'default'           => false,
                'visible'           => ['type', '=', 'service']
            ],

            'duration' => [
                'type'              => 'integer',
                'description'       => 'Duration of the service (in days), used for planning.',
                'default'           => 1,
                'visible'           => [ ['type', '=', 'service'], ['has_duration', '=', true] ]
            ],

            'capacity' => [
                'type'              => 'integer',
                'description'       => 'Capacity implied by the service (used for filtering rental units).',
                'default'           => 1,
                'visible'           => [ ['is_rental_unit', '=', true] ]
            ],

            // a product either refers to a specific rental unit, or to a category of rental units (both allowing to find matching units for a given period and a capacity)
            'rental_unit_category_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'realestate\RentalUnitCategory',
                'description'       => "Rental Unit Category this Product related to, if any.",
                'visible'           => [ ['is_rental_unit', '=', true], ['rental_unit_assignement', '=', 'category'] ]
            ],

            'rental_unit_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'lodging\realestate\RentalUnit',
                'description'       => "Specific Rental Unit this Product related to, if any",
                'visible'           => [ ['is_rental_unit', '=', true], ['rental_unit_assignement', '=', 'unit'] ],
                'onupdate'          => 'onupdateRentalUnitId'
            ],

            'products_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'lodging\sale\catalog\Product',
                'foreign_field'     => 'product_model_id',
                'description'       => "Product variants that are related to this model.",
            ],

            'groups_ids' => [
                'type'              => 'many2many',
                'foreign_object'    => 'lodging\sale\catalog\Group',
                'foreign_field'     => 'product_models_ids',
                'rel_table'         => 'sale_catalog_product_rel_productmodel_group',
                'rel_foreign_key'   => 'group_id',
                'rel_local_key'     => 'productmodel_id',
                'onupdate'          => 'sale\catalog\ProductModel::onupdateGroupsIds'
            ]

        ];
    }

    /**
     * Assigns the related rental unity capacity as own capacity.
     */
    public static function onupdateRentalUnitId($om, $oids, $values, $lang) {
        trigger_error("QN_DEBUG_ORM::calling lodging\sale\catalog\ProductModel:onupdateRentalUnitId", QN_REPORT_DEBUG);

        $models = $om->read(__CLASS__, $oids, ['rental_unit_id.capacity', 'rental_unit_id.is_accomodation'], $lang);

        foreach($models as $mid => $model) {
            $om->write(get_called_class(), $mid, ['capacity' => $model['rental_unit_id.capacity'], 'is_accomodation' => $model['rental_unit_id.is_accomodation']]);
        }
    }
}