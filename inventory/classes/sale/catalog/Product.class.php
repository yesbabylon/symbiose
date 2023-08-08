<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace inventory\sale\catalog;


class Product extends \sale\catalog\Product {

    public static function getColumns() {
        return [
            'subscriptions_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'inventory\service\Subscription',
                'foreign_field'     => 'product_id',
                'description'       => 'The subscriptions about the service belongs.'
            ],
        ];
    }

}