<?php
namespace symbiose\sale\product;
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
                'foreign_object'    => 'symbiose\sale\product\Family',
                'required'          => true
            ],
            'selling_accounting_rule_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'symbiose\finance\accounting\AccountingRule'
            ],
            'buying_accounting_rule_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'symbiose\finance\accounting\AccountingRule'
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
            'is_pack' => [
                'type'              => 'boolean',
                'description'       => "Is this a bundle of other products?",
                'default'           => false
            ],
            'has_own_price' => [
                'type'              => 'boolean',
                'description'       => 'Is the pack a bundle of products with their related prices, or a catalog product of its own with specific price?',
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
                'visible'           => [ ['type', '=', 'service'], ['service_type', '=', 'schedulable'] ]
            ],
            'schedule_default_value' => [
                'type'              => 'string',
                'description'       => 'Multipurpose string representing the default value of the schedule according to its type (time, timerange).',
                'visible'           => [ ['type', '=', 'service'], ['service_type', '=', 'schedulable'] ]
            ],
            'schedule_offset' => [
                'type'              => 'integer',
                'description'       => 'Default number of days to set-off the service from the sojourn start date.',
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
            'group_ids' => [ 
                'type'              => 'many2many',
                'foreign_object'    => 'symbiose\sale\product\Group',
                'foreign_field'     => 'productmodel_ids',
                'rel_table'         => 'symbiose_sale_prooduct_rel_productmodel_group',
                'rel_foreign_key'   => 'group_id',
                'rel_local_key'     => 'productmodel_id'
            ],
            'category_ids' => [ 
                'type'              => 'many2many',
                'foreign_object'    => 'symbiose\sale\product\Category',
                'foreign_field'     => 'productmodel_ids',
                'rel_table'         => 'symbiose_sale_prooduct_rel_productmodel_category',
                'rel_foreign_key'   => 'category_id',
                'rel_local_key'     => 'productmodel_id'
            ]            

            
        ];
    }

}