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
            'price_list_category_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'symbiose\sale\price\PriceListCategory',
                'description'       => "Category this list is related to, if any.",
            ]
            
        ];
    }
}