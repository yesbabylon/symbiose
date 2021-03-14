<?php
namespace symbiose\sale\price;
use qinoa\orm\Model;

class Price extends Model {
    public static function getColumns() {
        /**
         */

        return [
            'price' => [
                'type'              => 'float',
                'usage'             => 'amount/money',
                'required'          => true
            ],
            'type' => [
                'type'              => 'string',
                'selection'         => ['simple', 'computed']
            ],
            'description' => [
                'type'              => 'string',
                'description'       => "Short description of the variant."
            ],
            'price_list_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'symbiose\sale\price\PriceList',
                'description'       => "Price List to which beliongs this price.",
                'required'          => true
            ]

        ];
    }

    public static function getUnique() {
        return [
            ['sku']
        ];
    }   
}