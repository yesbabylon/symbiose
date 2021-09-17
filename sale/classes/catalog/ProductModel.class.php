<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace sale\catalog;
use equal\orm\Model;

class ProductModel extends Model {

    public static function getName() {
        return "Product Model";
    }

    public static function getColumns() {
        /**
         * Product Models act as common denominator for products variants (referred to as "Products").
         * These objects are used for catalogs generation: for instance, if a picture is related to a Product, it is associated on the Product Model level.
         * A Product Model has at minimum one variant, which means at minimum one SKU.
         */

        return [
            'name' => [
                'type'              => 'string',
                'description'       => "Name of the product model (used for all variants).",
                'required'          => true
            ],

            'family_id' => [
                'type'              => 'many2one',
                'description'       => "Product Family which current product belongs to.",
                'foreign_object'    => 'sale\catalog\Family',
                'required'          => true
            ],

            'selling_accounting_rule_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'finance\accounting\AccountingRule'
            ],

            'buying_accounting_rule_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'finance\accounting\AccountingRule'
            ],

            'stat_section_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'finance\stats\StatSection',
                'description'       => 'Statistics section to which relates the product, if any.'
            ],

            'can_buy' => [
                'type'              => 'boolean',
                'description'       => "Can this product be purchassed?",
                'default'           => false
            ],

            'can_sell' => [
                'type'              => 'boolean',
                'description'       => "Can this product be sold?",
                'default'           => true
            ],

            'cost' => [
                'type'              => 'boolean',
                'description'       => 'Buying cost.',
                'visible'           => ['can_buy', '=', true]
            ],

            'is_pack' => [
                'type'              => 'boolean',
                'description'       => "Is the product a bundle of other products?",
                'default'           => false,
                'onchange'          => 'sale\catalog\ProductModel::onchangeIsPack'
            ],

            'pack_lines_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'sale\catalog\PackLine',
                'foreign_field'     => 'parent_product_id',
                'description'       => "Products that are bundled in the pack.",
            ],

            'has_own_price' => [
                'type'              => 'boolean',
                'description'       => 'Has the bundle its own price, or do we use each sub-product price?',
                'default'           => false,
                'visible'           => ['is_pack', '=', true]
            ],

            'type' => [
                'type'              => 'string',
                'selection'         => ['consumable', 'service'],
                'required'          => true
            ],

            'consumable_type' => [
                'type'              => 'string',
                'selection'         => ['simple', 'storable'],
                'visible'           => ['type', '=', 'consumable']
            ],

            'service_type' => [
                'type'              => 'string',
                'selection'         => ['simple', 'schedulable'],
                'visible'           => ['type', '=', 'service']
            ],

            'schedule_type' => [
                'type'              => 'string',
                'selection'         => ['time', 'timerange'],
                'default'           => 'time',
                'visible'           => [ ['type', '=', 'service'], ['service_type', '=', 'schedulable'] ]
            ],

            'schedule_default_value' => [
                'type'              => 'string',
                'description'       => "Default value of the schedule according to type (time: '9:00', timerange: '9:00-10:00').",
                'visible'           => [ ['type', '=', 'service'], ['service_type', '=', 'schedulable'] ]
            ],

            'schedule_offset' => [
                'type'              => 'integer',
                'description'       => 'Default number of days to set-off the service from a sojourn start date.',
                'default'           => 0,
                'visible'           => [ ['type', '=', 'service'], ['service_type', '=', 'schedulable'] ]
            ],

            'tracking_type' => [
                'type'              => 'string',
                'selection'         => ['none', 'batch', 'sku', 'upc'],
                'visible'           => [ ['type', '=', 'consumable'], ['consumable_type', '=', 'storable'] ]
            ],

            'description_delivery' => [
                'type'              => 'text',
                'description'       => "Description for delivery notes."
            ],

            'description_receipt' => [
                'type'              => 'text',
                'description'       => "Description for reception vouchers."
            ],

            'groups_ids' => [
                'type'              => 'many2many',
                'foreign_object'    => 'sale\catalog\Group',
                'foreign_field'     => 'product_models_ids',
                'rel_table'         => 'sale_product_rel_productmodel_group',
                'rel_foreign_key'   => 'group_id',
                'rel_local_key'     => 'productmodel_id'
            ],

            'categories_ids' => [
                'type'              => 'many2many',
                'foreign_object'    => 'sale\catalog\Category',
                'foreign_field'     => 'product_models_ids',
                'rel_table'         => 'sale_product_rel_productmodel_category',
                'rel_foreign_key'   => 'category_id',
                'rel_local_key'     => 'productmodel_id'
            ],

            'products_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'sale\catalog\Product',
                'foreign_field'     => 'product_model_id',
                'description'       => "Product variants that are related to this model.",
            ],

        ];
    }

    /**
     *
     * reset related products is_pack
     */
    public static function onchangeIsPack($om, $oids, $lang) {
        $models = $om->read(__CLASS__, $oids, ['products_ids']);
        $products_ids = [];
        foreach($models as $mid => $model) {
            $products_ids = array_merge($products_ids, $model['products_ids']);
        }
        $om->write('sale\catalog\Product', $products_ids, ['is_pack' => null]);
    }



}