<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2024
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

namespace finance\accounting;

use equal\orm\Model;

class InvoiceLine extends Model {

    public static function getName() {
        return 'Invoice line';
    }

    public static function getDescription() {
        return 'Invoice lines describe the products and quantities that are part of an invoice.';
    }

    public static function getColumns() {
        return [
            'name' => [
                'type'              => 'string',
                'description'       => 'Default label of the line.',
                'required'          => true
            ],

            'description' => [
                'type'              => 'string',
                'description'       => 'Complementary description of the line (independent from product).'
            ],

            'invoice_line_group_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'finance\accounting\InvoiceLineGroup',
                'description'       => 'Group related (to their invoice) for lines.',
                'ondelete'          => 'cascade',
                'domain'            => ['invoice_id', '=', 'object.invoice_id']
            ],

            'invoice_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'finance\accounting\Invoice',
                'description'       => 'Invoice the line is related to.',
                'required'          => true,
                'onupdate'          => 'onupdateInvoiceId',
                'ondelete'          => 'cascade'
            ],

            'unit_price' => [
                'type'              => 'float',
                'usage'             => 'amount/money:4',
                'description'       => 'Unit price of the product related to the line.'
            ],

            'vat_rate' => [
                'type'              => 'float',
                'usage'             => 'amount/rate',
                'description'       => 'VAT rate to be applied.',
                'default'           => 0.0,
                'onupdate'          => 'onupdateVatRate'
            ],

            'qty' => [
                'type'              => 'float',
                'description'       => 'Quantity of product.',
                'default'           => 0,
                'onupdate'          => 'onupdateQty'
            ],

            'free_qty' => [
                'type'              => 'integer',
                'description'       => 'Free quantity.',
                'default'           => 0,
                'onupdate'          => 'onupdateFreeQty'
            ],

            // #memo - important: to allow maximum flexibility, percent values can hold 4 decimal digits (must not be rounded, except for display)
            'discount' => [
                'type'              => 'float',
                'usage'             => 'amount/rate',
                'description'       => 'Total amount of discount to apply, if any.',
                'default'           => 0.0,
                'onupdate'          => 'onupdateDiscount'
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
                'foreign_object'    => 'finance\accounting\Invoice',
                'description'       => 'Downpayment invoice (for invoiced downpayment).'
            ]

        ];
    }

    public static function calcTotal($self) {
        $result = [];
        $self->read(['qty','unit_price','free_qty','discount']);
        foreach($self as $id => $line) {
            $result[$id] = $line['unit_price'] * (1.0 - $line['discount']) * ($line['qty'] - $line['free_qty']);
        }
        return $result;
    }

    public static function calcPrice($self) {
        $result = [];
        $self->read(['total','vat_rate']);
        foreach($self as $id => $line) {
            $total = (float) $line['total'];
            $vat = (float) $line['vat_rate'];
            $result[$id] = round($total * (1.0 + $vat), 2);
        }
        return $result;
    }

    public static function onupdateInvoiceId($self) {
        $self->do('reset_invoice_prices');
    }

    public static function onupdateVatRate($self) {
        $self->do('reset_prices');
    }

    public static function onupdateQty($self) {
        $self->do('reset_prices');
    }

    public static function onupdateFreeQty($self) {
        $self->do('reset_prices');
    }

    public static function onupdateDiscount($self) {
        $self->do('reset_prices');
    }

    public static function getActions() {
        return [
            'reset_prices' => [
                'description'   => 'Resets price and total computed fields of the invoice line and the invoice.',
                'policies'      => [],
                'function'      => 'doResetPrices'
            ],
            'reset_invoice_prices' => [
                'description'   => 'Resets price and total computed fields of the invoice.',
                'policies'      => [],
                'function'      => 'doResetInvoicePrices'
            ]
        ];
    }

    public static function doResetPrices($self) {
        $self->update([
            'price' => null,
            'total' => null
        ]);

        $self->do('reset_invoice_prices');
    }

    public static function doResetInvoicePrices($self) {
        $self->read(['invoice_id']);

        Invoice::ids(array_column($self->get(true), 'invoice_id'))
            ->update([
                'price' => null,
                'total' => null
            ]);
    }

    public static function canupdate($self, $values): array {
        $self->read(['invoice_id' => ['status'], 'qty', 'free_qty']);
        foreach($self as $invoice_line) {
            if(
                isset($invoice_line['invoice_id']['id'], $values['invoice_id'])
                && $invoice_line['invoice_id']['id'] !== $values['invoice_id']
            ) {
                return ['invoice_id' => ['non_editable' => 'Line cannot be linked to another invoice after creation.']];
            }

            if($invoice_line['invoice_id']['status'] !== 'proforma') {
                return ['status' => ['non_editable' => 'Invoice Line can only be updated while its invoice\'s status is proforma.']];
            }

            if(isset($values['invoice_line_group_id'])) {
                $group = InvoiceLineGroup::id($values['invoice_line_group_id'])
                    ->read(['invoice_id'])
                    ->first();

                if($group['invoice_id'] !== $invoice_line['invoice_id']['id']) {
                    return ['invoice_line_group_id' => ['invalid_param' => 'Group must be linked to same invoice.']];
                }
            }

            if(isset($values['qty'])) {
                if($values['qty'] <= 0) {
                    return ['qty' => ['must_be_greater_than_zero' => 'Quantity must be greater than 0.']];
                }

                $free_qty = $values['free_qty'] ?? $invoice_line['free_qty'];
                if($values['qty'] <= $free_qty) {
                    return ['qty' => ['must_be_greater_than_free_qty' => 'Quantity must be greater than free quantity.']];
                }
            }

            if(isset($values['free_qty'])) {
                if($values['free_qty'] < 0) {
                    return ['free_qty' => ['must_be_greater_than_or_equal_to_zero' => 'Free quantity must be greater than or equal to 0.']];
                }

                $qty = $values['qty'] ?? $invoice_line['qty'];
                if($values['free_qty'] >= $qty) {
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

        return parent::canupdate($self, $values);
    }
}