<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2024
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

namespace sale\subscription;

use sale\SaleEntry;

class SubscriptionEntry extends SaleEntry {

    public static function getDescription() {
        return 'A subscription entry represents one period of a subscription. Like a sale entry a receivable can be generated from it.';
    }

    public static function getColumns(): array {
        return [

            /**
             * Override SaleEntry columns
             */

            'qty' => [
                'type'           => 'float',
                'description'    => 'Quantity of product.',
                'default'        => 1,
                'visible'        => ['pricing_mode', '=', 'consumption']
            ],

            'name' => [
                'type'              => 'computed',
                'result_type'       => 'string',
                'description'       => 'Short readable identifier of the entry.',
                'store'             => true,
                'instant'           => true,
                'function'          => 'calcName'
            ],

            'subscription_id' => [
                'type'           => 'many2one',
                'foreign_object' => 'sale\subscription\Subscription',
                'description'    => 'Identifier of the Subscription the sale entry originates from.',
                'dependents'     => ['name', 'pricing_mode', 'product_id', 'customer_id', 'is_billable']
            ],

            'pricing_mode' => [
                'type'           => 'computed',
                'result_type'    => 'string',
                'selection'      => [
                    'fixed'       => 'Fixed',
                    'consumption' => 'Consumption'
                ],
                'description'    => 'Pricing mode inherited from the subscription.',
                'store'          => true,
                'instant'        => true,
                'relation'       => ['subscription_id' => 'pricing_mode']
            ],

            'product_id' => [
                'type'           => 'computed',
                'result_type'    => 'many2one',
                'foreign_object' => 'sale\catalog\Product',
                'description'    => 'Product of the catalog sale.',
                'store'          => true,
                'instant'        => true,
                'relation'       => ['subscription_id' => 'product_id']
            ],

            'customer_id' => [
                'type'           => 'computed',
                'result_type'    => 'many2one',
                'foreign_object' => 'sale\customer\Customer',
                'description'    => 'The Customer to who refers the item.',
                'store'          => true,
                'instant'        => true,
                'relation'       => ['subscription_id' => 'customer_id']
            ],

            'is_billable' => [
                'type'           => 'computed',
                'result_type'    => 'boolean',
                'description'    => 'Can be billed to the customer.',
                'store'          => true,
                'instant'        => true,
                'relation'       => ['subscription_id' => 'is_billable']
            ],

            /**
             * Specific SubscriptionEntry columns
             */

            'date_from' => [
                'type'           => 'date',
                'description'    => 'Start date of the subscription period this entry covers.',
                'required'       => true,
                'dependents'     => ['name']
            ],

            'date_to' => [
                'type'           => 'date',
                'description'    => 'End date of the subscription period this entry covers.',
                'required'       => true,
                'dependents'     => ['name']
            ]

        ];
    }

    public static function getPolicies(): array {
        return array_merge(parent::getPolicies(), [
            'can_shift_subscription_period' => [
                'description' => 'Check that the sale entry belongs to the current subscription period.',
                'function'    => 'policyCanShiftSubscriptionPeriod'
            ]
        ]);
    }

    public static function getWorkflow() {
        return array_merge_recursive(parent::getWorkflow(), [
            'pending' => [
                'transitions' => [
                    'approve' => [
                        'description'   => 'Approve the Subscription Entry.',
                        'policies'      => ['can_shift_subscription_period'],
                        'onafter'       => 'onafterApprove',
                        'status'        => 'approved'
                    ]
                ]
            ],
            'submitted' => [
                'transitions' => [
                    'approve' => [
                        'description'   => 'Approve the Subscription Entry.',
                        'policies'      => ['can_approve', 'can_shift_subscription_period'],
                        'onafter'       => 'onafterApprove',
                        'status'        => 'approved'
                    ]
                ]
            ]
        ]);
    }

    protected static function policyCanShiftSubscriptionPeriod($self): array {
        $result = [];
        $self->read([
            'date_from',
            'date_to',
            'subscription_id' => ['date_from', 'date_to']
        ]);

        foreach($self as $id => $subscription_entry) {
            if(!$subscription_entry['subscription_id']) {
                $result[$id] = [
                    'subscription_id' => [
                        'missing_subscription' => 'A subscription is required to approve the sale entry.'
                    ]
                ];
                continue;
            }

            if(!isset(
                $subscription_entry['subscription_id']['date_from'],
                $subscription_entry['subscription_id']['date_to']
            )) {
                $result[$id] = [
                    'subscription_id' => [
                        'missing_period_dates' => 'The subscription period dates are required.'
                    ]
                ];
                continue;
            }

            $current_period_midpoint = $subscription_entry['subscription_id']['date_from']
                + intdiv($subscription_entry['subscription_id']['date_to'] - $subscription_entry['subscription_id']['date_from'], 2);

            if(
                !isset($subscription_entry['date_from'], $subscription_entry['date_to'])
                || $subscription_entry['date_from'] > $current_period_midpoint
                || $subscription_entry['date_to'] < $current_period_midpoint
            ) {
                $result[$id] = [
                    'date_from' => [
                        'outside_current_period' => 'The sale entry does not belong to the current subscription period.'
                    ]
                ];
            }
        }

        return $result;
    }

    protected static function onafterApprove($self): void {
        $self->read(['subscription_id' => ['date_to']]);

        $map_subscription_ids = [];
        foreach($self as $id => $subscription_entry) {
            if(!$subscription_entry['subscription_id']['id']) {
                continue;
            }
            $subscription_id = $subscription_entry['subscription_id']['id'];

            // force alignment of date_to to current subscription period end
            self::id($id)->write(['date_to' => $subscription_entry['subscription_id']['date_to']]);

            if(!isset($map_subscription_ids[$subscription_id])) {
                Subscription::id($subscription_id)->do('shift_period');
            }
            $map_subscription_ids[$subscription_id] = true;
        }

    }

    public static function calcName($self): array {
        $result = [];
        $self->read(['subscription_id' => ['name'], 'date_from', 'date_to']);
        foreach($self as $id => $entry) {
            $name_parts = [];

            if(!empty($entry['subscription_id']['name'])) {
                $name_parts[] = $entry['subscription_id']['name'];
            }

            if(isset($entry['date_from'], $entry['date_to'])) {
                $name_parts[] = sprintf(
                    '[%s - %s]',
                    date('Y-m-d', $entry['date_from']),
                    date('Y-m-d', $entry['date_to'])
                );
            }

            $result[$id] = implode(' ', $name_parts);
        }

        return $result;
    }

}
