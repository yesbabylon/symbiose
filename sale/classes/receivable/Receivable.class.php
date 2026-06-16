<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2024
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace sale\receivable;

use core\setting\Setting;
use equal\orm\Model;
use sale\SaleEntry;

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
                'domain'            => ['object_class', '=', 'sale\SaleEntry'],
                'visible'           => ['origin_object_class', '=', 'sale\SaleEntry']
            ],

            'time_entry_id' => [
                'type'              => 'computed',
                'result_type'       => 'many2one',
                'foreign_object'    => 'timetrack\TimeEntry',
                'relation'          => ['origin_object_id'],
                'readonly'          => true,
                'domain'            => ['object_class', '=', 'timetrack\TimeEntry'],
                'visible'           => ['origin_object_class', '=', 'timetrack\TimeEntry']
            ],

            'subscription_entry_id' => [
                'type'              => 'computed',
                'result_type'       => 'many2one',
                'foreign_object'    => 'sale\subscription\SubscriptionEntry',
                'relation'          => ['origin_object_id'],
                'readonly'          => true,
                'domain'            => ['object_class', '=', 'sale\subscription\SubscriptionEntry'],
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
                'dependents'        => ['total', 'price'],
                'readonly'          => true
            ],

            'vat_rate' => [
                'type'              => 'computed',
                'result_type'       => 'float',
                'usage'             => 'amount/rate',
                'description'       => 'VAT rate to be applied.',
                'function'          => 'calcVatRate',
                'store'             => true,
                'dependents'        => ['price'],
                'readonly'          => true
            ],

            'qty' => [
                'type'              => 'computed',
                'result_type'       => 'float',
                'description'       => 'Quantity of product.',
                'function'          => 'calcQty',
                'store'             => true,
                'dependents'        => ['total', 'price'],
                'readonly'          => true
            ],

            'free_qty' => [
                'type'              => 'computed',
                'result_type'       => 'integer',
                'description'       => 'Free quantity of product, if any.',
                'function'          => 'calcFreeQty',
                'store'             => true,
                'dependents'        => ['total', 'price'],
                'readonly'          => true
            ],

            'discount' => [
                'type'              => 'computed',
                'result_type'       => 'float',
                'usage'             => 'amount/rate',
                'description'       => 'Total amount of discount to apply, if any.',
                'function'          => 'calcDiscount',
                'store'             => true,
                'dependents'        => ['total', 'price'],
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

    protected static function calcName($self) {
        $result = [];
        $self->read(['origin_object_id']);

        foreach($self as $id => $receivable) {
            $saleEntry = SaleEntry::id($receivable['origin_object_id'])->read(['name'])->first();
            if($saleEntry) {
                $result[$id] = $saleEntry['name'];
            }
        }

        return $result;
    }

    protected static function calcDescription($self): array {
        $result = [];
        $self->read(['origin_object_id']);

        foreach($self as $id => $receivable) {
            $saleEntry = SaleEntry::id($receivable['origin_object_id'])->read(['description'])->first();
            if($saleEntry) {
                $result[$id] = $saleEntry['description'];
            }
        }

        return $result;
    }

    protected static function calcCustomerId($self): array {
        $result = [];
        $self->read(['origin_object_id']);

        foreach($self as $id => $receivable) {
            $saleEntry = SaleEntry::id($receivable['origin_object_id'])->read(['customer_id'])->first();
            if($saleEntry) {
                $result[$id] = $saleEntry['customer_id'];
            }
        }

        return $result;
    }

    protected static function calcProductId($self): array {
        $result = [];
        $self->read(['origin_object_id']);

        foreach($self as $id => $receivable) {
            $saleEntry = SaleEntry::id($receivable['origin_object_id'])->read(['product_id'])->first();
            if($saleEntry) {
                $result[$id] = $saleEntry['product_id'];
            }
        }

        return $result;
    }

    protected static function calcPriceId($self): array {
        $result = [];
        $self->read(['origin_object_id']);

        foreach($self as $id => $receivable) {
            $saleEntry = SaleEntry::id($receivable['origin_object_id'])->read(['price_id'])->first();
            if($saleEntry) {
                $result[$id] = $saleEntry['price_id'];
            }
        }

        return $result;
    }

    protected static function calcUnitPrice($self): array {
        $result = [];
        $self->read(['origin_object_id']);

        foreach($self as $id => $receivable) {
            $saleEntry = SaleEntry::id($receivable['origin_object_id'])->read(['unit_price'])->first();
            if($saleEntry) {
                $result[$id] = $saleEntry['unit_price'];
            }
        }

        return $result;
    }

    protected static function calcVatRate($self): array {
        $result = [];
        $self->read(['origin_object_id']);

        foreach($self as $id => $receivable) {
            $saleEntry = SaleEntry::id($receivable['origin_object_id'])->read(['vat_rate'])->first();
            if($saleEntry) {
                $result[$id] = $saleEntry['vat_rate'];
            }
        }

        return $result;
    }

    protected static function calcQty($self): array {
        $result = [];
        $self->read(['origin_object_id']);

        foreach($self as $id => $receivable) {
            $saleEntry = SaleEntry::id($receivable['origin_object_id'])->read(['qty'])->first();
            if($saleEntry) {
                $result[$id] = $saleEntry['qty'];
            }
        }

        return $result;
    }

    protected static function calcFreeQty($self): array {
        $result = [];
        $self->read(['origin_object_id']);

        foreach($self as $id => $receivable) {
            $saleEntry = SaleEntry::id($receivable['origin_object_id'])->read(['free_qty'])->first();
            if($saleEntry) {
                $result[$id] = $saleEntry['free_qty'];
            }
        }

        return $result;
    }

    protected static function calcDiscount($self): array {
        $result = [];
        $self->read(['origin_object_id']);

        foreach($self as $id => $receivable) {
            $saleEntry = SaleEntry::id($receivable['origin_object_id'])->read(['discount'])->first();
            if($saleEntry) {
                $result[$id] = $saleEntry['discount'];
            }
        }

        return $result;
    }

    protected static function calcTotal($self) {
        $result = [];
        $self->read(['unit_price', 'discount', 'qty', 'free_qty']);
        foreach($self as $id => $receivable) {
            if(!isset($receivable['unit_price'], $receivable['qty'])) {
                continue;
            }

            $unit_price = (float) $receivable['unit_price'];
            $discount = (float) ($receivable['discount'] ?? 0.0);
            $qty = (float) $receivable['qty'];
            $free_qty = (float) ($receivable['free_qty'] ?? 0.0);

            $result[$id] = $unit_price * (1.0 - $discount) * ($qty - $free_qty);
        }

        return $result;
    }

    protected static function calcPrice($self) {
        $result = [];
        $self->read(['total', 'vat_rate']);
        $currency_decimal_precision = Setting::get_value('core', 'locale', 'currency.decimal_precision', 2);

        foreach($self as $id => $receivable) {
            if(!isset($receivable['total'])) {
                continue;
            }

            $total = (float) $receivable['total'];
            $vat = (float) ($receivable['vat_rate'] ?? 0.0);

            $result[$id] = round($total * (1.0 + $vat), $currency_decimal_precision);
        }

        return $result;
    }

    protected static function canupdate($self, $values) {
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
