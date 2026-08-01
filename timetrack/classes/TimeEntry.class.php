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
use sale\SaleModel;

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
                'dependents'        => ['name']
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
                'description'     => 'The inventory product the time entry refers to, if any.',
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
                'function'       => 'calcProductId',
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
                'dependents'     => ['price_id', 'unit_price', 'creation_delta']
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

            'pause_time' => [
                'type'              => 'integer',
                'usage'             => 'time',
                'description'       => 'Pause time to subtract from total time.',
            ],

            // #todo - add a Customer establishment and base time on related address
            'travel_time' => [
                'type'              => 'integer',
                'usage'             => 'time',
                'description'       => 'Computed travel duration: based on settings and customer.',
            ],

            'on_site' => [
                'type'              => 'boolean',
                'default'           => false,
                'description'       => 'Does the entry imply some travel? Value retrieved from the related Ticket.'
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

            'priority' => [
                'type'              => 'integer',
                'selection'         => [
                    1       => 'Low',
                    2       => 'Medium',
                    3       => 'High',
                    4       => 'Critical'
                ],
                'default'           => 1,
                'description'       => 'Priority level retrieved from the related Ticket (1 = low, 4 = critical).'
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
                'description'    => 'Duration that will be invoiced.',
                'help'           => 'The duration that is eventually invoiced. By default it matches billable duration, but it can be adjusted manually on a case-by-case basis.',
                'dependents'     => ['qty', 'total']
            ],

            'user_id' => [
                'type'           => 'many2one',
                'foreign_object' => 'core\User',
                'description'    => 'User the time entry was performed by.',
                'default'        => 'defaultUserId'
            ],

            'creation_delta' => [
                'type'              => 'computed',
                'result_type'       => 'time',
                'description'       => 'Computed delay between the time of recording of the entry and the actual time the work took place.',
                'function'          => 'calcCreationDelta',
                'store'             => true
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

    private static function computeBillableDurationValue($duration, bool $is_billable, bool $is_internal): float {
        if($is_internal || !$is_billable) {
            return 0.0;
        }

        return self::computeQuarterHourDuration($duration);
    }

    private static function computeWorkedDurationValue(bool $is_full_day, $time_start = null, $time_end = null, $duration = null): float {
        if($is_full_day) {
            return 7.5 * 3600;
        }

        if($duration !== null) {
            return max(0, (float) $duration);
        }

        if($time_start !== null && $time_end !== null) {
            return max(0, (float) ($time_end - $time_start));
        }

        return 0.0;
    }

    private static function computeApplicablePrice($product_id, $date): ?Model {
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
        $self->read(['status', 'project_id' => ['is_internal'], 'is_billable', 'is_full_day', 'time_start', 'time_end', 'duration', 'billable_duration', 'billed_duration']);

        foreach($self as $id => $entry) {
            if(in_array($entry['status'], ['pending', 'ready'])) {
                // allowed statuses, keep checking business constraints below
            }
            else {
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

            $project_is_internal = (bool) ($entry['project_id']['is_internal'] ?? false);
            if(array_key_exists('project_id', $values)) {
                if($values['project_id']) {
                    $project = Project::id($values['project_id'])->read(['is_internal'])->first();
                    $project_is_internal = (bool) ($project['is_internal'] ?? false);
                }
                else {
                    $project_is_internal = false;
                }
            }

            $is_internal = $project_is_internal;
            $is_billable = array_key_exists('is_billable', $values) ? (bool) $values['is_billable'] : (bool) ($entry['is_billable'] ?? true);
            $is_full_day = array_key_exists('is_full_day', $values) ? (bool) $values['is_full_day'] : (bool) ($entry['is_full_day'] ?? false);
            $time_start = array_key_exists('time_start', $values) ? $values['time_start'] : ($entry['time_start'] ?? null);
            $time_end = array_key_exists('time_end', $values) ? $values['time_end'] : ($entry['time_end'] ?? null);
            $duration = array_key_exists('duration', $values) ? $values['duration'] : ($entry['duration'] ?? null);

            if(!$is_full_day && $time_start !== null && $time_end !== null) {
                $duration = $time_end - $time_start;
            }

            $target_billable_duration = self::computeBillableDurationValue(
                self::computeWorkedDurationValue($is_full_day, $time_start, $time_end, $duration),
                $is_billable,
                $is_internal
            );

            $recomputes_billed_duration =
                array_key_exists('project_id', $values)
                || array_key_exists('is_billable', $values)
                || array_key_exists('is_full_day', $values)
                || array_key_exists('time_start', $values)
                || array_key_exists('time_end', $values)
                || array_key_exists('duration', $values);

            $target_billed_duration = array_key_exists('billed_duration', $values)
                ? (float) $values['billed_duration']
                : ($recomputes_billed_duration ? $target_billable_duration : (float) ($entry['billed_duration'] ?? 0));

            if($target_billed_duration > $target_billable_duration) {
                return [
                    'billed_duration' => [
                        'invalid' => 'Billed duration cannot be greater than billable duration.'
                    ]
                ];
            }
        }

        return parent::canupdate($self, $values);
    }

    public static function onchange($event, $values): array {
        $result = [];
        $refresh_durations = false;
        $refresh_sale_amounts = false;

        $get_changed_value = function(string $field, $default = null) use ($event, &$result) {
            if(array_key_exists($field, $result)) {
                return $result[$field];
            }

            if(array_key_exists($field, $event)) {
                return $event[$field];
            }

            return $default;
        };

        $get_assigned_value = function(string $field, $default = null) use ($values) {
            if(array_key_exists($field, $values)) {
                return $values[$field];
            }

            return $default;
        };

        if(array_key_exists('origin', $event)) {
            if($event['origin'] != 'support') {
                $result['ticket_id'] = null;
                $result['ticket_link'] = null;
            }
        }

        if(array_key_exists('ticket_id', $event) && !empty($values['project_id'])) {
            $project = Project::id($values['project_id'])->read(['product_id' => 'url'])->first();
            $result['ticket_link'] = self::computeTicketLink($project['product_id']['url'], $event['ticket_id']);
        }

        if(array_key_exists('project_id', $event)) {
            if($event['project_id']) {
                $project = Project::id($event['project_id'])
                    ->read(['product_id', 'is_internal', 'has_sale_model', 'sale_model_id' => ['product_id', 'price_id', 'unit_price'], 'customer_id' => ['name']])
                    ->first();

                $result['is_internal'] = $project['is_internal'];
                $result['customer_id'] = $project['customer_id'];
                $result['inventory_product_id'] = $project['product_id'];
                $result['has_sale_model'] = $project['has_sale_model'] ?? false;

                if($project['has_sale_model'] ?? false) {
                    $result['product_id'] = $project['sale_model_id']['product_id'] ?? null;
                    $result['price_id'] = $project['sale_model_id']['price_id'] ?? null;
                    $result['unit_price'] = $project['sale_model_id']['unit_price'] ?? null;
                    $refresh_sale_amounts = true;
                }
                else {
                    $result['product_id'] = null;
                    $result['price_id'] = null;
                    $result['unit_price'] = null;
                    $refresh_sale_amounts = true;
                }
            }
            else {
                $result['is_internal'] = false;
                $result['customer_id'] = null;
                $result['inventory_product_id'] = null;
                $result['has_sale_model'] = false;
                $result['product_id'] = null;
                $result['price_id'] = null;
                $result['unit_price'] = null;
                $refresh_sale_amounts = true;
            }

            $refresh_durations = true;
        }

        $has_sale_model = (bool) $get_changed_value('has_sale_model', $get_assigned_value('has_sale_model', false));

        if(
            (
                array_key_exists('project_id', $event)
                || array_key_exists('product_id', $event)
                || array_key_exists('date', $event)
            )
            && !$has_sale_model
        ) {
            $product_id = $get_changed_value('product_id', $get_assigned_value('product_id'));
            $date = $get_changed_value('date', $get_assigned_value('date', time()));

            if($product_id) {
                $price = self::computeApplicablePrice($product_id, $date);

                if($price) {
                    $result['price_id'] = [
                        'id'    => $price['id'],
                        'name'  => $price['name'],
                    ];
                    $result['unit_price'] = $price['price'];
                    $refresh_sale_amounts = true;
                }
                else {
                    $result['price_id'] = null;
                    $result['unit_price'] = null;
                    $refresh_sale_amounts = true;
                }
            }
            else {
                $result['price_id'] = null;
                $result['unit_price'] = null;
                $refresh_sale_amounts = true;
            }
        }

        if(
            array_key_exists('time_start', $event)
            || array_key_exists('time_end', $event)
            || array_key_exists('duration', $event)
            || array_key_exists('is_full_day', $event)
            || array_key_exists('is_billable', $event)
        ) {
            $refresh_durations = true;
        }

        $is_full_day = (bool) $get_changed_value('is_full_day', $get_assigned_value('is_full_day', false));
        if($is_full_day && array_key_exists('is_full_day', $event)) {
            $result['time_start'] = 9 * 3600;
            $result['time_end'] = 17 * 3600;
            $result['duration'] = 7.5 * 3600;
        }

        if($refresh_durations) {
            if(!$is_full_day) {
                $time_start = $get_changed_value('time_start');
                $time_end = $get_changed_value('time_end');
                $assigned_time_start = $get_assigned_value('time_start');
                $assigned_time_end = $get_assigned_value('time_end');

                if($time_start === null) {
                    $time_start = $assigned_time_start;
                }

                if($time_end === null) {
                    $time_end = $assigned_time_end;
                }

                if(isset($time_start, $time_end) && $time_end < $time_start) {
                    $base_duration = $get_changed_value('duration', $get_assigned_value('duration', 0));
                    $result['time_end'] = $time_start + $base_duration;
                    $time_end = $result['time_end'];
                }

                if(array_key_exists('duration', $event) && isset($time_start)) {
                    $result['time_end'] = $time_start + $event['duration'];
                    $time_end = $result['time_end'];
                }

                if(isset($time_start, $time_end)) {
                    $result['duration'] = $time_end - $time_start;
                }
            }

            $duration = $get_changed_value('duration', $get_assigned_value('duration', 0));
            $is_internal = (bool) $get_changed_value('is_internal', $get_assigned_value('is_internal', false));
            $is_billable = (bool) $get_changed_value('is_billable', $get_assigned_value('is_billable', true));

            $billable_duration = self::computeBillableDurationValue($duration, $is_billable, $is_internal);
            $result['billable_duration'] = $billable_duration;
            $result['billed_duration'] = $billable_duration;
            $refresh_sale_amounts = true;
        }

        if(
            array_key_exists('billed_duration', $event)
            || array_key_exists('unit_price', $event)
            || array_key_exists('is_billable', $event)
            || array_key_exists('free_qty', $event)
            || array_key_exists('discount', $event)
        ) {
            $refresh_sale_amounts = true;
        }

        if($refresh_sale_amounts) {
            $billed_duration = $get_changed_value('billed_duration', $get_assigned_value('billed_duration', 0));
            $qty = round((floatval($billed_duration) / 3600) * 4) / 4;
            $is_billable = (bool) $get_changed_value('is_billable', $get_assigned_value('is_billable', true));
            $unit_price = $get_changed_value('unit_price', $get_assigned_value('unit_price', 0));
            $free_qty = $get_changed_value('free_qty', $get_assigned_value('free_qty', 0));
            $discount = $get_changed_value('discount', $get_assigned_value('discount', 0));

            $result['qty'] = $qty;
            $result['total'] = $is_billable
                ? round(floatval($unit_price) * (1.0 - floatval($discount)) * ($qty - floatval($free_qty)), 4)
                : 0.0;
        }

        return $result;
    }

    private static function computeBillableDuration($id, $duration) {
        $entry = self::id($id)->read(['is_billable', 'is_internal'])->first();
        return self::computeBillableDurationValue(
            $duration,
            (bool) ($entry['is_billable'] ?? true),
            (bool) ($entry['is_internal'] ?? false)
        );
    }

    public static function onupdateProjectId($self): void {
        $self->read(['project_id' => ['name', 'receivable_queue_id']]);
        foreach($self as $id => $entry) {
            if(!$entry['project_id']) {
                continue;
            }
            $values = [
                // #memo - by convention we group sales from a same project when invoicing
                'invoice_group' => $entry['project_id']['name']
            ];
            if($entry['project_id']['receivable_queue_id']) {
                $values['receivable_queue_id'] = $entry['project_id']['receivable_queue_id'];
            }
            self::id($id)->update($values);
        }
    }

    public static function onupdateTicketId($self): void {
        $self->read(['ticket_id']);
        foreach($self as $id => $entry) {
            self::id($id)->update(['reference' => 'ticket '.$entry['ticket_id']]);
        }
    }
    protected static function calcProductId($self) {
        $saleModel = SaleModel::id(1)->read(['product_id'])->first();
        $self->read(['project_id' => ['sale_model_id' => ['product_id']]]);
        foreach($self as $id => $timeEntry) {
            if($timeEntry['project_id']['sale_model_id']['product_id'] ?? null) {
                $result[$id] = $timeEntry['project_id']['sale_model_id']['product_id'];
            }
            else {
                // fallback to default product (from default sale model)
                $result[$id] = $saleModel['product_id'];
            }
        }
        return $result;
    }

    protected static function calcCreationDelta($self) {
        $result = [];
        $self->read(['date', 'created']);
        foreach($self as $id => $entry) {
            if(!$entry['created'] || !$entry['end']) {
                continue;
            }
            $result[$id] = $entry['created'] - $entry['end'];
        }
        return $result;
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
        $self->read(['product_id', 'date', 'has_sale_model', 'project_id' => ['sale_model_id' => ['has_price', 'price_id']]]);
        foreach($self as $id => $entry) {
            $price_id = null;

            if($entry['has_sale_model']) {
                if($entry['project_id']['sale_model_id']['has_price']) {
                    $price_id = $entry['project_id']['sale_model_id']['price_id'];
                }
            }

            if(!$price_id && isset($entry['product_id'], $entry['date'])) {
                $price = self::computeApplicablePrice($entry['product_id'], $entry['date']);
                if($price) {
                    $price_id = $price['id'];
                }
            }

            $result[$id] = $price_id;
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
        $self->read(['description', 'project_id', 'user_id', 'origin', 'duration']);
        foreach($self as $id => $entry) {
            if( !isset($entry['description'], $entry['project_id'], $entry['user_id'], $entry['origin'], $entry['duration'])
                    || !strlen(trim($entry['description']))
                    || $entry['duration'] <= 0 ) {
                $result[$id] = false;
            }
        }

        return $result;
    }

    public function getIndexes(): array {
        return [
            ['object_class', 'user_id', 'customer_id', 'project_id']
        ];
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
