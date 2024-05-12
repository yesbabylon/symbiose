<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2024
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace timetrack;

use DateTime;
use DateTimeZone;
use sale\SaleEntry;
use sale\catalog\Product;
use sale\price\Price;
use core\setting\Setting;
use eQual;
use Exception;

class TimeEntry extends SaleEntry {

    public static function getName(): string {
        return 'Time entry';
    }

    public static function getDescription(): string {
        return 'Time entries are used to log the tasks performed by employees on customers projects, and the duration spent on it.';
    }

    public static function getColumns(): array {
        $current_hour = self::getTimeZoneCurrentHour();

        return [

            'name' => [
                'type'              => 'computed',
                'result_type'       => 'string',
                'description'       => 'Short readable identifier of the entry.',
                'store'             => true,
                'function'          => 'calcName'
            ],

            'description' => [
                'type'              => 'string',
                'description'       => 'Short description of the task performed.',
                'dependents'        => ['name']
            ],

            'project_id' => [
                'type'           => 'many2one',
                'foreign_object' => 'timetrack\Project',
                'description'    => 'Identifier of the Project the sale entry originates from.',
                'dependents'     => ['name', 'ticket_link', 'product_id', 'price_id', 'unit_price'],
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
                'dependents'     => ['project_id']
            ],

            'product_id' => [
                'type'           => 'computed',
                'result_type'    => 'many2one',
                'foreign_object' => 'sale\catalog\Product',
                'description'    => 'Product of the sale catalog.',
                'help'           => 'This field references a Product from the catalog. This field is not to be mistaken with the Product (software) of the customer.',
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
                'dependents'     => ['unit_price']
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
                'default'        => function() { return time(); },
            ],

            'time_start' => [
                'type'           => 'time',
                'description'    => 'Start time of the entry.',
                'default'        => $current_hour * 3600,
                'dependents'     => ['duration']
            ],

            'time_end' => [
                'type'           => 'time',
                'description'    => 'End time of the entry.',
                'default'        => ($current_hour + 1) * 3600,
                'dependents'     => ['duration']
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
                'description'    => 'User the time entry was performed by.',
                'default'        => 'defaultUserId'
            ],

            'origin' => [
                'type'           => 'string',
                'selection'      => [
                    'project',
                    'backlog',
                    'email',
                    'support'
                ],
                'dependents'     => ['name'],
                'description'    => 'Origin of the time entry: what the task performed is a response to.',
                'help'           => "Project: refers to a Project Management task.\n
                                     Backlog: refers to one (or more) entry from the backlog associated with the project.\n
                                     E-mail: refers to a specific email conversation\n
                                     Support: refers to a specific support ticket.",
                'default'        => 'project'
            ],

            'ticket_id' => [
                'type'           => 'integer',
                'description'    => 'Identifier of the support ticket (number).',
                'dependents'     => ['name', 'ticket_link'],
                'onupdate'       => 'onupdateTicketId',
                'visible'        => ['origin', '=', 'support']
            ],

            'ticket_link' => [
                'type'           => 'computed',
                'result_type'    => 'string',
                'description'    => 'Support ticket link for quick access.',
                'usage'          => 'uri/url',
                'function'       => 'calcTicketLink',
                'store'          => true,
                'visible'        => ['origin', '=', 'support']
            ],

            'reference' => [
                'type'           => 'string',
                'dependents'     => ['name'],
                'description'    => 'Email or backlog reference.',
                'visible'        => ['origin', 'in', ['backlog', 'email']]
            ],

            'status' => [
                'type'           => 'string',
                'selection'      => [
                    'pending'   => 'Draft',
                    'ready'     => 'Ready',
                    'validated' => 'Validated',
                    'billed'    => 'Billed'
                ],
                'description'    => 'Status of the time entry',
                'default'        => 'pending'
            ]

        ];
    }

    private static function getTimeZoneCurrentHour(): int {
        $current_hour = (int) date('H');

        $time_zone = Setting::get_value('core', 'locale', 'time_zone');
        if(!is_null($time_zone)) {
            try {
                $timezone = new DateTimeZone($time_zone);
                $dateTime = new DateTime('now', $timezone);
                $current_hour = (int) $dateTime->format('H');
            }
            catch(Exception $e) {
                trigger_error('PHP::error getting time zone current hour', EQ_REPORT_WARNING);
            }
        }

        return $current_hour;
    }

    public static function generateTicketLink($instance_url, $ticket_id): string {
        $url = $instance_url ?? '';
        if(substr($url, -1) !== '/') {
            $url .= '/';
        }
        return $url.'support/#/ticket/'.$ticket_id;
    }

    public static function defaultUserId($auth) {
        return $auth->userId();
    }

    public static function canupdate($om, $oids, $values, $lang = 'en'): array {
        $res = $om->read(self::class, $oids, ['status']);

        foreach($res as $odata) {
            if(in_array($odata['status'], ['pending', 'ready'])) {
                continue;
            }

            $editable_fields = ['description', 'detailed_description', 'status'];
            $sale_fields = ['product_id', 'price_id', 'unit_price', 'is_billable'];
            if($odata['status'] === 'validated') {
                $editable_fields = array_merge($editable_fields, $sale_fields);
            }

            foreach($values as $field => $value) {
                if(!in_array($field, $editable_fields)) {
                    return [
                        $field => [
                            'non_editable' => sprintf(
                                'Time entry %s can only be updated from %s to %s.',
                                $field,
                                'pending',
                                !in_array($field, $sale_fields) ? 'ready' : 'validated'
                            )
                        ]
                    ];
                }
            }
        }

        return parent::canupdate($om, $oids, $values, $lang);
    }

    public static function onchange($event, $values): array {
        $result = [];

        if(isset($event['origin'])) {
            if(!in_array($event['origin'], ['backlog', 'email'])) {
                $result['reference'] = null;
            }
            if($event['origin'] != 'support') {
                $result['ticket_id'] = null;
                $result['ticket_link'] = null;
            }
        }

        if(isset($event['ticket_id'])) {
            $entry = self::id($values['id'])->read(['project_id' => ['instance_id' => ['url']]])->first();
            $result['ticket_link'] = self::generateTicketLink($entry['project_id']['instance_id']['url'], $event['ticket_id']);
        }

        if(isset($event['project_id'])) {
            $project = Project::id($event['project_id'])
                ->read(['customer_id' => ['name']])
                ->first();

            $result['customer_id'] = $project['customer_id'];
        }

        if( isset($event['time_start'], $values['time_end'])
            || isset($event['time_end'], $values['time_start']) ) {
            $time_start = $event['time_start'] ?? $values['time_start'];
            $time_end = $event['time_end'] ?? $values['time_end'];

            if($time_end < $time_start) {
                $result['time_end'] = $time_start + ($values['duration'] ?? 0);
            }
            else {
                $result['duration'] = $time_end - $time_start;
            }
        }
        elseif(isset($event['duration'], $values['time_start'])) {
            $result['time_end'] = $values['time_start'] + $event['duration'];
        }

        return $result;
    }

    public static function onupdateProjectId($self): void {
        $self->read(['object_id', 'object_class', 'project_id']);
        foreach($self as $id => $entry) {
            if($entry['object_id'] != $entry['project_id'] || $entry['object_id'] != Project::getType()) {
                self::id($id)->update([
                        'object_id'     => $entry['project_id'],
                        'object_class'  => Project::getType()
                    ]);
            }
        }
    }

    public static function onupdateTicketId($self) : void {
        $self->read(['ticket_id']);
        foreach($self as $id => $entry) {
            self::id($id)->update(['reference' => 'ticket '.$entry['ticket_id']]);
        }
    }

    public static function calcName($self) {
        $result = [];
        $self->read(['project_id' => ['name'], 'origin', 'reference', 'description']);
        foreach($self as $id => $entry) {
            $result[$id] = $entry['project_id']['name'];
            $result[$id] .= ' - '.ucfirst($entry['origin']).' ['.$entry['reference'].']';
            if(isset($entry['description']) && strlen($entry['description']) > 0) {
                $result[$id] .= ' - '.$entry['description'];
            }
        }
        return $result;
    }

    public static function calcProductId($self): array {
        $result = [];
        $self->read(['project_id' => ['time_entry_sale_model_id' => 'product_id']]);
        foreach($self as $id => $entry) {
            $result[$id] = $entry['project_id']['time_entry_sale_model_id']['product_id'] ?? null;
        }
        return $result;
    }

    public static function calcPriceId($self): array {
        $result = [];
        $self->read(['project_id' => ['time_entry_sale_model_id' => 'price_id']]);
        foreach($self as $id => $entry) {
            $result[$id] = $entry['project_id']['time_entry_sale_model_id']['price_id'] ?? null;
        }
        return $result;
    }

    public static function calcUnitPrice($self): array {
        $result = [];
        $self->read(['project_id' => ['time_entry_sale_model_id' => 'unit_price']]);
        foreach($self as $id => $entry) {
            $result[$id] = $entry['project_id']['time_entry_sale_model_id']['unit_price'] ?? null;
        }
        return $result;
    }

    public static function calcDuration($self): array {
        $result = [];
        $self->read(['time_start', 'time_end']);
        foreach($self as $id => $entry) {
            if(!isset($entry['time_start'], $entry['time_end'])) {
                continue;
            }

            $result[$id] = $entry['time_end'] - $entry['time_start'];
        }

        return $result;
    }

    public static function onupdateDuration($self): void {
        $self->read(['time_start', 'time_end', 'duration', 'qty']);
        foreach($self as $id => $entry) {
            $updates = ['qty' => 0];

            if(isset($entry['duration'])) {
                $updates['qty'] = $entry['duration'] / 3600;
            }

            if(isset($entry['duration'], $entry['time_start'], $entry['time_end'])
                && $entry['duration'] !== ($entry['time_end'] - $entry['time_start'])) {
                $updates['time_end'] = $entry['time_start'] + $entry['duration'];
            }

            TimeEntry::id($id)->update($updates);
        }
    }

    public static function calcCustomerId($self): array {
        $result = [];
        $self->read(['project_id' => ['customer_id']]);
        foreach($self as $id => $entry) {
            if(!isset($entry['project_id']['customer_id'])) {
                continue;
            }

            $result[$id] = $entry['project_id']['customer_id'];
        }

        return $result;
    }

    public static function calcTicketLink($self): array {
        $result = [];
        $self->read(['origin', 'ticket_id', 'project_id' => ['instance_id' => ['url']]]);
        foreach($self as $id => $entry) {
            if($entry['origin'] == 'support') {
                $result[$id] = self::generateTicketLink($entry['project_id']['instance_id']['url'], $entry['ticket_id']);
            }
        }
        return $result;
    }

    public static function getPolicies(): array {
        return [
            'ready-for-validation' => [
                'description' => 'Verifies that time entry is ready for validation.',
                'function'    => 'isReadyForValidation'
            ],
            'billable' => [
                'description' => 'Verifies that time entry holds all information required for invoicing.',
                'function'    => 'isBillable'
            ]
        ];
    }

    public static function isReadyForValidation($self, $user_id): array {
        $result = [];
        $self->read(['project_id', 'user_id', 'origin', 'duration']);
        foreach($self as $id => $entry) {
            if(!isset($entry['project_id'], $entry['user_id'], $entry['origin'], $entry['duration'])
                || $entry['duration'] <= 0) {
                $result[$id] = false;
            }
        }

        return $result;
    }

    public static function isBillable($self, $user_id): array {
        $result = [];
        $self->read(['product_id', 'price_id', 'unit_price', 'is_billable']);
        foreach($self as $id => $entry) {
            if(!isset($entry['product_id'], $entry['price_id'], $entry['unit_price'])
                || !$entry['is_billable']) {
                $result[$id] = false;
            }
        }

        return $result;
    }

    public static function addReceivable($self): void {
        $self->read(['id']);
        foreach($self as $entry) {
            try {
                eQual::run('do', 'sale_saleentry_add-receivable', ['id' => $entry['id']]);
            }
            catch (Exception $e) {
                trigger_error("PHP::Failed adding receivable for time entry {$entry['id']}", EQ_REPORT_ERROR);
            }
        }
    }

    public static function getWorkflow(): array {
        return [
            'pending'   => [
                'description' => 'Time entry is still a draft an waiting to be completed.',
                'transitions' => [
                    'request-validation' => [
                        'description' => 'Sets time entry as ready for validation.',
                        'status'      => 'ready',
                        'policies'    => ['ready-for-validation']
                    ]
                ]
            ],

            'ready'     => [
                'description' => 'All required information have been completed and time entry is waiting to be validated.',
                'transitions' => [
                    'refuse'   => [
                        'description' => 'Refuse time entry, sets its status back to pending.',
                        'status'      => 'pending'
                    ],
                    'validate' => [
                        'description' => 'Validate time entry.',
                        'status'      => 'validated'
                    ]
                ]
            ],

            'validated' => [
                'description' => 'Time entry has been validated and is waiting to be invoiced.',
                'transitions' => [
                    'bill' => [
                        'description' => 'Create receivable, from time entry, who will be billed to the customer.',
                        'status'      => 'billed',
                        'policies'    => ['billable'],
                        'onafter'     => 'addReceivable'
                    ]
                ]
            ]
        ];
    }
}
