<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2024
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace sale\receivable;

use core\setting\Setting;
use equal\orm\Model;

class Receivable extends Model {

    public static function getDescription() {
        return 'A Sale Receivable represent a good or a service that has been sold to a Customer, and whose amount must be received.';
    }

    public static function getColumns() {

        return [
            'name' => [
                'type'              => 'computed',
                'result_type'       => 'string',
                'description'       => 'Default label of the line, based on product.',
                'function'          => 'calcName',
                'store'             => true,
                'readonly'          => true
            ],

            'description' => [
                'type'              => 'computed',
                'result_type'       => 'string',
                'usage'             => 'text/plain',
                'description'       => 'Description of the receivable.',
                'function'          => 'calcDescription',
                'store'             => true,
                'readonly'          => true
            ],

            'receivables_queue_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\receivable\ReceivablesQueue',
                'description'       => 'The parent Queue the receivable is attached to.',
                'required'          => true,
                'domain'            => ['customer_id', '=', 'object.customer_id']
            ],

            'date' => [
                'type'              => 'datetime',
                'description'       => 'Creation date of the receivable.',
                'help'              => 'Not all sale entries are synchronous, and a receivable might have a distinct date (i.e. subscription).',
                'readonly'          => true
            ],

            'status' => [
                'type'              => 'string',
                'description'       => 'Status of the receivable (pending, invoiced or cancelled).',
                'selection'         => [
                    'pending',
                    'invoiced',
                    'cancelled'
                ],
                'default'           => 'pending'
            ],

            'origin_object_class' => [
                'type'              => 'string',
                'description'       => 'Entity class that the Receivable originates from.',
                'help'              => 'Sale entries can to extended by other classes to enrich logic behavior. This field is used to store the class name of the object. Selection is provided as a memo but is non-exhaustive.',
                'default'           => 'sale\SaleEntry',
                'dependents'        => ['name', 'description', 'sale_entry_id', 'time_entry_id', 'subscription_entry_id', 'customer_id', 'product_id', 'price_id', 'unit_price', 'vat_rate', 'qty', 'free_qty', 'discount', 'total', 'price'],
                'selection'         => [
                    'sale\SaleEntry',
                    'timetrack\TimeEntry',
                    'sale\subscription\SubscriptionEntry'
                ]
            ],

            'origin_object_id' => [
                'type'              => 'integer',
                'description'       => 'Object identifier, as a complement to `origin_object_class`.',
                'help'              => 'Together origin_object_class and origin_object_id reference the accounting document the entry is linked to.',
                'dependents'        => ['name', 'description', 'sale_entry_id', 'time_entry_id', 'subscription_entry_id', 'product_id', 'price_id', 'unit_price', 'vat_rate', 'qty', 'free_qty', 'discount', 'total', 'price'],
                'required'          => true,
                'readonly'          => true
            ],

            'sale_entry_id' => [
                'type'              => 'computed',
                'result_type'       => 'many2one',
                'foreign_object'    => 'sale\SaleEntry',
                'relation'          => ['origin_object_id'],
                'readonly'          => true,
                'visible'           => ['origin_object_class', '=', 'sale\SaleEntry']
            ],

            'time_entry_id' => [
                'type'              => 'computed',
                'result_type'       => 'many2one',
                'foreign_object'    => 'timetrack\TimeEntry',
                'relation'          => ['origin_object_id'],
                'readonly'          => true,
                'visible'           => ['origin_object_class', '=', 'timetrack\TimeEntry']
            ],

            'subscription_entry_id' => [
                'type'              => 'computed',
                'result_type'       => 'many2one',
                'foreign_object'    => 'sale\subscription\SubscriptionEntry',
                'relation'          => ['origin_object_id'],
                'readonly'          => true,
                'visible'           => ['origin_object_class', '=', 'sale\subscription\SubscriptionEntry']
            ],

            'invoice_group' => [
                'type'              => 'string',
                'description'       => 'Arbitrary name for grouping sales when invoicing (might be left unset).',
            ],

            'customer_id' => [
                'type'              => 'computed',
                'result_type'       => 'many2one',
                'foreign_object'    => 'sale\customer\Customer',
                'description'       => 'The Customer to who refers the item.',
                'function'          => 'calcCustomerId',
                'store'             => true,
                'readonly'          => true
            ],

            'product_id' => [
                'type'              => 'computed',
                'result_type'       => 'many2one',
                'foreign_object'    => 'sale\catalog\Product',
                'description'       => 'The product (SKU) the receivable relates to.',
                'function'          => 'calcProductId',
                'store'             => true,
                'readonly'          => true
            ],

            'price_id' => [
                'type'              => 'computed',
                'result_type'       => 'many2one',
                'foreign_object'    => 'sale\price\Price',
                'description'       => 'The price the receivable relates to.',
                'function'          => 'calcPriceId',
                'store'             => true,
                'readonly'          => true
            ],

            'unit_price' => [
                'type'              => 'computed',
                'result_type'       => 'float',
                'usage'             => 'amount/money:4',
                'description'       => 'Unit price of the product related to the receivable.',
                'function'          => 'calcUnitPrice',
                'store'             => true,
                'readonly'          => true
            ],

            'vat_rate' => [
                'type'              => 'computed',
                'result_type'       => 'float',
                'usage'             => 'amount/rate',
                'description'       => 'VAT rate to be applied.',
                'function'          => 'calcVatRate',
                'store'             => true,
                'readonly'          => true
            ],

            'qty' => [
                'type'              => 'computed',
                'result_type'       => 'float',
                'description'       => 'Quantity of product.',
                'function'          => 'calcQty',
                'store'             => true,
                'readonly'          => true
            ],

            'free_qty' => [
                'type'              => 'computed',
                'result_type'       => 'integer',
                'description'       => 'Free quantity of product, if any.',
                'function'          => 'calcFreeQty',
                'store'             => true,
                'readonly'          => true
            ],

            'discount' => [
                'type'              => 'computed',
                'result_type'       => 'float',
                'usage'             => 'amount/rate',
                'description'       => 'Total amount of discount to apply, if any.',
                'function'          => 'calcDiscount',
                'store'             => true,
                'readonly'          => true
            ],

            'total' => [
                'type'              => 'computed',
                'result_type'       => 'float',
                'usage'             => 'amount/money:4',
                'description'       => 'Total tax-excluded price of the receivable.',
                'function'          => 'calcTotal',
                'store'             => true,
                'readonly'          => true
            ],

            'price' => [
                'type'              => 'computed',
                'result_type'       => 'float',
                'usage'             => 'amount/money:2',
                'description'       => 'Final tax-included price of the receivable.',
                'function'          => 'calcPrice',
                'store'             => true,
                'readonly'          => true
            ],

            'invoice_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\accounting\invoice\Invoice',
                'description'       => 'Invoice the receivable is related to.',
                'ondelete'          => 'null'
            ],

            // receivable is either linked to an invoice line or to a service account entry

            'invoice_line_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\accounting\invoice\InvoiceLine',
                'description'       => 'The invoice line that has been generated based on the item.',
                'ondelete'          => 'null'
            ],

            'service_account_entry_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\contract\ServiceAccountEntry',
                'description'       => 'The SA entry that has been generated based on the item.',
                'ondelete'          => 'null'
            ]

        ];
    }

    public static function calcName($self) {
        $result = [];
        $self->read([
            'origin_object_class',
            'sale_entry_id'          => ['name'],
            'time_entry_id'          => ['name'],
            'subscription_entry_id'  => ['name']
        ]);

        foreach($self as $id => $receivable) {
            if(($receivable['origin_object_class'] ?? '') === 'timetrack\TimeEntry') {
                $saleEntry = $receivable['time_entry_id'] ?? [];
            }
            elseif(($receivable['origin_object_class'] ?? '') === 'sale\subscription\SubscriptionEntry') {
                $saleEntry = $receivable['subscription_entry_id'] ?? [];
            }
            else {
                $saleEntry = $receivable['sale_entry_id'] ?? [];
            }
            $result[$id] = $saleEntry['name'] ?? '';
        }

        return $result;
    }

    public static function calcDescription($self): array {
        $result = [];
        $self->read([
            'origin_object_class',
            'sale_entry_id'          => ['description'],
            'time_entry_id'          => ['description'],
            'subscription_entry_id'  => ['description']
        ]);

        foreach($self as $id => $receivable) {
            if(($receivable['origin_object_class'] ?? '') === 'timetrack\TimeEntry') {
                if(isset($receivable['time_entry_id']) && is_array($receivable['time_entry_id']) && array_key_exists('description', $receivable['time_entry_id'])) {
                    $result[$id] = $receivable['time_entry_id']['description'];
                }
            }
            elseif(($receivable['origin_object_class'] ?? '') === 'sale\subscription\SubscriptionEntry') {
                if(isset($receivable['subscription_entry_id']) && is_array($receivable['subscription_entry_id']) && array_key_exists('description', $receivable['subscription_entry_id'])) {
                    $result[$id] = $receivable['subscription_entry_id']['description'];
                }
            }
            else {
                if(isset($receivable['sale_entry_id']) && is_array($receivable['sale_entry_id']) && array_key_exists('description', $receivable['sale_entry_id'])) {
                    $result[$id] = $receivable['sale_entry_id']['description'];
                }
            }
        }

        return $result;
    }

    public static function calcCustomerId($self): array {
        $result = [];
        $self->read([
            'origin_object_class',
            'sale_entry_id'          => ['customer_id'],
            'time_entry_id'          => ['customer_id'],
            'subscription_entry_id'  => ['customer_id']
        ]);

        foreach($self as $id => $receivable) {
            if(($receivable['origin_object_class'] ?? '') === 'timetrack\TimeEntry') {
                if(isset($receivable['time_entry_id']) && is_array($receivable['time_entry_id']) && array_key_exists('customer_id', $receivable['time_entry_id'])) {
                    $result[$id] = $receivable['time_entry_id']['customer_id'];
                }
            }
            elseif(($receivable['origin_object_class'] ?? '') === 'sale\subscription\SubscriptionEntry') {
                if(isset($receivable['subscription_entry_id']) && is_array($receivable['subscription_entry_id']) && array_key_exists('customer_id', $receivable['subscription_entry_id'])) {
                    $result[$id] = $receivable['subscription_entry_id']['customer_id'];
                }
            }
            else {
                if(isset($receivable['sale_entry_id']) && is_array($receivable['sale_entry_id']) && array_key_exists('customer_id', $receivable['sale_entry_id'])) {
                    $result[$id] = $receivable['sale_entry_id']['customer_id'];
                }
            }
        }

        return $result;
    }

    public static function calcProductId($self): array {
        $result = [];
        $self->read([
            'origin_object_class',
            'sale_entry_id'          => ['product_id'],
            'time_entry_id'          => ['product_id'],
            'subscription_entry_id'  => ['product_id']
        ]);

        foreach($self as $id => $receivable) {
            if(($receivable['origin_object_class'] ?? '') === 'timetrack\TimeEntry') {
                if(isset($receivable['time_entry_id']) && is_array($receivable['time_entry_id']) && array_key_exists('product_id', $receivable['time_entry_id'])) {
                    $result[$id] = $receivable['time_entry_id']['product_id'];
                }
            }
            elseif(($receivable['origin_object_class'] ?? '') === 'sale\subscription\SubscriptionEntry') {
                if(isset($receivable['subscription_entry_id']) && is_array($receivable['subscription_entry_id']) && array_key_exists('product_id', $receivable['subscription_entry_id'])) {
                    $result[$id] = $receivable['subscription_entry_id']['product_id'];
                }
            }
            else {
                if(isset($receivable['sale_entry_id']) && is_array($receivable['sale_entry_id']) && array_key_exists('product_id', $receivable['sale_entry_id'])) {
                    $result[$id] = $receivable['sale_entry_id']['product_id'];
                }
            }
        }

        return $result;
    }

    public static function calcPriceId($self): array {
        $result = [];
        $self->read([
            'origin_object_class',
            'sale_entry_id'          => ['price_id'],
            'time_entry_id'          => ['price_id'],
            'subscription_entry_id'  => ['price_id']
        ]);

        foreach($self as $id => $receivable) {
            if(($receivable['origin_object_class'] ?? '') === 'timetrack\TimeEntry') {
                if(isset($receivable['time_entry_id']) && is_array($receivable['time_entry_id']) && array_key_exists('price_id', $receivable['time_entry_id'])) {
                    $result[$id] = $receivable['time_entry_id']['price_id'];
                }
            }
            elseif(($receivable['origin_object_class'] ?? '') === 'sale\subscription\SubscriptionEntry') {
                if(isset($receivable['subscription_entry_id']) && is_array($receivable['subscription_entry_id']) && array_key_exists('price_id', $receivable['subscription_entry_id'])) {
                    $result[$id] = $receivable['subscription_entry_id']['price_id'];
                }
            }
            else {
                if(isset($receivable['sale_entry_id']) && is_array($receivable['sale_entry_id']) && array_key_exists('price_id', $receivable['sale_entry_id'])) {
                    $result[$id] = $receivable['sale_entry_id']['price_id'];
                }
            }
        }

        return $result;
    }

    public static function calcUnitPrice($self): array {
        $result = [];
        $self->read([
            'origin_object_class',
            'sale_entry_id'          => ['unit_price'],
            'time_entry_id'          => ['unit_price'],
            'subscription_entry_id'  => ['unit_price']
        ]);

        foreach($self as $id => $receivable) {
            if(($receivable['origin_object_class'] ?? '') === 'timetrack\TimeEntry') {
                if(isset($receivable['time_entry_id']) && is_array($receivable['time_entry_id']) && array_key_exists('unit_price', $receivable['time_entry_id'])) {
                    $result[$id] = $receivable['time_entry_id']['unit_price'];
                }
            }
            elseif(($receivable['origin_object_class'] ?? '') === 'sale\subscription\SubscriptionEntry') {
                if(isset($receivable['subscription_entry_id']) && is_array($receivable['subscription_entry_id']) && array_key_exists('unit_price', $receivable['subscription_entry_id'])) {
                    $result[$id] = $receivable['subscription_entry_id']['unit_price'];
                }
            }
            else {
                if(isset($receivable['sale_entry_id']) && is_array($receivable['sale_entry_id']) && array_key_exists('unit_price', $receivable['sale_entry_id'])) {
                    $result[$id] = $receivable['sale_entry_id']['unit_price'];
                }
            }
        }

        return $result;
    }

    public static function calcVatRate($self): array {
        $result = [];
        $self->read([
            'origin_object_class',
            'sale_entry_id'          => ['vat_rate'],
            'time_entry_id'          => ['vat_rate'],
            'subscription_entry_id'  => ['vat_rate']
        ]);

        foreach($self as $id => $receivable) {
            if(($receivable['origin_object_class'] ?? '') === 'timetrack\TimeEntry') {
                if(isset($receivable['time_entry_id']) && is_array($receivable['time_entry_id']) && array_key_exists('vat_rate', $receivable['time_entry_id'])) {
                    $result[$id] = $receivable['time_entry_id']['vat_rate'];
                }
            }
            elseif(($receivable['origin_object_class'] ?? '') === 'sale\subscription\SubscriptionEntry') {
                if(isset($receivable['subscription_entry_id']) && is_array($receivable['subscription_entry_id']) && array_key_exists('vat_rate', $receivable['subscription_entry_id'])) {
                    $result[$id] = $receivable['subscription_entry_id']['vat_rate'];
                }
            }
            else {
                if(isset($receivable['sale_entry_id']) && is_array($receivable['sale_entry_id']) && array_key_exists('vat_rate', $receivable['sale_entry_id'])) {
                    $result[$id] = $receivable['sale_entry_id']['vat_rate'];
                }
            }
        }

        return $result;
    }

    public static function calcQty($self): array {
        $result = [];
        $self->read([
            'origin_object_class',
            'sale_entry_id'          => ['qty'],
            'time_entry_id'          => ['qty'],
            'subscription_entry_id'  => ['qty']
        ]);

        foreach($self as $id => $receivable) {
            if(($receivable['origin_object_class'] ?? '') === 'timetrack\TimeEntry') {
                if(isset($receivable['time_entry_id']) && is_array($receivable['time_entry_id']) && array_key_exists('qty', $receivable['time_entry_id'])) {
                    $result[$id] = $receivable['time_entry_id']['qty'];
                }
            }
            elseif(($receivable['origin_object_class'] ?? '') === 'sale\subscription\SubscriptionEntry') {
                if(isset($receivable['subscription_entry_id']) && is_array($receivable['subscription_entry_id']) && array_key_exists('qty', $receivable['subscription_entry_id'])) {
                    $result[$id] = $receivable['subscription_entry_id']['qty'];
                }
            }
            else {
                if(isset($receivable['sale_entry_id']) && is_array($receivable['sale_entry_id']) && array_key_exists('qty', $receivable['sale_entry_id'])) {
                    $result[$id] = $receivable['sale_entry_id']['qty'];
                }
            }
        }

        return $result;
    }

    public static function calcFreeQty($self): array {
        $result = [];
        $self->read([
            'origin_object_class',
            'sale_entry_id'          => ['free_qty'],
            'time_entry_id'          => ['free_qty'],
            'subscription_entry_id'  => ['free_qty']
        ]);

        foreach($self as $id => $receivable) {
            if(($receivable['origin_object_class'] ?? '') === 'timetrack\TimeEntry') {
                if(isset($receivable['time_entry_id']) && is_array($receivable['time_entry_id']) && array_key_exists('free_qty', $receivable['time_entry_id'])) {
                    $result[$id] = $receivable['time_entry_id']['free_qty'];
                }
            }
            elseif(($receivable['origin_object_class'] ?? '') === 'sale\subscription\SubscriptionEntry') {
                if(isset($receivable['subscription_entry_id']) && is_array($receivable['subscription_entry_id']) && array_key_exists('free_qty', $receivable['subscription_entry_id'])) {
                    $result[$id] = $receivable['subscription_entry_id']['free_qty'];
                }
            }
            else {
                if(isset($receivable['sale_entry_id']) && is_array($receivable['sale_entry_id']) && array_key_exists('free_qty', $receivable['sale_entry_id'])) {
                    $result[$id] = $receivable['sale_entry_id']['free_qty'];
                }
            }
        }

        return $result;
    }

    public static function calcDiscount($self): array {
        $result = [];
        $self->read([
            'origin_object_class',
            'sale_entry_id'          => ['discount'],
            'time_entry_id'          => ['discount'],
            'subscription_entry_id'  => ['discount']
        ]);

        foreach($self as $id => $receivable) {
            if(($receivable['origin_object_class'] ?? '') === 'timetrack\TimeEntry') {
                if(isset($receivable['time_entry_id']) && is_array($receivable['time_entry_id']) && array_key_exists('discount', $receivable['time_entry_id'])) {
                    $result[$id] = $receivable['time_entry_id']['discount'];
                }
            }
            elseif(($receivable['origin_object_class'] ?? '') === 'sale\subscription\SubscriptionEntry') {
                if(isset($receivable['subscription_entry_id']) && is_array($receivable['subscription_entry_id']) && array_key_exists('discount', $receivable['subscription_entry_id'])) {
                    $result[$id] = $receivable['subscription_entry_id']['discount'];
                }
            }
            else {
                if(isset($receivable['sale_entry_id']) && is_array($receivable['sale_entry_id']) && array_key_exists('discount', $receivable['sale_entry_id'])) {
                    $result[$id] = $receivable['sale_entry_id']['discount'];
                }
            }
        }

        return $result;
    }

    public static function calcTotal($self) {
        $result = [];
        $self->read(['qty', 'unit_price', 'free_qty', 'discount']);
        foreach($self as $id => $receivable) {
            $result[$id] = $receivable['unit_price'] * (1.0 - $receivable['discount']) * ($receivable['qty'] - $receivable['free_qty']);
        }

        return $result;
    }

    public static function calcPrice($self) {
        $result = [];
        $self->read(['total', 'vat_rate']);
        $currency_decimal_precision = Setting::get_value('core', 'locale', 'currency.decimal_precision', 2);
        foreach($self as $id => $receivable) {
            $total = (float) $receivable['total'];
            $vat = (float) $receivable['vat_rate'];
            $result[$id] = round($total * (1.0 + $vat), $currency_decimal_precision);
        }

        return $result;
    }

    public static function canupdate($self, $values) {
        $self->read(['status']);
        foreach($self as $receivable) {
            if(array_key_exists('receivables_queue_id', $values)) {
                if($receivable['status'] !== 'pending') {
                    return ['receivables_queue_id' => ['not_allowed' => 'Queue can be modified only when status pending.']];
                }

                if(is_null($values['receivables_queue_id'])) {
                    return ['receivables_queue_id' => ['not_allowed' => 'A receivable must be linked to a queue.']];
                }
            }
        }

        return parent::canupdate($self, $values);
    }
}
