<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2024
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

namespace sale\subscription;

use business\subscription\SubscriptionAbstract;
use sale\price\Price;
use sale\price\PriceList;

class Subscription extends SubscriptionAbstract {

    public static function getDescription() {
        return 'A commercial subscription defines the recurring pricing and billing terms agreed with a customer.';
    }

    public static function getColumns(): array
    {
        return [
            'name' => [
                'type'              => 'string',
                'required'          => true,
                'description'       => 'Name of the subscription.',
                'dependents'        => ['subscription_entries_ids' => ['name']]
            ],

            'date_from' => [
                'type'              => 'date',
                'required'          => true,
                'description'       => 'Start date of subscription.',
                'default'           => function () { return time(); },
                'dependents'        => ['price_id', 'has_current_sale_entry'],
                'onupdate'          => 'onupdateDateFrom'
            ],

            'date_to' => [
                'type'              => 'date',
                'description'       => 'End date of subscription.',
                'required'          => true,
                'default'           => function () { return strtotime('+1 year'); },
                'dependents'        => ['price_id', 'is_expired', 'has_upcoming_expiry', 'has_current_sale_entry']
            ],

            'pricing_mode' => [
                'type'              => 'string',
                'selection'         => [
                    'fixed'       => 'Fixed',
                    'consumption' => 'Consumption'
                ],
                'description'       => 'Pricing mode of the subscription.',
                'help'              => 'Fixed subscriptions compute the period price. Consumption subscriptions multiply the selected unit price by the subscription quantity.',
                'default'           => 'fixed',
                'dependents'        => ['price_id', 'price']
            ],

            'qty' => [
                'type'              => 'float',
                'description'       => 'Quantity used to compute the subscription price.',
                'default'           => 1.0,
                'visible'           => ['pricing_mode', '=', 'consumption'],
                'dependents'        => ['price']
            ],

            'is_auto_renew' => [
                'type'              => 'boolean',
                'description'       => 'The subscription is auto renew.',
                'default'           => false,
                'onupdate'          =>'onupdateIsAutoRenew'
            ],

            'ref_order' => [
                'type'              => 'string',
                'description'       => 'Subscription reference number.'
            ],

            'license_key' => [
                'type'              => 'string',
                'description'       => 'Subscription license key.'
            ],

            'infra_subscription_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'infra\service\Subscription',
                'description'       => 'Linked service subscription, when applicable.',
                'readonly'          => true
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
                'help'              => 'The price is computed from the pricing mode and selected price. In consumption mode, it is the quantity multiplied by the selected unit price.'
            ],

            'subscription_entries_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'sale\subscription\SubscriptionEntry',
                'foreign_field'     => 'subscription_id',
                'ondetach'          => 'delete',
                'description'       => 'Subscription entries of the subscription.',
                'dependents'        => ['has_current_sale_entry']
            ],

            'has_current_sale_entry' => [
                'type'              => 'computed',
                'result_type'       => 'boolean',
                'description'       => 'The subscription has a sale entry for its current period.',
                'function'          => 'calcHasCurrentSaleEntry',
                'store'             => true,
                'instant'           => true
            ]

        ];
    }

    public function getUniques(): array {
        return array_merge(parent::getUniques(), [
            ['infra_subscription_id']
        ]);
    }

    public static function getPolicies(): array {
        return array_merge(parent::getPolicies(), [
            'period_shiftable' => [
                'description' => 'Check that the current subscription period has an approved sale entry.',
                'function'    => 'policyPeriodShiftable'
            ],
            'renewal_schedulable' => [
                'description' => 'Check that an auto-renew subscription has a period end date.',
                'function'    => 'policyRenewalSchedulable'
            ]
        ]);
    }

    public static function getActions(): array {
        return array_merge(parent::getActions(), [
            'shift_period' => [
                'description' => 'Advance the subscription to the period following its current approved sale entry.',
                'policies'    => ['period_shiftable'],
                'function'    => 'doShiftPeriod'
            ],
            'schedule_renewal' => [
                'description' => 'Refresh the automatic renewal task for the current subscription period.',
                'policies'    => ['renewal_schedulable'],
                'function'    => 'doScheduleRenewal'
            ]
        ]);
    }

    public static function onchange($event, $values): array {
        $result = parent::onchange($event, $values);

        $duration = $event['duration'] ?? $values['duration'] ?? 'yearly';
        $date_from =  $event['date_from'] ?? $values['date_from'] ?? null;
        $date_to = $result['date_to'] ?? $event['date_to'] ?? $values['date_to'] ?? null;

        $pricing_mode = $event['pricing_mode'] ?? $values['pricing_mode'] ?? 'fixed';
        $qty = $event['qty'] ?? $values['qty'] ?? 1.0;

        if( (isset($event['product_id']) || isset($event['pricing_mode']) || isset($event['qty']) || isset($event['date_from']) || isset($event['duration'])) && isset($date_from, $date_to) ) {
            $product_id = $event['product_id'] ?? $values['product_id'] ?? null;

            if($product_id) {
                $price_id = self::computePriceId(
                    $product_id,
                    $date_from,
                    $date_to
                );

                if($price_id) {
                    $price = Price::id($price_id)->read(['id', 'name', 'price'])->first();
                    if($price) {
                        $result['price_id'] = [
                            'id'    => $price['id'],
                            'name'  => $price['name']
                        ];
                        if($pricing_mode === 'fixed') {
                            $computed_price = self::computePrice($price_id, $duration);
                            $result['price'] = ($computed_price > 0) ? $computed_price : ($values['price'] ?? null);
                        }
                        else {
                            $result['price'] = isset($price['price']) ? round($qty * $price['price'], 4) : ($values['price'] ?? null);
                        }
                    }
                }
                else {
                    $result['price_id'] = null;
                    $result['price'] = $values['price'] ?? null;
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

    protected static function calcPriceId($self): array {
        $result = [];
        $self->read(['product_id', 'date_from', 'date_to']);
        foreach($self as $id => $subscription) {
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
        $self->read(['pricing_mode', 'qty', 'duration', 'price', 'price_id' => ['price', 'has_period', 'period']]);

        foreach($self as $id => $subscription) {
            if(($subscription['pricing_mode'] ?? 'fixed') === 'consumption') {
                if(isset($subscription['price_id']['price'])) {
                    $qty = $subscription['qty'] ?? 1.0;
                    $result[$id] = round($qty * $subscription['price_id']['price'], 4);
                }
                else {
                    $result[$id] = $subscription['price'] ?? null;
                }
                continue;
            }

            if(isset($subscription['price_id']['id'], $subscription['duration'])) {
                $price = self::computePrice(
                    $subscription['price_id']['id'],
                    $subscription['duration']
                );

                $result[$id] = ($price > 0) ? $price : ($subscription['price'] ?? null);
                continue;
            }

            $result[$id] = $subscription['price'] ?? null;
        }

        return $result;
    }

    protected static function calcHasCurrentSaleEntry($self): array {
        $result = [];
        $self->read([
            'date_from',
            'date_to',
            'subscription_entries_ids' => ['date_from', 'date_to']
        ]);

        foreach($self as $id => $subscription) {
            $result[$id] = false;

            if(!isset($subscription['date_from'], $subscription['date_to'])) {
                continue;
            }

            $current_period_midpoint = $subscription['date_from'] + intdiv($subscription['date_to'] - $subscription['date_from'], 2);

            foreach($subscription['subscription_entries_ids'] as $subscription_entry) {
                if(
                    isset($subscription_entry['date_from'], $subscription_entry['date_to'])
                    && $subscription_entry['date_from'] <= $current_period_midpoint
                    && $subscription_entry['date_to'] >= $current_period_midpoint
                ) {
                    $result[$id] = true;
                    break;
                }
            }
        }

        return $result;
    }

    protected static function policyPeriodShiftable($self, $values = []): array {
        $result = [];
        $self->read([
            'date_from',
            'date_to',
            'subscription_entries_ids' => ['date_from', 'date_to', 'status']
        ]);

        foreach($self as $id => $subscription) {
            if(!isset($subscription['date_from'], $subscription['date_to'])) {
                $result[$id] = [
                    'date_to' => [
                        'missing_period_dates' => 'The subscription period dates are required.'
                    ]
                ];
                continue;
            }

            $current_period_midpoint = $subscription['date_from']
                + intdiv($subscription['date_to'] - $subscription['date_from'], 2);
            $has_approved_entry = false;

            foreach($subscription['subscription_entries_ids'] as $subscription_entry) {
                if(
                    isset($subscription_entry['date_from'], $subscription_entry['date_to'])
                    && $subscription_entry['date_from'] <= $current_period_midpoint
                    && $subscription_entry['date_to'] >= $current_period_midpoint
                    && in_array($subscription_entry['status'], ['approved', 'charged'], true)
                ) {
                    $has_approved_entry = true;
                    break;
                }
            }

            if(!$has_approved_entry) {
                $result[$id] = [
                    'subscription_entries_ids' => [
                        'missing_approved_entry' => 'An approved sale entry is required for the current subscription period.'
                    ]
                ];
            }
        }

        return $result;
    }

    protected static function policyRenewalSchedulable($self): array {
        $result = [];
        $self->read(['date_to', 'is_auto_renew']);

        foreach($self as $id => $subscription) {
            if($subscription['is_auto_renew'] && !isset($subscription['date_to'])) {
                $result[$id] = [
                    'date_to' => [
                        'missing_period_end' => 'An auto-renew subscription requires a period end date.'
                    ]
                ];
            }
        }

        return $result;
    }

    protected static function doShiftPeriod($self, $values = []): void {
        $self->read(['date_to']);
        $subscription_ids = [];

        foreach($self as $id => $subscription) {
            self::id($id)->update([
                // Updating date_from also recalculates date_to from the subscription duration.
                'date_from' => strtotime('+1 day', $subscription['date_to'])
            ]);
            $subscription_ids[] = $id;
        }

        if($subscription_ids) {
            self::ids($subscription_ids)->do('schedule_renewal');
        }
    }

    protected static function doScheduleRenewal($self, $cron): void {
        $self->read(['date_to', 'is_auto_renew']);

        foreach($self as $id => $subscription) {
            $cron->cancel("subscription.{$id}.renew");
            $cron->cancel("subscription.{$id}.create.subscriptionEntry");

            if($subscription['is_auto_renew']) {
                $cron->schedule(
                    "subscription.{$id}.renew",
                    $subscription['date_to'],
                    'sale_subscription_renew',
                    ['id' => $id]
                );
            }
        }
    }

    /**
     *
     * @param  \equal\orm\ObjectManager     $om
     * @param  array                        $ids
     * @return void
     */
    public static function onupdateIsAutoRenew($om, $ids, $values, $lang) {
        self::ids($ids)->do('schedule_renewal');
    }

}
