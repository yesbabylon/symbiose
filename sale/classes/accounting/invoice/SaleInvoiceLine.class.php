<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2024
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace sale\accounting\invoice;

use sale\catalog\Product;
use sale\price\Price;
use sale\price\PriceList;

class SaleInvoiceLine extends \finance\accounting\operation\AccountingOperationLine {

    public static function getName() {
        return 'Sale invoice line';
    }

    public static function getDescription() {
        return 'Invoice lines describe the products and quantities that are part of an invoice.';
    }

    public static function getModelTable(): string {
        return 'sale_accounting_invoice_invoiceline';
    }

    public static function getColumns() {
        return [

            'description' => [
                'type'              => 'string',
                'description'       => 'Complementary description of the line (independent from product).'
            ],

            'accounting_operation_id' => [
                'type'              => 'computed',
                'result_type'       => 'many2one',
                'foreign_object'    => 'sale\accounting\invoice\SaleInvoice',
                'description'       => 'Accounting operation represented by the related invoice.',
                'relation'          => ['invoice_id'],
                'readonly'          => true
            ],

            'account_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'finance\accounting\Account',
                'description'       => 'Optional accounting account associated with the invoice line.',
                'ondelete'          => 'null',
                'domain'            => ['is_group_account', '=', false]
            ],

            /**
             * Override Finance Invoice columns
             */

            'name' => [
                'type'              => 'computed',
                'result_type'       => 'string',
                'description'       => 'Default label of the line, based on product (computed).',
                'function'          => 'calcName',
                'store'             => true,
                'instant'           => true
            ],

            'invoice_line_group_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\accounting\invoice\SaleInvoiceLineGroup',
                'description'       => 'Group the line relates to (in turn, groups relate to their invoice).',
                'ondelete'          => 'cascade',
                'domain'            => ['invoice_id', '=', 'object.invoice_id'],
            ],

            'invoice_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\accounting\invoice\SaleInvoice',
                'description'       => 'Invoice the line is related to.',
                'required'          => true,
                'ondelete'          => 'cascade'
            ],

            'unit_price' => [
                'type'              => 'computed',
                'result_type'       => 'float',
                'usage'             => 'amount/money:4',
                'description'       => 'Unit price of the product related to the line.',
                'function'          => 'calcUnitPrice',
                'store'             => true,
                'dependents'        => ['total', 'price', 'invoice_id' => ['total', 'price']]
            ],

            'vat_rate' => [
                'type'              => 'computed',
                'result_type'       => 'float',
                'usage'             => 'amount/rate',
                'description'       => 'VAT rate to be applied.',
                'function'          => 'calcVatRate',
                'store'             => true,
                'default'           => 0.0,
                'dependents'        => ['price', 'invoice_id' => ['price']]
            ],

            'qty' => [
                'type'              => 'float',
                'description'       => 'Quantity of product.',
                'default'           => 0,
                'dependents'        => ['price', 'total', 'invoice_id' => ['total', 'price']]
            ],

            'free_qty' => [
                'type'              => 'integer',
                'description'       => 'Free quantity.',
                'default'           => 0,
                'dependents'        => ['price', 'total', 'invoice_id' => ['total', 'price']]
            ],

            'discount' => [
                'type'              => 'float',
                'usage'             => 'amount/rate',
                'description'       => 'Total amount of discount to apply, if any.',
                'default'           => 0.0,
                'dependents'        => ['price', 'total', 'invoice_id' => ['total', 'price']]
            ],

            'total' => [
                'type'              => 'computed',
                'result_type'       => 'float',
                'usage'             => 'amount/money:4',
                'description'       => 'Total tax-excluded price of the line (computed).',
                'function'          => 'calcTotal',
                'store'             => true
            ],

            'price' => [
                'type'              => 'computed',
                'result_type'       => 'float',
                'usage'             => 'amount/money:2',
                'description'       => 'Final tax-included price of the line (computed).',
                'function'          => 'calcPrice',
                'store'             => true
            ],

            'downpayment_invoice_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\accounting\invoice\SaleInvoice',
                'description'       => 'Downpayment invoice (for invoiced downpayment).'
            ],

            /**
             * Specific sale invoice line columns
             */

            'product_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\catalog\Product',
                'description'       => 'The product (SKU) the line relates to.',
                'required'          => true,
                'dependents'      => ['name']
            ],

            'price_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\price\Price',
                'description'       => 'The price the line relates to (assigned at line creation).',
                'dependents'        => ['vat_rate', 'price', 'total']
            ],

            'receivable_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\receivable\Receivable',
                'description'       => 'Receivable at the origin of the invoice line.'
            ],

            'has_receivable' => [
                'type'              => 'boolean',
                'description'       => 'Was the line generated from a receivable.',
                'default'           => false
            ]

        ];
    }

    public function getIndexes(): array {
        return [];
    }

    public static function getActions() {
        return [];
    }

    public static function onchange($event, $values, $view = null): array {
        $result = [];

        if(isset($event['product_id'])) {
            $product = Product::id($event['product_id'])
                ->read(['name'])
                ->first();

            if(isset($product)) {
                $result['name'] = $product['name'];
            }

            $price_lists_ids = PriceList::search([
                [
                    ['date_from', '<=', time()],
                    ['date_to', '>=', time()],
                    ['status', '=', 'published'],
                ]
            ])
                ->ids();

            $result['price_id'] = Price::search([
                ['product_id', '=', $event['product_id']],
                ['price_list_id', 'in', $price_lists_ids]
            ])
                ->read(['id', 'name', 'price', 'vat_rate'])
                ->first();

            if(isset($result['price_id']['price'])) {
                $result['unit_price'] = $result['price_id']['price'];
            }

            if(isset($result['price_id']['vat_rate'])) {
                $result['vat_rate'] = $result['price_id']['vat_rate'];
            }
        }

        return $result;
    }

    public static function calcName($self): array {
        $result = [];
        $self->read(['product_id' => ['name']]);
        foreach($self as $id => $line) {
            $result[$id] = $line['product_id']['name'];
        }

        return $result;
    }

    public static function calcUnitPrice($self): array {
        $result = [];
        $self->read(['price_id' => ['price']]);
        foreach($self as $id => $line) {
            $result[$id] = $line['price_id']['price'];
        }

        return $result;
    }

    public static function calcVatRate($self): array {
        $result = [];
        $self->read(['price_id' => ['vat_rate']]);
        foreach($self as $id => $line) {
            $result[$id] = 0.0;
            if(isset($line['price_id']['vat_rate'])) {
                $result[$id] = floatval($line['price_id']['vat_rate']);
            }
        }

        return $result;
    }

    public static function calcTotal($self): array {
        $result = [];
        $self->read(['qty', 'unit_price', 'free_qty', 'discount']);
        foreach($self as $id => $line) {
            $result[$id] = $line['unit_price'] * (1.0 - $line['discount']) * ($line['qty'] - $line['free_qty']);
        }

        return $result;
    }

    public static function calcPrice($self): array {
        $result = [];
        $self->read(['total', 'vat_rate']);
        foreach($self as $id => $line) {
            $total = (float) $line['total'];
            $vat = (float) $line['vat_rate'];
            $result[$id] = round($total * (1.0 + $vat), 2);
        }

        return $result;
    }

    public static function cancreate($self, $values): array {
        return [];
    }

    public static function canupdate($self, $values): array {
        $self->read(['has_receivable', 'invoice_id' => ['id', 'status'], 'qty', 'free_qty']);
        $allowed_fields = ['name', 'invoice_line_group_id'];
        foreach($self as $id => $invoiceLine) {
            if($invoiceLine['has_receivable'] && count(array_diff(array_keys($values), $allowed_fields)) > 0) {
                return ['receivable_id' => ['non_editable' => 'Invoice lines generated by receivable cannot be updated.']];
            }

            if(
                isset($invoiceLine['invoice_id']['id'], $values['invoice_id'])
                && $invoiceLine['invoice_id']['id'] !== $values['invoice_id']
            ) {
                return ['invoice_id' => ['non_editable' => 'Line cannot be linked to another invoice after creation.']];
            }

            if($invoiceLine['invoice_id']['status'] !== 'proforma') {
                return ['status' => ['non_editable' => 'Invoice Line can only be updated while its invoice\'s status is proforma.']];
            }

            if(isset($values['invoice_line_group_id'])) {
                $group = SaleInvoiceLineGroup::id($values['invoice_line_group_id'])
                    ->read(['invoice_id'])
                    ->first();

                if($group && $group['invoice_id'] !== $invoiceLine['invoice_id']['id']) {
                    return ['invoice_line_group_id' => ['invalid_param' => 'Group must be linked to same invoice.']];
                }
            }

            if(isset($values['qty'])) {
                $free_qty = $values['free_qty'] ?? $invoiceLine['free_qty'];
                if($free_qty && $values['qty'] <= $free_qty) {
                    return ['qty' => ['must_be_greater_than_free_qty' => 'Quantity must be greater than free quantity.']];
                }
            }

            if(isset($values['free_qty'])) {
                if($values['free_qty'] < 0) {
                    return ['free_qty' => ['must_be_greater_than_or_equal_to_zero' => 'Free quantity must be greater than or equal to 0.']];
                }

                $qty = $values['qty'] ?? $invoiceLine['qty'];
                if($values['free_qty'] && $values['free_qty'] >= $qty) {
                    return ['free_qty' => ['must_be_lower_than_qty' => 'Free quantity must be lower than quantity.']];
                }
            }

            if(isset($values['unit_price']) && $values['unit_price'] <= 0) {
                return ['unit_price' => ['must_be_greater_than_zero' => 'Unit price must be greater than 0.']];
            }

            if(isset($values['discount'])) {
                if($values['discount'] < 0) {
                    return ['discount' => ['must_be_greater_than_zero' => 'Discount must be greater than or equal to 0%.']];
                }
                if($values['discount'] > 0.99) {
                    return ['discount' => ['must_be_lower_than_one' => 'Discount must be lower than 100%.']];
                }
            }
        }

        return [];
    }

    public static function candelete($self): array {
        return [];
    }

    protected static function oncreate($self, $values, $lang) {
    }
}
