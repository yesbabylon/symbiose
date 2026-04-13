<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2024
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

namespace sale;

use equal\orm\Model;
use sale\price\Price;

class SaleModel extends Model {

    public static function getName(): string {
        return 'Sale model';
    }

    public static function getDescription(): string {
        return 'A sale model allows to define a fixed product, price and unit price that can be applied to sale entries.';
    }

    public static function getColumns(): array {
        return [

            'name' => [
                'type'              => 'string',
                'description'       => 'Name of the sale model.',
                'required'          => true,
                'unique'            => true,
                'multilang'         => true
            ],

            'product_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\catalog\Product',
                'description'       => 'The product to assign to sale entries.'
            ],

            'price_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\price\Price',
                'description'       => 'The price to assign to sale entries.',
                'onupdate'          => 'onupdatePriceId',
                'domain'            => ['product_id', '=', 'object.product_id']
            ],

            'unit_price' => [
                'type'              => 'float',
                'usage'             => 'amount/money:4',
                'description'       => 'The unit price to assign to sale entries.',
                'help'              => 'Change to assign a custom unit price.'
            ],

            'projects_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'timetrack\Project',
                'foreign_field'     => 'sale_model_id',
                'description'       => 'Projects applying the sale model.'
            ]

        ];
    }

    public static function onupdatePriceId($self) {
        $self->read(['unit_price', 'price_id' => ['price']]);
        foreach($self as $id => $model) {
            if(is_null($model['unit_price'])) {
                self::id($id)->update(['unit_price' => $model['price_id']['price']]);
            }
        }
    }

    public static function onchange($event) {
        $result = [];
        if(isset($event['price_id'])) {
            $price = Price::id($event['price_id'])->read(['price'])->first();
            $result['unit_price'] = $price['price'];
        }
        return $result;
    }
}
