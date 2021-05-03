<?php
namespace symbiose\sale\price;
use qinoa\orm\Model;

class PriceList extends Model {
    public static function getColumns() {
        /**
         */

        return [
            'name' => [
                'type'              => 'string',
                'description'       => "Short label to ease identification of the list."
            ],
            'date_from' => [
                'type'              => 'datetime',
                'description'       => "Sart of validity period."
            ],
            'date_to' => [
                'type'              => 'datetime',
                'description'       => "End of validity period."
            ],
            'price_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'symbiose\sale\price\Price',
                'foreign_field'     => 'price_list_id',
                'description'       => "Prices that are related to this list, if any.",
            ],
            'price_list_category_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'symbiose\sale\price\PriceListCategory',
                'description'       => "Category this list is related to, if any.",
            ]
            
        ];
    }
}