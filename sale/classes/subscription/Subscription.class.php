<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2024
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

namespace sale\subscription;

use equal\orm\Model;
use infra\service\Subscription as InfraSubscription;
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
                'description'       => 'Name of the subscription.',
                'dependents'        => ['subscription_entries_ids' => ['name']]
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

            'inventory_subscription_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'infra\service\Subscription',
                'description'       => 'Inventory subscription created from this sale subscription.'
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

    public static function getPolicies(): array {
        return array_merge(parent::getPolicies(), [
            'period_shiftable' => [
                'description' => 'Check that the current subscription period has an approved sale entry.',
                'function'    => 'policyPeriodShiftable'
            ],
            'renewal_schedulable' => [
                'description' => 'Check that an auto-renew subscription has a period end date.',
                'function'    => 'policyRenewalSchedulable'
            ],
            'can_create_inventory_subscription' => [
                'description' => 'Check that a corresponding inventory subscription can be created.',
                'function'    => 'policyCanCreateInventorySubscription'
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
            ],
            'create_inventory_subscription' => [
                'description' => 'Create a corresponding inventory subscription when none exists yet.',
                'policies'    => ['can_create_inventory_subscription'],
                'function'    => 'doCreateInventorySubscription'
            ]
        ]);
    }

    protected static function onupdateDateFrom($self) {
        $self->read(['date_from', 'duration']);
        foreach($self as $id => $subscription) {
            $date_to = null;
            if($subscription['date_from']) {
                $date_to = strtotime('-1 day', strtotime(self::MAP_DURATION_OFFSETS[$subscription['duration']], $subscription['date_from']));
            }
            self::id($id)->update(['date_to' => $date_to]);
        }
    }

    public static function onchange($event, $values): array {
        $result = [];

        $duration = $event['duration'] ?? $values['duration'] ?? 'yearly';
        $date_from =  $event['date_from'] ?? $values['date_from'] ?? null;
        $date_to = $date_from
            ? strtotime('-1 day', strtotime(self::MAP_DURATION_OFFSETS[$duration], $date_from))
            : null;

        if( isset($event['date_from']) || isset($event['duration']) ) {
            $now = time();

            $days_until_expiry = ($date_to - $now) / 86400;

            $result['date_to'] = $date_to;
            $result['is_expired'] = $now > $date_to;
            $result['has_upcoming_expiry'] = $days_until_expiry < 30;
        }

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

    protected static function calcIsExpired($self): array {
        $result = [];
        $self->read(['date_to']);
        foreach($self as $id => $subscription) {
            if(isset($subscription['date_to'])) {
                $result[$id] = (time() > $subscription['date_to']);
            }
        }

        return $result;
    }

    protected static function calcUpcomingExpiry($self): array {
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

    protected static function policyCanCreateInventorySubscription($self): array {
        $result = [];
        $self->read(['model', 'name', 'date_from', 'date_to', 'inventory_subscription_id']);

        foreach($self as $id => $subscription) {
            if($subscription['model'] !== self::class) {
                $result[$id] = [
                    'model' => [
                        'unsupported_subscription_model' => 'Only sale subscriptions can create a corresponding inventory subscription.'
                    ]
                ];
                continue;
            }

            if(isset($subscription['inventory_subscription_id'])) {
                continue;
            }

            if(isset($subscription['name'])) {
                $inventory_subscription = InfraSubscription::search([
                    ['name', '=', $subscription['name']],
                    ['state', 'in', ['draft', 'instance']]
                ]);
                if($inventory_subscription->count() > 0) {
                    continue;
                }
            }

            foreach(['name', 'date_from', 'date_to'] as $field) {
                if(!isset($subscription[$field])) {
                    $result[$id][$field] = [
                        'missing_inventory_subscription_value' => 'This value is required to create the corresponding inventory subscription.'
                    ];
                }
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

    protected static function doCreateInventorySubscription($self): void {
        $self->read([
            'name',
            'description',
            'date_from',
            'date_to',
            'duration',
            'pricing_mode',
            'qty',
            'is_auto_renew',
            'ref_order',
            'license_key',
            'customer_id',
            'product_id',
            'inventory_subscription_id'
        ]);

        foreach($self as $id => $subscription) {
            if(isset($subscription['inventory_subscription_id'])) {
                continue;
            }

            $inventorySubscription = InfraSubscription::search([
                    ['product_id', '=', $subscription['product_id']],
                    ['customer_id', '=', $subscription['customer_id']]
                ])
                ->read(['id'])
                ->first();

            if(!$inventorySubscription) {
                $inventorySubscription = InfraSubscription::create([
                        'name'                   => $subscription['name'],
                        'description'            => $subscription['description'] ?? null,
                        'date_from'              => $subscription['date_from'],
                        'date_to'                => $subscription['date_to'],
                        'duration'               => $subscription['duration'] ?? 'yearly',
                        'pricing_mode'           => $subscription['pricing_mode'] ?? 'fixed',
                        'qty'                    => $subscription['qty'] ?? 1.0,
                        'is_auto_renew'          => $subscription['is_auto_renew'] ?? false,
                        'ref_order'              => $subscription['ref_order'] ?? null,
                        'license_key'            => $subscription['license_key'] ?? null,
                        'product_id'             => $subscription['product_id'] ?? null,
                        'customer_id'            => $subscription['customer_id'] ?? null
                    ])
                    ->first();
            }

            self::id($id)->update(['inventory_subscription_id' => $inventorySubscription['id']]);
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
