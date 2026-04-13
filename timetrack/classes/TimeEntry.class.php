<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2024
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace timetrack;

use sale\SaleEntry;
use sale\price\Price;
use sale\price\PriceList;
use core\setting\Setting;
use equal\orm\Model;

class TimeEntry extends SaleEntry {

    public static function getName(): string {
        return 'Time entry';
    }

    public static function getDescription(): string {
        return 'Time entries are used to log the tasks performed by employees on customers projects, and the duration spent on it.';
    }

    public static function getColumns(): array {

        return [

            /**
             * Override SaleEntry columns
             */

            'object_class' => [
                'type'           => 'string',
                'description'    => 'Class of the object.',
                'default'        => 'timetrack\TimeEntry'
            ],

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
                'help'              => 'This field is meant to explain what has actually been done, and serves for invoicing justification and followups.',
                'dependents'        => ['name'],
                'required'          => true
            ],

            'project_id' => [
                'type'           => 'many2one',
                'foreign_object' => 'timetrack\Project',
                'description'    => 'Identifier of the Project the sale entry originates from.',
                'dependents'     => ['name', 'ticket_link', 'customer_id', 'inventory_product_id', 'has_sale_model', 'product_id', 'price_id', 'unit_price', 'is_internal', 'billable_duration', 'billed_duration', 'qty', 'total'],
                'onupdate'       => 'onupdateProjectId'
            ],

            'inventory_product_id' => [
                'type'            => 'computed',
                'result_type'     => 'many2one',
                'foreign_object'  => 'inventory\Product',
                'description'     => 'The inventory product (infra) the the time entry refers to, if any.',
                'relation'        => ['project_id' => ['product_id']],
                'instant'         => true,
                'store'           => true
            ],

            'customer_id' => [
                'type'           => 'computed',
                'result_type'    => 'many2one',
                'foreign_object' => 'sale\customer\Customer',
                'description'    => 'Customer this time entry was created for.',
                'relation'       => ['project_id' => ['customer_id']],
                'store'          => true,
                'instant'        => true,
                'readonly'       => true
            ],

            'has_sale_model' => [
                'type'           => 'computed',
                'result_type'    => 'boolean',
                'description'    => 'Flag telling if a fixed sale model applies to the project.',
                'relation'       => ['project_id' => ['has_sale_model']],
                'store'          => true,
                'instant'        => true,
                'readonly'       => true
            ],

            'product_id' => [
                'type'           => 'computed',
                'result_type'    => 'many2one',
                'foreign_object' => 'sale\catalog\Product',
                'description'    => 'Product of the sale catalog.',
                'help'           => 'This field references a Product from the catalog. This field is not to be mistaken with the Product (software) of the customer.',
                'relation'       => ['project_id' => ['sale_model_id' => 'product_id']],
                'instant'        => true,
                'store'          => true,
                'dependents'     => ['price_id', 'unit_price', 'total']
            ],

            'price_id' => [
                'type'           => 'computed',
                'result_type'    => 'many2one',
                'foreign_object' => 'sale\price\Price',
                'description'    => 'Price of the sale.',
                'function'       => 'calcPriceId',
                'instant'        => true,
                'store'          => true,
                'dependents'     => ['unit_price', 'total']
            ],

            'unit_price' => [
                'type'           => 'computed',
                'result_type'    => 'float',
                'usage'          => 'amount/money:4',
                'description'    => 'Unit price of the product related to the entry.',
                'function'       => 'calcUnitPrice',
                'instant'        => true,
                'store'          => true,
                'onupdate'       => 'onupdateUnitPrice'
            ],

            'is_billable' => [
                'type'              => 'boolean',
                'description'       => 'Flag telling if the entry can be billed to the customer.',
                'help'              => 'Under certain circumstances, a task is performed for the organisation itself, or relates to a customer but cannot be billed (from a commercial perspective). Most of the time this cannot be known in advance and this flag is intended to be set manually.',
                'default'           => true,
                'dependents'        => ['billable_duration', 'billed_duration', 'qty', 'total']
            ],

            'qty' => [
                'type'           => 'computed',
                'result_type'    => 'float',
                'description'    => 'Quantity is expressed in hours, rounded to the quarter hour, and based on the billed duration.',
                'function'       => 'calcQty',
                'store'          => true
            ],

            /**
             * Specific TimeEntry columns
             */

            'date' => [
                'type'           => 'datetime',
                'description'    => 'Date of the entry.',
                'default'        => function() { return time(); },
                'dependents'     => ['price_id', 'unit_price']
            ],

            'time_start' => [
                'type'           => 'time',
                'description'    => 'Start time of the entry.',
                'default'        => function () { return self::getTimeZoneCurrentHour() * 3600; },
                'dependents'     => ['duration', 'billable_duration', 'billed_duration', 'qty', 'total']
            ],

            'time_end' => [
                'type'           => 'time',
                'description'    => 'End time of the entry.',
                'default'        => function () { return (self::getTimeZoneCurrentHour() + 1) * 3600; },
                'dependents'     => ['duration', 'billable_duration', 'billed_duration', 'qty', 'total']
            ],

            'is_full_day' => [
                'type'           => 'boolean',
                'description'    => 'The task of the entry was performed for a whole day.',
                'default'        => false,
                'dependents'     => ['duration', 'billable_duration', 'billed_duration', 'qty', 'total']
            ],

            'duration' => [
                'type'           => 'computed',
                'result_type'    => 'time',
                'description'    => 'Duration actually worked for the entry.',
                'function'       => 'calcDuration',
                'store'          => true,
                'instant'        => true,
                'readonly'       => true
            ],

            'billable_duration' => [
                'type'           => 'computed',
                'result_type'    => 'time',
                'function'       => 'calcBillableDuration',
                'description'    => 'Duration that can theoretically be invoiced.',
                'help'           => 'The duration that can be billed to the Customer according to the related requested Task. This value is based on the actual duration and rounded up to the started quarter hour.',
                'store'          => true,
                'instant'        => true,
                'readonly'       => true
            ],

            'billed_duration' => [
                'type'           => 'computed',
                'result_type'    => 'time',
                'store'          => true,
                'function'       => 'calcBilledDuration',
                'description'    => 'Duration that is eventually invoiced.',
                'help'           => 'The duration that is eventually invoiced. By default it matches billable duration, but it can be adjusted manually on a case-by-case basis.',
                'dependents'     => ['qty', 'total']
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
                                     E-mail: refers to a specific email conversation.\n
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
                'description'    => 'Reference completing the origin.'
            ],

            'total' => [
                'type'              => 'computed',
                'result_type'       => 'float',
                'usage'             => 'amount/money:4',
                'description'       => 'Tax-excluded amount that will be invoiced to the Customer.',
                'function'          => 'calcTotal',
                'store'             => true
            ],

            'is_internal' => [
                'type'              => 'computed',
                'result_type'       => 'boolean',
                'description'       => 'Flag telling if the entry can be billed to the customer.',
                'help'              => 'Under certain circumstances, a task is performed for the organisation itself, or relates to a customer but cannot be billed (from a commercial perspective). Most of the time this cannot be known in advance and this flag is intended to be set manually.',
                'store'             => true,
                'function'          => 'calcIsInternal',
                'dependents'        => ['billable_duration', 'billed_duration', 'qty', 'total']
            ],

        ];
    }

    private static function getTimeZoneCurrentHour(): int {
        $result = (int) date('H');

        $time_zone = Setting::get_value('core', 'locale', 'time_zone');
        if(!is_null($time_zone)) {
            try {
                $timezone = new \DateTimeZone($time_zone);
                $dateTime = new \DateTime('now', $timezone);
                $result = (int) $dateTime->format('H');
            }
            catch(\Exception $e) {
                trigger_error('PHP::error getting time zone current hour', EQ_REPORT_WARNING);
            }
        }

        return $result;
    }

    private static function computeTicketLink($url, $ticket_id): string {
        $result = $url ?? '';
        if(substr($result, -1) !== '/') {
            $result .= '/';
        }
        $result .= 'support/#/ticket/' . $ticket_id;
        return $result;
    }

    private static function computeQuarterHourDuration($duration): float {
        if($duration <= 0) {
            return 0.0;
        }

        return (float) (ceil($duration / 60 / 15) * 15 * 60);
    }

    private static function searchApplicablePrice($product_id, $date): ?Model {
        $price_lists_ids = PriceList::search(
                [
                    ['date_from', '<=', $date],
                    ['date_to', '>=', $date],
                    ['status', '=', 'published'],
                ],
                ['sort' => ['duration' => 'asc']]
            )
            ->ids();

        if(empty($price_lists_ids)) {
            return null;
        }

        foreach($price_lists_ids as $price_list_id) {
            $price = Price::search([
                    ['price_list_id', '=', $price_list_id],
                    ['product_id', '=', $product_id]
                ])
                ->read(['id', 'name', 'price'])
                ->first();

            if($price) {
                return $price;
            }
        }

        return null;
    }

    public static function defaultUserId($auth) {
        return $auth->userId();
    }

    public static function canupdate($self, $values): array {
        $self->read(['status']);

        foreach($self as $id => $entry) {
            if(in_array($entry['status'], ['pending', 'ready'])) {
                continue;
            }

            $editable_fields = ['description', 'detailed_description', 'status'];

            if($entry['status'] === 'validated') {
                $editable_fields = array_merge($editable_fields, ['product_id', 'price_id', 'unit_price', 'is_billable', 'billed_duration', 'has_receivable', 'receivable_id']);
            }

            foreach($values as $field => $value) {
                if(!in_array($field, $editable_fields)) {
                    return [
                            $field => [
                                'non_editable' => "Field '$field' cannot be updated Time entry at '{$entry['status']}'."
                            ]
                        ];
                }
            }
        }

        return parent::canupdate($self, $values);
    }

    public static function onchange($event, $values): array {
        $result = [];

        if(isset($event['origin'])) {
            if($event['origin'] != 'support') {
                $result['ticket_id'] = null;
                $result['ticket_link'] = null;
            }
        }

        if(isset($event['ticket_id'])) {
            $project = Project::id($values['project_id'])->read(['product_id' => 'url'])->first();
            $result['ticket_link'] = self::computeTicketLink($project['product_id']['url'], $event['ticket_id']);
        }

        if(isset($event['project_id'])) {
            $project = Project::id($event['project_id'])
                ->read(['product_id', 'is_internal', 'has_sale_model', 'sale_model_id' => ['product_id', 'price_id', 'unit_price'], 'customer_id' => ['name']])
                ->first();

            $result['is_internal'] = $project['is_internal'];
            $result['customer_id'] = $project['customer_id'];
            $result['inventory_product_id'] = $project['product_id'];
            $result['has_sale_model'] = $project['has_sale_model'] ?? false;

            if($project['has_sale_model'] ?? false) {
                $result['product_id'] = $project['sale_model_id']['product_id'] ?? null;
            }
            else {
                $result['product_id'] = null;
            }
        }

        $has_sale_model = $result['has_sale_model'] ?? $values['has_sale_model'] ?? false;

        if((isset($event['product_id']) || isset($event['date'])) && !$has_sale_model) {
            $product_id = $event['product_id'] ?? $values['product_id'] ?? null;
            $date = $event['date'] ?? $values['date'] ?? time();

            if($product_id) {
                $price = self::searchApplicablePrice($product_id, $date);
                $result['price_id'] = [
                    'id'    => $price['id'],
                    'name'  => $price['name'],
                ];
            }
            else {
                $result['price_id'] = null;
            }
        }

        if(isset($event['time_start'], $values['time_end'])
                || isset($event['time_end'], $values['time_start']) ) {
            $time_start = $event['time_start'] ?? $values['time_start'];
            $time_end = $event['time_end'] ?? $values['time_end'];

            if($time_end < $time_start) {
                $result['time_end'] = $time_start + ($values['duration'] ?? 0);
            }
            else {
                $diff = $time_end - $time_start;
                $billable_duration = self::computeBillableDuration($values['id'], $diff);
                $result['duration'] = $diff;
                $result['billable_duration'] = $billable_duration;
                $result['billed_duration'] = $billable_duration;
            }
        }
        elseif(isset($event['duration'], $values['time_start'])) {
            $billable_duration = self::computeBillableDuration($values['id'], $event['duration']);
            $result['time_end'] = $values['time_start'] + $event['duration'];
            $result['billable_duration'] = $billable_duration;
            $result['billed_duration'] = $billable_duration;
        }

        if(isset($event['is_full_day']) && $event['is_full_day']) {
            // #todo - read from settings
            $billable_duration = self::computeBillableDuration($values['id'], 7 * 3600);
            $result['time_start'] = 9 * 3600;
            $result['time_end'] = 17 * 3600;
            $result['duration'] = 7.5 * 3600;
            $result['billable_duration'] = $billable_duration;
            $result['billed_duration'] = $billable_duration;
        }
        elseif(
            isset($event['project_id'])
            || isset($event['is_billable'])
            || isset($event['is_full_day'])
            || isset($result['is_internal'])
        ) {
            $is_full_day = $values['is_full_day'] ?? false;
            $is_internal = $values['is_internal'] ?? $event['is_internal'] ?? false;
            $is_billable = $values['is_billable'] ?? $event['is_billable'] ?? false;

            if($is_internal || !$is_billable) {
                $result['billable_duration'] = 0;
                $result['billed_duration'] = 0;
            }
            else {
                if($is_full_day) {
                    // #todo - read from settings
                    $duration = 7.5 * 3600;
                    $result['billable_duration'] = $duration;
                    $result['billed_duration'] = $duration;
                }
                else {
                    $duration = $values['duration'] ?? $event['duration'] ?? 0;

                    if(!$duration && isset($values['time_start'], $values['time_end'])) {
                        $duration = $values['time_end'] - $values['time_start'];
                    }

                    if($duration) {
                        $result['billable_duration'] = $duration;
                        $result['billed_duration'] = $duration;
                    }
                }
            }
        }

        return $result;
    }

    private static function computeBillableDuration($id, $duration) {
        $entry = self::id($id)->read(['is_billable', 'is_internal'])->first();
        $is_billable = !$entry['is_internal'];
        $is_billable = $is_billable && $entry['is_billable'];
        return $is_billable ? self::computeQuarterHourDuration($duration) : 0.0;
    }

    public static function onupdateProjectId($self): void {
        $self->read(['project_id' => ['name', 'receivable_queue_id']]);
        foreach($self as $id => $entry) {
            if(!$entry['project_id']) {
                continue;
            }
            if($entry['project_id']['receivable_queue_id']) {
                self::id($id)->update(['receivable_queue_id' => $entry['project_id']['receivable_queue_id']]);
            }
            // #memo - by convention we group sales from a same project when invoicing
            self::id($id)->update(['invoice_group' => $entry['project_id']['name']]);
        }
    }

    public static function onupdateTicketId($self): void {
        $self->read(['ticket_id']);
        foreach($self as $id => $entry) {
            self::id($id)->update(['reference' => 'ticket '.$entry['ticket_id']]);
        }
    }

    public static function calcIsInternal($self) {
        $result = [];
        $self->read(['project_id' => ['is_internal'], 'inventory_product_id' => ['is_internal']]);
        foreach($self as $id => $entry) {
            $result[$id] = ($entry['project_id']['is_internal'] ?? false) || ($entry['inventory_product_id']['is_internal'] ?? false);
        }
        return $result;
    }

    public static function calcName($self) {
        $result = [];
        $self->read(['project_id' => ['name'], 'origin', 'reference', 'description']);
        foreach($self as $id => $entry) {
            $result[$id] = $entry['project_id']['name'];
            if(isset($entry['reference']) && strlen($entry['reference']) > 0) {
                $result[$id] .=  ' ['.$entry['reference'].']';
            }
            else {
                $result[$id] .= ' - '.ucfirst($entry['origin']);
            }
            if(isset($entry['description']) && strlen($entry['description']) > 0) {
                $result[$id] .= ' - '.$entry['description'];
            }
        }
        return $result;
    }

    protected static function calcPriceId($self): array {
        $result = [];
        $self->read(['has_sale_model', 'project_id' => ['sale_model_id' => ['price_id']], 'product_id', 'date']);
        foreach($self as $id => $entry) {
            if($entry['has_sale_model'] ?? false) {
                $result[$id] = $entry['project_id']['sale_model_id']['price_id'] ?? null;
                continue;
            }

            if(!isset($entry['product_id'], $entry['date'])) {
                continue;
            }

            $price = self::searchApplicablePrice($entry['product_id'], $entry['date']);
            if($price) {
                $result[$id] = $price['id'];
            }
        }
        return $result;
    }

    protected static function calcUnitPrice($self): array {
        $result = [];
        $self->read(['has_sale_model', 'project_id' => ['sale_model_id' => ['unit_price']], 'price_id' => ['price']]);
        foreach($self as $id => $entry) {
            if($entry['has_sale_model'] ?? false) {
                $result[$id] = $entry['project_id']['sale_model_id']['unit_price'] ?? ($entry['price_id']['price'] ?? null);
                continue;
            }

            if(isset($entry['price_id']['price'])) {
                $result[$id] = $entry['price_id']['price'];
            }
        }
        return $result;
    }

    protected static function calcBillableDuration($self): array {
        $result = [];
        $self->read(['is_full_day', 'time_start', 'time_end']);
        foreach($self as $id => $entry) {
            if($entry['is_full_day']) {
                // #todo - read billable_hours_in_day from settings
                $result[$id] = self::computeBillableDuration($id, 7.5 * 3600);
            }
            elseif(isset($entry['time_start'], $entry['time_end'])) {
                $result[$id] =  self::computeBillableDuration($id, $entry['time_end'] - $entry['time_start']);
            }
        }
        return $result;
    }

    public static function calcBilledDuration($self): array {
        $result = [];
        $self->read(['billable_duration']);
        foreach($self as $id => $entry) {
            $result[$id] = $entry['billable_duration'] ?? 0.0;
        }
        return $result;
    }

    public static function calcDuration($self, $orm): array {
        $result = [];
        $self->read(['state', 'is_full_day', 'time_start', 'time_end']);
        foreach($self as $id => $entry) {
            if(!isset($entry['time_start'], $entry['time_end'])) {
                continue;
            }
            if($entry['is_full_day']) {
                // #todo - read from settings
                $result[$id] = 7.5 * 3600;
            }
            else {
                $result[$id] = $entry['time_end'] - $entry['time_start'];
            }
        }
        return $result;
    }

    public static function calcQty($self): array {
        $result = [];
        $self->read(['billed_duration']);
        foreach($self as $id => $entry) {
            $hours = floatval($entry['billed_duration']) / 3600;
            $result[$id] = round($hours * 4) / 4;
        }
        return $result;
    }

    public static function calcTicketLink($self): array {
        $result = [];
        $self->read(['origin', 'ticket_id', 'project_id' => ['product_id' => ['url']]]);
        foreach($self as $id => $entry) {
            if($entry['origin'] == 'support') {
                $result[$id] = self::computeTicketLink($entry['project_id']['product_id']['url'], $entry['ticket_id']);
            }
        }
        return $result;
    }

    public static function policyReadyForValidation($self): array {
        $result = [];
        $self->read(['project_id', 'user_id', 'origin', 'duration']);
        foreach($self as $id => $entry) {
            if( !isset($entry['project_id'], $entry['user_id'], $entry['origin'], $entry['duration'])
                    || $entry['duration'] <= 0 ) {
                $result[$id] = false;
            }
        }

        return $result;
    }

    public static function getWorkflow() {
        return [
            'pending' => [
                'description' => 'Time entry is still a draft and waiting to be completed.',
                'icon' => 'edit',
                'transitions' => [
                    'submit' => [
                        'description' => 'Sets time entry as ready for validation.',
                        'policies' => [
                            'ready-for-validation',
                        ],
                        'status' => 'ready',
                    ],
                ],
            ],
            'ready' => [
                'description' => 'Time entry required information are waiting for approval.',
                'help' => 'Specific information about time entry (project, user, origin and duration) have been completed and time entry is waiting for approval.',
                'icon' => 'pending',
                'transitions' => [
                    'refuse' => [
                        'description' => 'Refuse time entry, sets its status back to pending.',
                        'status' => 'pending',
                    ],
                    'validate' => [
                        'description' => 'Validate time entry.',
                        'status' => 'validated',
                    ],
                ],
            ],
            'validated' => [
                'description' => 'Sale information must be completed to bill the sale entry.',
                'help' => 'Time entry information have been validated, product and prices information must be completed to be billable.',
                'icon' => 'check_circled',
                'transitions' => [
                    'bill' => [
                        'description' => 'Create receivable, from time entry, who will be billed to the customer.',
                        'onbefore' => 'doCreateReceivable',
                        'policies' => [
                            'billable',
                        ],
                        'status' => 'billed',
                    ],
                ],
            ],
            'billed' => [
                'description' => 'A receivable was generated, it can be invoiced to the customer.',
                'icon' => 'receipt_long',
                'transitions' => [
                ],
            ],
        ];
    }
}
