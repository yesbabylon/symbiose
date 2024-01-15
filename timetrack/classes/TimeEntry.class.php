<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

namespace timetrack;

use sale\SaleEntry;

class TimeEntry extends SaleEntry {

    const ORIGIN_BACKLOG = 1;
    const ORIGIN_EMAIL = 2;
    const ORIGIN_SUPPORT = 3;

    const ORIGIN_MAP = [
        self::ORIGIN_BACKLOG => 'Backlog',
        self::ORIGIN_EMAIL   => 'E-mail',
        self::ORIGIN_SUPPORT => 'Support ticket',
    ];

    public static function getColumns(): array {
        return [

            /**
             * Override SaleEntry columns
             */

            'project_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'timetrack\Project',
                'description'       => 'Identifier of the Project the sale entry originates from.',
                'dependencies'      => ['ticket_link'],
                'onupdate'          => 'onupdateProjectId'
            ],

            'customer_id' => [
                'type'           => 'computed',
                'result_type'    => 'many2one',
                'foreign_object' => 'sale\customer\Customer',
                'description'    => 'Customer this time entry was created for.',
                'function'       => 'calcCustomerId',
                'store'          => true,
                'readonly'       => true
            ],

            'object_class' => [
                'type'           => 'string',
                'description'    => 'Class of the object object_id points to.',
                'default'        => 'timetrack\Project',
                'dependencies'   => ['project_id']
            ],

            'product_id' => [
                'type'           => 'computed',
                'result_type'    => 'many2one',
                'foreign_object' => 'sale\catalog\Product',
                'description'    => 'Product of the catalog sale.',
                'function'       => 'calcProductId',
                'store'          => true
            ],
            
            'price_id' => [
                'type'           => 'computed',
                'result_type'    => 'many2one',
                'foreign_object' => 'sale\price\Price',
                'description'    => 'Price of the sale.',
                'function'       => 'calcPriceId',
                'store'          => true,
                'dependencies'   => ['unit_price']
            ],

            'unit_price' => [
                'type'              => 'computed',
                'result_type'       => 'float',
                'usage'             => 'amount/money:4',
                'description'       => 'Unit price of the product related to the entry.',
                'function'          => 'calcUnitPrice',
                'store'             => true
            ],

            'qty' => [
                'type'           => 'computed',
                'result_type'    => 'float',
                'description'    => 'Quantity of product.',
                'visible'        => false,
                'store'          => true,
                'function'       => 'calcQty'
            ],

            /**
             * Specific TimeEntry columns
             */

            'name' => [
                'type'           => 'string',
                'description'    => 'Name of the time entry.',
                'required'       => true
            ],

            'description' => [
                'type'           => 'string',
                'description'    => 'Description of the time entry.'
            ],

            'time_start' => [
                'type'           => 'datetime',
                'description'    => 'Start date time of the entry.',
                'default'        => time(),
                'dependencies'   => ['duration']
            ],

            'time_end' => [
                'type'           => 'datetime',
                'description'    => 'End date time of the entry.',
                'default'        => strtotime('+1 hour'),
                'dependencies'   => ['duration']
            ],

            'duration' => [
                'type'           => 'computed',
                'result_type'    => 'string',
                'store'          => true,
                'instant'        => true,
                'function'       => 'calcDuration',
                'onupdate'       => 'onupdateDuration',
                'dependencies'   => ['qty']
            ],

            'user_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'core\User',
                'description'       => 'User the time entry was realised by.'
            ],

            'origin' => [
                'type'           => 'integer',
                'selection'      => self::ORIGIN_MAP,
                'description'    => 'Origin of the this time entry creation.',
                'default'        => self::ORIGIN_EMAIL
            ],

            'ticket_id' => [
                'type'           => 'integer',
                'description'    => 'Support ticket id from project Symbiose instance.',
                'dependencies'   => ['ticket_link'],
                'visible'        => ['origin', '=', self::ORIGIN_SUPPORT]
            ],

            'ticket_link' => [
                'type'           => 'computed',
                'result_type'    => 'string',
                'usage'          => 'uri/url',
                'function'       => 'calcTicketLink',
                'store'          => true,
                'visible'        => ['origin', '=', self::ORIGIN_SUPPORT]
            ]

        ];
    }

    public static function onchange($event, $values): array {
        $result = [];

        if(
            (isset($event['project_id']) && isset($values['origin']))
            || (isset($event['origin']) && isset($values['project_id']))
        ) {
            $sale_model = TimeEntrySaleModel::getModelToApply(
                $event['origin'] ?? $values['origin'],
                $event['project_id'] ?? $values['project_id']
            );

            if(!is_null($sale_model)) {
                $result = [
                    'product_id' => $sale_model['product_id'],
                    'price_id'   => $sale_model['price_id'],
                    'unit_price' => $sale_model['unit_price']
                ];
            }
        }

        if(isset($event['project_id'])) {
            $project = Project::id($event['project_id'])
                ->read(['customer_id' => ['name']])
                ->first();

            $result['customer_id'] = $project['customer_id'];
        }

        return $result;
    }

    public static function onupdateProjectId($self): void {
        $self->read(['object_id', 'project_id']);
        foreach($self as $id => $time_entry) {
            if ($time_entry['object_id'] === $time_entry['project_id']) {
                continue;
            }

            TimeEntry::id($id)
                ->update(['object_id' => $time_entry['project_id']]);
        }
    }

    public static function calcProductId($self): array {
        $result = [];
        $self->read(['project_id', 'origin']);
        foreach($self as $id => $time_entry) {
            if(!isset($time_entry['origin'], $time_entry['project_id'])) {
                continue;
            }

            $sale_model = TimeEntrySaleModel::getModelToApply(
                $time_entry['origin'],
                $time_entry['project_id']
            );
            if(!isset($sale_model['product_id']['id'])) {
                continue;
            }

            $result[$id] = $sale_model['product_id']['id'];
        }

        return $result;
    }

    public static function calcPriceId($self): array {
        $result = [];
        $self->read(['project_id', 'origin']);
        foreach($self as $id => $time_entry) {
            if(!isset($time_entry['origin'], $time_entry['project_id'])) {
                continue;
            }

            $sale_model = TimeEntrySaleModel::getModelToApply(
                $time_entry['origin'],
                $time_entry['project_id']
            );
            if(!isset($sale_model['price_id']['id'])) {
                continue;
            }

            $result[$id] = $sale_model['price_id']['id'];
        }

        return $result;
    }

    public static function calcUnitPrice($self) {
        $result = [];
        $self->read(['project_id', 'origin', 'price_id' => ['price']]);
        foreach($self as $id => $time_entry) {
            if(!isset($time_entry['origin'], $time_entry['project_id'])) {
                continue;
            }

            $sale_model = TimeEntrySaleModel::getModelToApply(
                $time_entry['origin'],
                $time_entry['project_id']
            );

            if(isset($sale_model['unit_price'])) {
                $result[$id] = $sale_model['unit_price'];
            }
            elseif(isset($time_entry['price_id']['price'])) {
                $result[$id] = $time_entry['price_id']['price'];
            }
        }

        return $result;
    }

    public static function calcDuration($self): array {
        $result = [];
        $self->read(['time_start', 'time_end']);
        foreach($self as $id => $time_entry) {
            $seconds = $time_entry['time_end'] - $time_entry['time_start'];
            $result[$id] = sprintf('%02d:%02d', ($seconds/3600), ($seconds/60%60));
        }

        return $result;
    }

    public static function onupdateDuration($self): void {
        $self->read(['time_start', 'time_end', 'duration']);
        foreach($self as $id => $time_entry) {
            $hh_mm_pattern = '/^([0-1]?[0-9]|2[0-3]):[0-5][0-9]$/';
            if(
                is_null($time_entry['duration'])
                || !preg_match($hh_mm_pattern, $time_entry['duration'])
            ) {
                continue;
            }

            $parsed_time = date_parse($time_entry['duration'].':00');
            $duration = $parsed_time['hour'] * 3600 + $parsed_time['minute'] * 60;
            if($duration === ($time_entry['time_end'] - $time_entry['time_start'])) {
                continue;
            }

            $new_time_end = $time_entry['time_start'] + $duration;

            TimeEntry::id($id)
                ->update(['time_end' => $new_time_end]);
        }
    }

    public static function calcCustomerId($self): array {
        $result = [];
        $self->read(['project_id' => ['customer_id']]);
        foreach($self as $id => $time_entry) {
            if (!isset($time_entry['project_id']['customer_id'])) {
                continue;
            }

            $result[$id] = $time_entry['project_id']['customer_id'];
        }

        return $result;
    }

    public static function calcTicketLink($self): array {
        $result = [];
        $self->read(['origin', 'ticket_id', 'project_id' => ['instance_id' => ['url']]]);
        foreach($self as $id => $time_entry) {
            if(
                $time_entry['origin'] !== self::ORIGIN_SUPPORT
                || is_null($time_entry['ticket_id'])
                || empty($time_entry['project_id']['instance_id']['url'])
            ) {
                continue;
            }

            $instance_url = $time_entry['project_id']['instance_id']['url'];
            if(substr($instance_url, -1) !== '/') {
                $instance_url .= '/';
            }

            $result[$id] = $instance_url.'support/#/ticket/'.$time_entry['ticket_id'];
        }

        return $result;
    }

    public static function calcQty($self): array {
        $result = [];
        $self->read(['duration']);
        foreach($self as $id => $time_entry) {
            if (is_null($time_entry['duration'])) {
                continue;
            }

            $parsed_time = date_parse($time_entry['duration'].':00');
            $result[$id] = $parsed_time['hour'] + $parsed_time['minute'] / 60;
        }

        return $result;
    }
}
