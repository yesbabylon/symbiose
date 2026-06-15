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

    const MAP_DURATION_OFFSETS = [
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
                'dependents'        => ['price_id'],
                'onupdate'          => 'onupdateDateFrom'
            ],

            'date_to' => [
                'type'              => 'date',
                'description'       => 'End date of subscription.',
                'required'          => true,
                'default'           => function () { return strtotime('+1 year'); },
                'dependents'        => ['price_id', 'is_expired','has_upcoming_expiry']
            ],

            'duration' => [
                'type'              => 'string',
                'selection'         => [
                    'monthly'     => 'Monthly',
                    'quarterly'   => 'Quarterly',
                    'half-yearly' => 'Half-yearly',
                    'yearly'      => 'Yearly'
                ],
                'description'       => 'Duration of the subscription.',
                'help'              => 'If not auto renewable, no sale entries will be generated after `date_to`.',
                'default'           => 'yearly'
            ],

            'pricing_mode' => [
                'type'              => 'string',
                'selection'         => [
                    'fixed'       => 'Fixed',
                    'consumption' => 'Consumption'
                ],
                'description'       => 'Pricing mode of the subscription.',
                'help'              => 'Fixed subscriptions use a price list. Consumption subscriptions are completed on each generated entry once usage is known.',
                'default'           => 'fixed',
                'dependents'        => ['price_id', 'price']
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
                'dependents'        => ['price_id']
            ],

            'price_id' => [
                'type'              => 'computed',
                'result_type'       => 'many2one',
                'foreign_object'    => 'sale\price\Price',
                'description'       => 'Price of the sale.',
                'dependents'        => ['price'],
                'store'             => true,
                'function'          => 'calcPriceId'
            ],

            'price' => [
                'type'              => 'computed',
                'result_type'       => 'float',
                'usage'             => 'amount/money:4',
                'function'          => 'calcPrice',
                'store'             => true,
                'description'       => 'Price of the subscription.',
                'help'              => 'This is a computed price and not stored, since it depends on the price list that relate to the subsequent sales.',
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

    protected static function onupdateDateFrom($self) {
        $self->read(['date_from', 'duration']);
        foreach($self as $id => $subscription) {
            $date_to = $subscription['date_from'] ? strtotime(self::MAP_DURATION_OFFSETS[$subscription['duration']], $subscription['date_from']) : null;
            self::id($id)->update(['date_to' => $date_to]);
        }
    }

    public static function onchange($event, $values): array {
        $result = [];

        $duration = $event['duration'] ?? $values['duration'] ?? 'yearly';
        $date_from =  $event['date_from'] ?? $values['date_from'] ?? null;
        $date_to = $date_from ? strtotime(self::MAP_DURATION_OFFSETS[$duration], $date_from) : null;

        if( isset($event['date_from']) || isset($event['duration']) ) {
            $now = time();

            $days_until_expiry = ($date_to - $now) / 86400;

            $result['date_to'] = $date_to;
            $result['is_expired'] = $now > $date_to;
            $result['has_upcoming_expiry'] = $days_until_expiry < 30;
        }

        $pricing_mode = $event['pricing_mode'] ?? $values['pricing_mode'] ?? 'fixed';

        if($pricing_mode === 'consumption') {
            $result['price_id'] = null;
            $result['price'] = null;
        }
        elseif( (isset($event['product_id']) || isset($event['pricing_mode'])) && isset($date_from, $date_to) ) {
            $product_id = $event['product_id'] ?? $values['product_id'] ?? null;

            if($product_id) {
                $price_id = self::computePriceId(
                    $product_id,
                    $date_from,
                    $date_to
                );

                if($price_id) {
                    $price = Price::id($price_id)->read(['id', 'name'])->first();
                    if($price) {
                        $result['price_id'] = [
                            'id'    => $price['id'],
                            'name'  => $price['name']
                        ];
                        $result['price'] = self::computePrice($price_id, $duration);
                    }
                }
                else {
                    $result['price_id'] = null;
                    $result['price'] = null;
                }
            }
        }

        return $result;
    }

    /**
     * Retrieves all published PriceLists that are active at the given date,
     * i.e. whose validity period includes the provided $date_from.
     *
     * Among the matching PriceLists, the results are sorted by duration
     * (shortest first) so that the most specific PriceList can be selected.
     */
    private static function computePriceListsIds($date_from, $date_to) {
        return PriceList::search(
                [
                    ['date_from', '<=', $date_from],
                    ['date_to', '>=', $date_from],
                    ['status', '=', 'published'],
                ],
                ['sort' => ['duration' => 'desc']]
            )
            ->ids();
    }

    private static function computePriceId($product_id, $date_from, $date_to) {
        $result = null;

        $price_lists_ids = self::computePriceListsIds($date_from, $date_to);
        if(!empty($price_lists_ids)) {
            $price = Price::search([
                    ['product_id', '=', $product_id],
                    ['price_list_id', 'in', $price_lists_ids]
                ])
                ->first();

            if($price) {
                $result = $price['id'];
            }
        }

        return $result;
    }

    public static function calcPriceId($self): array {
        $result = [];
        $self->read(['pricing_mode', 'product_id', 'date_from', 'date_to']);
        foreach($self as $id => $subscription) {
            if(($subscription['pricing_mode'] ?? 'fixed') === 'consumption') {
                $result[$id] = null;
                continue;
            }

            if(isset($subscription['product_id'], $subscription['date_from'], $subscription['date_to'])) {
                $price_id = self::computePriceId(
                    $subscription['product_id'],
                    $subscription['date_from'],
                    $subscription['date_to']
                );

                $result[$id] = $price_id;
            }
        }

        return $result;
    }

    private static function computePrice($price_id, $duration) {
        $result = null;
        static $map_period_months = [
                'monthly'     => 1,
                'quarterly'   => 3,
                'half-yearly' => 6,
                'yearly'      => 12
            ];

        $price = Price::id($price_id)->read(['price', 'has_period', 'period'])->first();
        if(isset($price['price'])) {
            $result = $price['price'];
            if($price['has_period']) {
                $price_months = $map_period_months[$price['period']] ?? 1;
                $subscription_months = $map_period_months[$duration] ?? 1;
                $factor = $subscription_months / $price_months;
                $result = round($result * $factor, 2);
            }
        }

        return $result;
    }

    protected static function calcPrice($self): array {
        $result = [];
        $self->read(['pricing_mode', 'duration', 'price_id' => ['price', 'has_period', 'period']]);

        foreach($self as $id => $subscription) {
            if(($subscription['pricing_mode'] ?? 'fixed') === 'consumption') {
                $result[$id] = null;
                continue;
            }

            if(isset($subscription['price_id']['id'], $subscription['duration'])) {
                $price = self::computePrice(
                    $subscription['price_id']['id'],
                    $subscription['duration']
                );

                $result[$id] = $price;
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
                $days_until_expiry = ($subscription['date_to'] - time()) / 86400;
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
