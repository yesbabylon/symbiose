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


	public static function getName() {
        return "Product Model";
    }

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

            'is_accomodation' => [
                'type'              => 'boolean',
                'description'       => 'Is the product an accomodation?',
                'default'           => false,
                'visible'           => [ ['type', '=', 'service'], ['is_meal', '=', false] ] 
            ],

            'is_meal' => [
                'type'              => 'boolean',
                'description'       => 'Is the product a meal? (meals might be part of the board / included services of the stay).',
                'default'           => false,
                'visible'           => [ ['type', '=', 'service'], ['is_accomodation', '=', false] ]
            ],

            'rental_unit_assignement' => [
                'type'              => 'string',
                'description'       => 'The way the product is assigned to a rental unit (a specific unit, a specific category, or based on capacity match).',
                'selection'         => ['unit', 'category', 'capacity'],
                'default'           => 'category',
                'visible'           => [ ['is_accomodation', '=', true] ]
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
                'visible'           => ['has_duration', '=', true]
            ],

            'capacity' => [
                'type'              => 'integer',
                'description'       => 'Capacity implied by the service (used for filtering rental units).',
                'default'           => 1,
                'visible'           => [ ['is_accomodation', '=', true] ]
            ],

            // a product either refers to a specific rental unit, or to a category of rental units (both allowing to find matching units for a given period and a capacity)
            'rental_unit_category_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'realestate\RentalUnitCategory',
                'description'       => "Rental Unit Category this Product related to, if any.",
                'visible'           => [ ['is_accomodation', '=', true], ['rental_unit_assignement', '=', 'category'] ]
            ],

            'rental_unit_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'lodging\realestate\RentalUnit',
                'description'       => "Specific Rental Unit this Product related to, if any",
                'visible'           => [ ['is_accomodation', '=', true], ['rental_unit_assignement', '=', 'unit'] ],
                'onchange'          => 'lodging\sale\catalog\ProductModel::onchangeRentalUnitId'
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
                'onchange'          => 'sale\catalog\ProductModel::onchangeGroupsIds'
            ]

        ];
    }

    /**
     * Assigns the related rental unity capacity as own capacity.
     */
    public static function onchangeRentalUnitId($om, $oids, $lang) {
        trigger_error("QN_DEBUG_ORM::calling lodging\sale\catalog\ProductModel:onchangeRentalUnitId", QN_REPORT_DEBUG);
        
        $models = $om->read(__CLASS__, $oids, ['rental_unit_id.capacity'], $lang);

        foreach($models as $mid => $model) {
            $om->write(get_called_class(), $mid, ['capacity' => $model['rental_unit_id.capacity']]);
        }
    }
}