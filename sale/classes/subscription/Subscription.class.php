<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2024
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

namespace sale\subscription;

use equal\orm\Model;
use sale\price\Price;
use sale\price\PriceList;

class Subscription extends Model  {

    public static function getDescription() {
        return 'A subscription is a recurring payment model where a customer pays regularly, typically monthly or annually, to access a product or service.'
            .' An internal subscription is used by your business, so it can\'t be invoiced to customers.';
    }

    const MAP_DURATION = [
        'monthly'      => '+1 month',
        'quarterly'    => '+3 month',
        'half-yearly'  => '+6 month',
        'yearly'       => '+1 year'
    ];

    public static function getColumns(): array
    {
        return [
            'name' => [
                'type'              => 'string',
                'unique'            => true,
                'required'          => true,
                'description'       => 'Name of the subscription.'
            ],

            'description' => [
                'type'              => 'string',
                'usage'             => 'text/plain',
                'description'       => 'Information about a subscription.'
            ],

            'date_from' => [
                'type'              => 'date',
                'required'          => true,
                'description'       => 'Start date of subscription.',
                'default'           => function () { return time(); },
                'dependencies'      => ['price_id']
            ],

            'date_to' => [
                'type'              => 'date',
                'description'       => 'End date of subscription.',
                'required'          => true,
                'default'           => function () { return strtotime('+1 year'); },
                'dependencies'      => ['price_id', 'is_expired','has_upcoming_expiry']
            ],

            'duration' => [
                'type'              => 'string',
                'selection'         => [
                    'monthly'     => 'Monthly',
                    'quarterly'   => 'Quarterly',
                    'half-yearly' => 'Half-yearly',
                    'yearly'      => 'Yearly'
                ],
                'description'       => 'Type of the duration.',
                'default'           => 'yearly'
            ],

            'is_auto_renew' => [
                'type'              => 'boolean',
                'description'       => 'The subscription is auto renew.',
                'default'           => false,
                'onupdate'          =>'onupdateIsAutoRenew'
            ],

            'is_expired' => [
                'type'              => 'computed',
                'result_type'       => 'boolean',
                'description'       => 'The subscription is expired.',
                'function'          => 'calcIsExpired',
                'store'             => true,
                'instant'           => true
            ],

            'has_upcoming_expiry' => [
                'type'              => 'computed',
                'description'       => 'The subscription is  upcoming expiry.',
                'result_type'       => 'boolean',
                'function'          => 'calcUpcomingExpiry',
                'store'             => true,
                'instant'           => true
            ],

            'ref_order' => [
                'type'              => 'string',
                'description'       => 'Subscription reference number.'
            ],

            'license_key' => [
                'type'              => 'string',
                'description'       => 'Subscription license key.'
            ],

            'customer_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\customer\Customer',
                'description'       => 'The Customer concerned by the subscription.'
            ],

            'is_billable' => [
                'type'              => 'boolean',
                'description'       => 'Can be billed to the customer.',
                'default'           => true
            ],

            'product_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\catalog\Product',
                'description'       => 'Product of the catalog sale.',
                'dependencies'      => ['price_id']
            ],

            'price_id' => [
                'type'              => 'computed',
                'result_type'       => 'many2one',
                'foreign_object'    => 'sale\price\Price',
                'description'       => 'Price of the sale.',
                'dependencies'      => ['price'],
                'store'             => true,
                'function'          => 'calcPriceId'

            ],

            'price' => [
                'type'              => 'computed',
                'result_type'       => 'float',
                'usage'             => 'amount/money:4',
                'description'       => 'Price of the subscription.',
                'store'             => true,
                'function'          => 'calcPrice'
            ],

            'subscription_entries_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'sale\subscription\SubscriptionEntry',
                'foreign_field'     => 'subscription_id',
                'ondetach'          => 'delete',
                'description'       => 'Subscription entries of the subscription.'
            ]

        ];
    }

    public static function onchange($event, $values): array {
        $result = [];

        if( isset($event['date_from']) || isset($event['duration']) ) {
            $now = time();
            $date_from =  $event['date_from'] ??  $values['date_from'];
            $duration = self::MAP_DURATION[$event['duration'] ?? $values['duration']];
            $date_to = strtotime($duration, $date_from);
            $seconds_in_a_day = 60 * 60 * 24;
            $days_until_expiry = ($date_to - $now) / $seconds_in_a_day;

            $result['date_to'] = $date_to;
            $result['is_expired'] = $now > $date_to;
            $result['has_upcoming_expiry'] = $days_until_expiry < 30;
        }

        if( isset($event['product_id'])
                && isset($values['date_from'])
                && isset($values['date_to']) ) {
            $price = self::getProductPrice(
                    $event['product_id'],
                    $values['date_from'],
                    $values['date_to']
                );

            $result['price_id'] = $price;
            $result['price'] = $price['price'];
        }

        return $result;
    }

    private static function getPriceListsIds($date_from, $date_to) {
        return PriceList::search([
                [
                    ['date_from', '<', $date_from],
                    ['date_to', '>=', $date_from],
                    ['date_to', '<=', $date_to],
                    ['status', '=', 'published'],
                ],
                [
                    ['date_from', '>=', $date_from],
                    ['date_to', '>=', $date_from],
                    ['date_to', '<=', $date_to],
                    ['status', '=', 'published'],
                ],
                [
                    ['date_from', '>=', $date_from],
                    ['date_to', '>', $date_to],
                    ['status', '=', 'published'],
                ],
                [
                    ['date_from', '<', $date_from],
                    ['date_to', '>', $date_to],
                    ['status', '=', 'published'],
                ]
            ])
            ->ids();
    }

    public static function getProductPrice($product_id, $date_from, $date_to) {
        $price = null;

        $price_lists_ids = self::getPriceListsIds($date_from, $date_to);
        if(!empty($price_lists_ids)) {
            $price = Price::search([
                    ['product_id', '=', $product_id],
                    ['price_list_id', 'in', $price_lists_ids]
                ])
                ->read(['id', 'name', 'price'])
                ->first();
        }

        return $price;
    }

    public static function calcPriceId($self): array {
        $result = [];
        $self->read(['product_id', 'date_from', 'date_to']);
        foreach($self as $id => $subscription) {
            if(isset($subscription['product_id'], $subscription['date_from'], $subscription['date_to'])) {
                $price = self::getProductPrice(
                    $subscription['product_id'],
                    $subscription['date_from'],
                    $subscription['date_to']
                );

                $result[$id] = $price['id'];
            }
        }

        return $result;
    }

    public static function calcPrice($self): array {
        $result = [];
        $self->read(['price_id' => ['price']]);
        foreach($self as $id => $subscription) {
            if(isset($subscription['price_id']['price'])) {
                $result[$id] = $subscription['price_id']['price'];
            }
        }

        return $result;
    }

    public static function calcIsExpired($self): array {
        $result = [];
        $self->read(['date_to']);
        foreach($self as $id => $subscription) {
            if(isset($subscription['date_to'])) {
                $result[$id] = (time() > $subscription['date_to']);
            }
        }

        return $result;
    }

    public static function calcUpcomingExpiry($self): array {
        $result = [];
        $self->read(['date_to']);
        foreach($self as $id => $subscription) {
            if(isset($subscription['date_to'])) {
                $seconds_in_a_day = 60 * 60 * 24;
                $days_until_expiry = ($subscription['date_to'] - time()) / $seconds_in_a_day;
                $result[$id] = $days_until_expiry < 30;
            }
        }

        return $result;
    }

    /**
     *
     * @param  \equal\orm\ObjectManager     $om
     * @param  array                        $ids
     * @return void
     */
    public static function onupdateIsAutoRenew($om, $ids, $values, $lang) {
        $subscriptions = $om->read(self::getType(), $ids, ['date_to','is_auto_renew']);
        $cron = $om->getContainer()->get('cron');

        foreach($subscriptions as $id => $subscription) {
            if($subscription['is_auto_renew']) {
                $cron->schedule(
                    "subscription.{$id}.create.subscriptionEntry",
                     $subscription['date_to'],
                    'sale_subscription_add-subscriptionentry',
                    [ 'id' => $id ]
                );
            }
        }
    }

}
