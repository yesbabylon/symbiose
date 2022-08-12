<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace lodging\sale\pos;

class OrderLine extends \sale\pos\OrderLine {

    public static function getColumns() {

        return [
            'order_id' => [
                'type'              => 'many2one',
                'foreign_object'    => Order::getType(),
                'description'       => 'The operation the payment relates to.'
            ],

            // we override for using .center_id.price_list_category_id
            'product_id' => [
                'type'              => 'many2one',
                'foreign_object'    => \sale\catalog\Product::getType(),
                'description'       => 'The product (SKU) the line relates to.',
                'onupdate'          => 'onupdateProductId'
            ],
            
            'order_payment_id' => [
                'type'              => 'many2one',
                'foreign_object'    => OrderPayment::getType(),
                'description'       => 'The payement the line relates to.',
                'default'           => 0,
                'ondelete'          => 'null'
            ],

            'has_booking' => [
                'type'              => 'boolean',
                'description'       => 'Mark the line as paid using a booking.',
                'default'           => false
            ]

        ];
    }

    public static function onupdateProductId($om, $oids, $values, $lang) {
        $lines = $om->read(self::getType(), $oids, ['product_id', 'order_id.center_id.price_list_category_id']);

        foreach($lines as $lid => $line) {
            /*
                Find the first Price List that matches the criteria from the order with (shortest duration first)
            */
            $price_lists_ids = $om->search(
                'sale\price\PriceList', [
                    ['price_list_category_id', '=', $line['order_id.center_id.price_list_category_id']],
                    ['date_from', '<=', time()],
                    ['date_to', '>=', time()],
                    ['status', 'in', ['published']],
                    ['is_active', '=', true]
                ],
                ['duration' => 'asc']
            );

            $found = false;

            if($price_lists_ids > 0 && count($price_lists_ids)) {
                /*
                    Search for a matching Price within the found Price List
                */
                foreach($price_lists_ids as $price_list_id) {
                    // there should be one or zero matching pricelist with status 'published', if none of the found pricelist
                    $prices_ids = $om->search(\sale\price\Price::getType(), [ ['price_list_id', '=', $price_list_id], ['product_id', '=', $line['product_id']] ]);
                    if($prices_ids > 0 && count($prices_ids)) {
                        /*
                            Assign found Price to current line
                        */
                        $prices = $om->read(\sale\price\Price::getType(), $prices_ids, ['id', 'price', 'vat_rate']);
                        $price = reset($prices);
                        // set unit_price and vat_rate from found price
                        $om->update(self::getType(), $lid, ['price_id' => $price['id'], 'unit_price' => $price['price'], 'vat_rate' => $price['vat_rate']]);
                        $found = true;
                        break;
                    }
                }
            }
            if(!$found) {
                $date = date('Y-m-d', time());
                trigger_error("QN_DEBUG_ORM::no matching price list found for product {$line['product_id']} for date {$date}", QN_REPORT_ERROR);
            }
        }
    }
    
}