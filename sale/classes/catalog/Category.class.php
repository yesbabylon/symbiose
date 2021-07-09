<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace sale\catalog;
use equal\orm\Model;

class Category extends Model {
    public static function getColumns() {
        /**
         * Categories are not related to Families and allow a different way of grouping Products.
         */
        return [
            'name' => [
                'type'              => 'string',
                'description'       => "Name of the product category (used for all variants).",
                'required'          => true
            ],

            'description' => [
                'type'              => 'string',
                'description'       => "Short string describing the purpose and usage of the category."
            ],
            
            'product_models_ids' => [ 
                'type'              => 'many2many', 
                'foreign_object'    => 'sale\catalog\ProductModel', 
                'foreign_field'     => 'categories_ids', 
                'rel_table'         => 'sale_product_rel_productmodel_category', 
                'rel_foreign_key'   => 'productmodel_id',
                'rel_local_key'     => 'category_id',
                'description'       => 'List of product models assigned to this category.'
            ],

            'booking_types_ids' => [ 
                'type'              => 'many2many', 
                'foreign_object'    => 'sale\booking\Type', 
                'foreign_field'     => 'product_categories_ids', 
                'rel_table'         => 'sale_rel_productcategory_bookingtype', 
                'rel_local_key'     => 'productcategory_id',
                'rel_foreign_key'   => 'bookingtype_id',
                'description'       => 'List of booking types assigned to this category.'
            ]            
        ];
    }
}