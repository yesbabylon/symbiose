<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2026
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

namespace business\subscription;

use equal\orm\Model;

/**
 * Common lifecycle schema for subscriptions owned by domain packages.
 *
 * Concrete subscriptions remain responsible for their parties, subject,
 * pricing, entries, scheduling, and domain-specific renewal workflow.
 */
abstract class SubscriptionAbstract extends Model {

    protected const MAP_DURATION_OFFSETS = [
        'monthly'     => '+1 month',
        'quarterly'   => '+3 months',
        'half-yearly' => '+6 months',
        'yearly'      => '+1 year'
    ];

    protected const UPCOMING_EXPIRY_DELAY = '+30 days';

    public static function getDescription() {
        return 'Common lifecycle information shared by domain-specific subscriptions.';
    }

    public static function getColumns(): array {
        return [
            'name' => [
                'type'        => 'string',
                'required'    => true,
                'description' => 'Human-readable name of the subscription.'
            ],

            'description' => [
                'type'        => 'string',
                'usage'       => 'text/plain',
                'description' => 'Additional information about the subscription.'
            ],

            'date_from' => [
                'type'        => 'date',
                'required'    => true,
                'default'     => function() { return time(); },
                'onupdate'    => 'onupdateDateFrom',
                'description' => 'Start date of the current subscription period.'
            ],

            'date_to' => [
                'type'        => 'date',
                'required'    => true,
                'default'     => function() { return strtotime('+1 year'); },
                'description' => 'End date of the current subscription period.',
                'dependents'  => ['is_expired', 'has_upcoming_expiry']
            ],

            'duration' => [
                'type'        => 'string',
                'selection'   => [
                    'monthly'     => 'Monthly',
                    'quarterly'   => 'Quarterly',
                    'half-yearly' => 'Half-yearly',
                    'yearly'      => 'Yearly'
                ],
                'default'     => 'yearly',
                'description' => 'Nominal duration of a subscription period.'
            ],

            'is_auto_renew' => [
                'type'        => 'boolean',
                'default'     => false,
                'description' => 'Whether the subscription is intended to renew automatically.'
            ],

            'is_expired' => [
                'type'        => 'computed',
                'result_type' => 'boolean',
                'function'    => 'calcIsExpired',
                'store'       => true,
                'instant'     => true,
                'readonly'    => true,
                'description' => 'Whether the current subscription period has ended.'
            ],

            'has_upcoming_expiry' => [
                'type'        => 'computed',
                'result_type' => 'boolean',
                'function'    => 'calcHasUpcomingExpiry',
                'store'       => true,
                'instant'     => true,
                'readonly'    => true,
                'description' => 'Current period ends within the upcoming expiry delay.'
            ]
        ];
    }

    public static function onchange($event, $values): array {
        $result = [];

        if(isset($event['date_from']) || isset($event['duration'])) {
            $duration = $event['duration'] ?? $values['duration'] ?? 'yearly';
            $date_from = $event['date_from'] ?? $values['date_from'] ?? null;
            $duration_offset = static::MAP_DURATION_OFFSETS[$duration] ?? static::MAP_DURATION_OFFSETS['yearly'];
            $date_to = $date_from
                ? strtotime('-1 day', strtotime($duration_offset, $date_from))
                : null;

            $now = time();
            $upcoming_expiry_limit = strtotime(static::UPCOMING_EXPIRY_DELAY, $now);

            $result['date_to'] = $date_to;
            $result['is_expired'] = isset($date_to) && $date_to < $now;
            $result['has_upcoming_expiry'] = isset($date_to)
                && $date_to >= $now
                && $date_to <= $upcoming_expiry_limit;
        }

        return $result;
    }

    protected static function onupdateDateFrom($self): void {
        $self->read(['date_from', 'duration']);
        foreach($self as $id => $subscription) {
            $date_to = null;
            if(isset($subscription['date_from'])) {
                $duration = $subscription['duration'] ?? 'yearly';
                $duration_offset = static::MAP_DURATION_OFFSETS[$duration] ?? static::MAP_DURATION_OFFSETS['yearly'];
                $date_to = strtotime('-1 day', strtotime($duration_offset, $subscription['date_from']));
            }
            static::id($id)->update(['date_to' => $date_to]);
        }
    }

    protected static function calcIsExpired($self): array {
        $result = [];
        $now = time();

        $self->read(['date_to']);
        foreach($self as $id => $subscription) {
            $result[$id] = isset($subscription['date_to'])
                && $subscription['date_to'] < $now;
        }

        return $result;
    }

    protected static function calcHasUpcomingExpiry($self): array {
        $result = [];
        $now = time();
        $upcoming_expiry_limit = strtotime(static::UPCOMING_EXPIRY_DELAY, $now);

        $self->read(['date_to']);
        foreach($self as $id => $subscription) {
            $result[$id] = isset($subscription['date_to'])
                && $subscription['date_to'] >= $now
                && $subscription['date_to'] <= $upcoming_expiry_limit;
        }

        return $result;
    }
}
