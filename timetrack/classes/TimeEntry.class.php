<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

namespace timetrack;

use DateTime;
use DateTimeZone;
use Exception;
use sale\SaleEntry;
use sale\catalog\Product;
use sale\price\Price;
use core\setting\Setting;

class TimeEntry extends SaleEntry {

    const ORIGIN_BACKLOG = 'backlog';
    const ORIGIN_EMAIL = 'email';
    const ORIGIN_SUPPORT = 'support';

    const ORIGIN_MAP = [
        self::ORIGIN_BACKLOG => 'Backlog',
        self::ORIGIN_EMAIL   => 'E-mail',
        self::ORIGIN_SUPPORT => 'Support ticket',
    ];

    const STATUS_PENDING = 'pending';
    const STATUS_READY = 'ready';
    const STATUS_VALIDATED = 'validated';
    const STATUS_BILLED = 'billed';

    const STATUS_MAP = [
        self::STATUS_PENDING   => 'Pending',
        self::STATUS_READY     => 'Ready for validation',
        self::STATUS_VALIDATED => 'Validated',
        self::STATUS_BILLED    => 'Billed',
    ];

    const TRANSITION_REQUEST_VALIDATION = 'request-validation';
    const TRANSITION_REFUSE = 'refuse';
    const TRANSITION_VALIDATE = 'validate';
    const TRANSITION_BILL = 'bill';

    const POLICY_READY_FOR_VALIDATION = 'ready-for-validation';
    const POLICY_BILLABLE = 'billable';

    public static function getName(): string {
        return 'Time entry';
    }

    public static function getDescription(): string {
        return 'A time entry records a duration of time an employee spent on a task related to a customer\'s project.';
    }

    public static function getColumns(): array {
        $current_hour = self::getTimeZoneCurrentHour();

        return [

            /**
             * Override SaleEntry columns
             */

            'project_id' => [
                'type'           => 'many2one',
                'foreign_object' => 'timetrack\Project',
                'description'    => 'Identifier of the Project the sale entry originates from.',
                'dependencies'   => ['ticket_link'],
                'onupdate'       => 'onupdateProjectId'
            ],

            'customer_id' => [
                'type'           => 'computed',
                'result_type'    => 'many2one',
                'foreign_object' => 'sale\customer\Customer',
                'description'    => 'Customer this time entry was created for.',
                'function'       => 'calcCustomerId',
                'store'          => true,
                'instant'        => true,
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
                'type'           => 'computed',
                'result_type'    => 'float',
                'usage'          => 'amount/money:4',
                'description'    => 'Unit price of the product related to the entry.',
                'function'       => 'calcUnitPrice',
                'store'          => true
            ],

            /**
             * Specific TimeEntry columns
             */

            'date'       => [
                'type'           => 'date',
                'description'    => 'Date of the entry',
                'default'        => time(),
            ],

            'time_start' => [
                'type'           => 'time',
                'description'    => 'Start time of the entry.',
                'default'        => $current_hour * 3600,
                'dependencies'   => ['duration']
            ],

            'time_end' => [
                'type'           => 'time',
                'description'    => 'End time of the entry.',
                'default'        => ($current_hour + 1) * 3600,
                'dependencies'   => ['duration']
            ],

            'duration' => [
                'type'           => 'computed',
                'result_type'    => 'time',
                'description'    => 'Duration of the entry.',
                'function'       => 'calcDuration',
                'store'          => true,
                'instant'        => true,
                'onupdate'       => 'onupdateDuration'
            ],

            'user_id' => [
                'type'           => 'many2one',
                'foreign_object' => 'core\User',
                'description'    => 'User the time entry was realised by.'
            ],

            'origin' => [
                'type'           => 'string',
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
                'description'    => 'Support ticket link for quick access.',
                'usage'          => 'uri/url',
                'function'       => 'calcTicketLink',
                'store'          => true,
                'visible'        => ['origin', '=', self::ORIGIN_SUPPORT]
            ],

            'status' => [
                'type'           => 'string',
                'selection'      => array_keys(self::STATUS_MAP),
                'description'    => 'Status of the time entry',
                'default'        => self::STATUS_PENDING
            ]

        ];
    }

    private static function getTimeZoneCurrentHour(): int {
        $time_zone = Setting::get_value('core', 'locale', 'time_zone');

        $current_hour = (int) date('H');
        if(!is_null($time_zone)) {
            try {
                $timezone = new DateTimeZone($time_zone);
                $dateTime = new DateTime('now', $timezone);

                $current_hour = (int) $dateTime->format('H');
            }
            catch(Exception $e) {
                trigger_error('PHP::error getting time zone current hour', QN_REPORT_DEBUG);
            }
        }

        return $current_hour;
    }

    public static function onchange($event, $values): array {
        $result = [];

        if(
            isset($event['project_id'], $values['origin'])
            || isset($event['origin'], $values['project_id'])
        ) {
            $sale_model = TimeEntrySaleModel::getModelToApply(
                $event['origin'] ?? $values['origin'],
                $event['project_id'] ?? $values['project_id']
            );

            if(!is_null($sale_model)) {
                $product = null;
                if(!is_null($sale_model['product_id'])) {
                    $product = Product::id($sale_model['product_id'])
                        ->read(['id', 'name'])
                        ->first();
                }

                $price = null;
                if(!is_null($sale_model['price_id'])) {
                    $price = Price::id($sale_model['price_id'])
                        ->read(['id', 'name'])
                        ->first();
                }

                $result = [
                    'product_id'  => $product,
                    'price_id'    => $price,
                    'unit_price'  => $sale_model['unit_price'],
                    'is_billable' => true
                ];
            }
        }

        if(isset($event['project_id'])) {
            $project = Project::id($event['project_id'])
                ->read(['customer_id' => ['name']])
                ->first();

            $result['customer_id'] = $project['customer_id'];
        }

        if(
            isset($event['time_start'], $values['time_end'])
            || isset($event['time_end'], $values['time_start'])
        ) {
            $time_start = $event['time_start'] ?? $values['time_start'];
            $time_end = $event['time_end'] ?? $values['time_end'];

            $result['duration'] = $time_end - $time_start;
        }
        elseif(isset($event['duration'], $values['time_start'])) {
            $result['time_end'] = $values['time_start'] + $event['duration'];
        }

        return $result;
    }

    public static function onupdateProjectId($self): void {
        $self->read(['object_id', 'project_id']);
        foreach($self as $id => $time_entry) {
            if($time_entry['object_id'] === $time_entry['project_id']) {
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
            if(is_null($sale_model['product_id'])) {
                continue;
            }

            $result[$id] = $sale_model['product_id'];
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
            if(is_null($sale_model['price_id'])) {
                continue;
            }

            $result[$id] = $sale_model['price_id'];
        }

        return $result;
    }

    public static function calcUnitPrice($self): array {
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
            if(!isset($time_entry['time_start'], $time_entry['time_end'])) {
                continue;
            }

            $result[$id] = $time_entry['time_end'] - $time_entry['time_start'];
        }

        return $result;
    }

    public static function onupdateDuration($self): void {
        $self->read(['time_start', 'time_end', 'duration', 'qty']);
        foreach($self as $id => $time_entry) {
            $updates = ['qty' => 0];

            if(!isset($time_entry['duration'])) {
                $updates['qty'] = $time_entry['duration'] / 3600;
            }

            if(
                isset($time_entry['duration'], $time_entry['time_start'], $time_entry['time_end'])
                && $time_entry['duration'] !== ($time_entry['time_end'] - $time_entry['time_start'])
            ) {
                $updates['time_end'] = $time_entry['time_start'] + $time_entry['duration'];
            }

            TimeEntry::id($id)->update($updates);
        }
    }

    public static function calcCustomerId($self): array {
        $result = [];
        $self->read(['project_id' => ['customer_id']]);
        foreach($self as $id => $time_entry) {
            if(!isset($time_entry['project_id']['customer_id'])) {
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

    public static function getPolicies(): array {
        return [
            self::POLICY_READY_FOR_VALIDATION => [
                'description' => 'Verifies that time entry is ready for validation.',
                'function'    => 'isReadyForValidation'
            ],
            self::POLICY_BILLABLE => [
                'description' => 'Verifies that time entry is billable.',
                'function'    => 'isBillable'
            ]
        ];
    }

    public static function isReadyForValidation($self, $user_id): array {
        $result = [];
        $self->read(['project_id', 'user_id', 'origin', 'duration']);
        foreach($self as $id => $time_entry) {
            if(
                !isset($time_entry['project_id'], $time_entry['user_id'], $time_entry['origin'], $time_entry['duration'])
                || $time_entry['duration'] <= 0
            ) {
                $result[$id] = false;
            }
        }

        return $result;
    }

    public static function isBillable($self, $user_id): array {
        $result = [];
        $self->read(['product_id', 'price_id', 'unit_price', 'is_billable']);
        foreach($self as $id => $time_entry) {
            if(
                !isset($time_entry['product_id'], $time_entry['price_id'], $time_entry['unit_price'])
                || !$time_entry['is_billable']
            ) {
                $result[$id] = false;
            }
        }

        return $result;
    }

    public static function getWorkflow(): array {
        return [
            self::STATUS_PENDING   => [
                'transitions' => [
                    self::TRANSITION_REQUEST_VALIDATION => [
                        'description' => 'Sets time entry as ready for validation.',
                        'status'      => self::STATUS_READY,
                        'policies'    => [self::POLICY_READY_FOR_VALIDATION]
                    ]
                ]
            ],
            self::STATUS_READY     => [
                'transitions' => [
                    self::TRANSITION_REFUSE   => [
                        'description' => 'Refuse time entry, sets its status back to pending.',
                        'status'      => self::STATUS_PENDING
                    ],
                    self::TRANSITION_VALIDATE => [
                        'description' => 'Validate time entry.',
                        'status'      => self::STATUS_VALIDATED
                    ]
                ]
            ],
            self::STATUS_VALIDATED => [
                'transitions' => [
                    self::TRANSITION_BILL   => [
                        'description' => 'Create receivable, from time entry, who will be billed to the customer.',
                        'status'      => self::STATUS_BILLED,
                        'policies'    => [self::POLICY_BILLABLE]
                    ]
                ]
            ],
        ];
    }
}
