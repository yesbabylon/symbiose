<?php

use core\setting\Setting;
use sale\accounting\invoice\SaleInvoice;
use sale\accounting\invoice\SaleInvoiceLine;
use sale\customer\Customer;
use sale\price\Price;
use sale\price\PriceList;

$tests = [
    '0101' => [
        'description' => 'Invoice rendering uses the invoice VAT rounding strategy.',
        'arrange'     => function() {
            $suffix = uniqid();

            $customer = Customer::create([
                    'name'                => "Test invoice rendering customer {$suffix}",
                    'partner_identity_id' => 0
                ])
                ->read(['id'])
                ->first(true);

            $price_list = PriceList::create([
                    'name'      => "Test invoice rendering price list {$suffix}",
                    'date_from' => strtotime('-1 day'),
                    'date_to'   => strtotime('+1 day'),
                    'status'    => 'published'
                ])
                ->read(['id'])
                ->first(true);

            $price = Price::create([
                    'price'              => 80.50,
                    'price_type'         => 'direct',
                    'price_list_id'      => $price_list['id'],
                    'product_id'         => 1,
                    'accounting_rule_id' => 1
                ])
                ->read(['id', 'vat_rate'])
                ->first(true);

            $invoice = SaleInvoice::create([
                    'customer_id' => $customer['id']
                ])
                ->read(['id'])
                ->first(true);

            $invoice_lines_ids = [];
            foreach([1.75, 1.75, 2.50, 6.00, 0.25] as $qty) {
                $invoice_line = SaleInvoiceLine::create([
                        'invoice_id' => $invoice['id'],
                        'product_id' => 1,
                        'price_id'   => $price['id'],
                        'qty'        => $qty
                    ])
                    ->read(['id'])
                    ->first(true);

                $invoice_lines_ids[] = $invoice_line['id'];
            }

            $previous_currency_decimal_precision = Setting::get_value('core', 'locale', 'currency.decimal_precision');
            Setting::set_value('core', 'locale', 'currency.decimal_precision', 2);

            SaleInvoiceLine::ids($invoice_lines_ids)
                ->update([
                    'total'    => null,
                    'vat_rate' => null
                ])
                ->read(['total', 'vat_rate']);

            SaleInvoice::id($invoice['id'])
                ->do('refresh_prices');

            $invoice = SaleInvoice::id($invoice['id'])
                ->read(['total', 'price', 'tax_lines'])
                ->first(true);

            return [
                'customer_id'   => $customer['id'],
                'price_list_id' => $price_list['id'],
                'price_id'      => $price['id'],
                'invoice_id'    => $invoice['id'],
                'total'         => $invoice['total'],
                'price'         => $invoice['price'],
                'tax_lines'     => $invoice['tax_lines'],
                'vat_rate'      => $price['vat_rate'],
                'previous_currency_decimal_precision' => $previous_currency_decimal_precision
            ];
        },
        'act'         => function($args) {
            $html = eQual::run('get', 'sale_accounting_invoice_render-html', [
                'id'    => $args['invoice_id'],
                'lang'  => 'fr',
                'debug' => false
            ]);

            $expected_total = number_format((float) $args['total'], 2, ',', '.').' €';
            $expected_vat = number_format((float) $args['price'] - (float) $args['total'], 2, ',', '.').' €';
            $expected_price = number_format((float) $args['price'], 2, ',', '.').' €';
            $tax_lines = json_decode($args['tax_lines'], true, 512, JSON_THROW_ON_ERROR);
            $tax_ref = strval($args['vat_rate']);

            $args['has_expected_total'] = strpos($html, $expected_total) !== false;
            $args['has_expected_vat'] = strpos($html, $expected_vat) !== false;
            $args['has_expected_price'] = strpos($html, $expected_price) !== false;
            $args['tax_line'] = $tax_lines[$tax_ref] ?? null;

            return $args;
        },
        'assert'      => function($args) {
            return (float) $args['vat_rate'] === 0.21
                && (float) $args['total'] === 986.14
                && (float) $args['price'] === 1193.23
                && (float) $args['tax_line']['vat_rate'] === (float) $args['vat_rate']
                && (float) $args['tax_line']['taxable_amount'] === (float) $args['total']
                && (float) $args['tax_line']['tax_amount'] === round((float) $args['price'] - (float) $args['total'], 2)
                && $args['has_expected_total']
                && $args['has_expected_vat']
                && $args['has_expected_price'];
        },
        'rollback'    => function($args) {
            Setting::set_value(
                'core',
                'locale',
                'currency.decimal_precision',
                $args['previous_currency_decimal_precision']
            );

            SaleInvoice::id($args['invoice_id'])
                ->delete(true);

            Price::id($args['price_id'])
                ->delete(true);

            PriceList::id($args['price_list_id'])
                ->delete(true);

            Customer::id($args['customer_id'])
                ->delete(true);
        }
    ]
];
