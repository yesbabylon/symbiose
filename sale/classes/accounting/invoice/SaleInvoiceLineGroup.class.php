<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2024
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace sale\accounting\invoice;

use core\setting\Setting;
use equal\orm\Model;

class SaleInvoiceLineGroup extends Model {

    public static function getName() {
        return 'Sale invoice line group';
    }

    public static function getDescription() {
        return 'Invoice line groups are related to an invoice and are meant to visually join several invoice lines.';
    }

    public static function getColumns() {
        return [

            'name' => [
                'type'              => 'string',
                'description'       => 'Label of the group (displayed on invoice).',
                'required'          => true
            ],

            'description' => [
                'type'              => 'string',
                'description'       => 'Short description of the group (displayed on invoice).'
            ],

            'order' => [
                'type'              => 'integer',
                'description'       => 'Order by which the group has to be sorted when presented.',
                'default'           => 0
            ],

            'invoice_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\accounting\invoice\SaleInvoice',
                'description'       => 'Invoice the line group is related to.',
                'required'          => true,
                'ondelete'          => 'cascade'
            ],

            'invoice_lines_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'sale\accounting\invoice\SaleInvoiceLine',
                'foreign_field'     => 'invoice_line_group_id',
                'description'       => 'Detailed lines of the group.',
                'ondetach'          => 'delete',
                'dependents'        => ['total', 'price', 'invoice_id' => ['total', 'price']]
            ],

            'total' => [
                'type'              => 'computed',
                'result_type'       => 'float',
                'usage'             => 'amount/money:4',
                'description'       => 'Total tax-excluded price for all lines (computed).',
                'function'          => 'calcTotal',
                'store'             => true
            ],

            'price' => [
                'type'              => 'computed',
                'result_type'       => 'float',
                'description'       => 'Final tax-included price for all lines (computed).',
                'function'          => 'calcPrice',
                'store'             => true
            ],

            'is_visible' => [
                'type'              => 'boolean',
                'description'       => 'Show group on the invoice.',
                'help'              => 'The group can be shown or hidden on the invoice.',
                'default'           => true
            ]

        ];
    }

    public static function calcTotal($self) {
        $result = [];
        $self->read(['invoice_lines_ids' => ['total']]);
        foreach($self as $id => $group) {
            $result[$id] = array_reduce(
                $group['invoice_lines_ids']->toArray(),
                function($carry, $line) {
                    return $carry + $line['total'];
                },
                0
            );
        }

        return $result;
    }

    public static function calcPrice($self) {
        $result = [];
        $self->read(['invoice_lines_ids' => ['total', 'vat_rate']]);
        $currency_decimal_precision = Setting::get_value('core', 'locale', 'currency.decimal_precision');
        foreach($self as $id => $group) {
            $map_taxable_amounts = [];
            foreach($group['invoice_lines_ids'] as $line) {
                $vat_rate = (float) $line['vat_rate'];
                $tax_ref = strval($vat_rate);
                if(!isset($map_taxable_amounts[$tax_ref])) {
                    $map_taxable_amounts[$tax_ref] = [
                        'vat_rate'       => $vat_rate,
                        'taxable_amount' => 0.0
                    ];
                }
                $map_taxable_amounts[$tax_ref]['taxable_amount'] += round(
                    (float) $line['total'],
                    $currency_decimal_precision
                );
            }

            $price = 0.0;
            foreach($map_taxable_amounts as $taxable_amount) {
                $total = round($taxable_amount['taxable_amount'], $currency_decimal_precision);
                $price += $total + round(
                    $total * $taxable_amount['vat_rate'],
                    $currency_decimal_precision
                );
            }
            $result[$id] = round($price, $currency_decimal_precision);
        }

        return $result;
    }
}
