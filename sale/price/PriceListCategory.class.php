<?php
namespace sale\price;
use equal\orm\Model;

class PriceListCategory extends Model {
    public static function getColumns() {
        /**
         */

        return [
            'name' => [
                'type'              => 'string',
                'description'       => "Short label to ease identification of the list."
            ],
            'price_list_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'sale\price\PriceList',
                'foreign_field'     => 'price_list_category_id',
                'description'       => "Lists that are related to this category, if any."
            ]            
        ];
    }
}