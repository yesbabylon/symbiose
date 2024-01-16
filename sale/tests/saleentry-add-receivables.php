<?php

use sale\catalog\Product;
use sale\customer\Customer;
use sale\price\Price;
use sale\SaleEntry;
use sale\receivable\Receivable;
use sale\receivable\ReceivablesQueue;

$tests = [
    '0101' => [
        'description' => 'Tests that action add-receivables throws if empty ids param',
        'return'      => 'integer',
        'expected'    => QN_ERROR_INVALID_PARAM,
        'test'        => function() {
            $error = 0;
            try {
                // Run action
                eQual::run('do', 'sale_saleentry_add-receivables', ['ids' => []]);
            } catch (Exception $e) {
                $error = $e->getCode();
            }

            return $error;
        }
    ],

    '0102' => [
        'description' => 'Tests that action add-receivables throws if some of given ids are not found',
        'arrange'     => function() {
            // Create customer and sale entry

            $customer = Customer::create([
                'name'                => 'Test customer',
                'partner_identity_id' => 0
            ])
                ->read(['id'])
                ->first();
    
            $sale_entry = SaleEntry::create([
                'customer_id' => $customer['id']
            ])
                ->read(['id'])
                ->first();
            
            return $sale_entry['id'];
        },
        'act'         => function($sale_entry_id) {
            $error = 0;

            try {
                // Run action
                eQual::run(
                    'do',
                    'sale_saleentry_add-receivables',
                    ['ids' => [$sale_entry_id, -1]]
                );
            } catch(Exception $e) {
                $error = $e->getCode();
            }
            
            return $error;
        },
        'assert'      => function($error) {
            return QN_ERROR_UNKNOWN_OBJECT === $error;
        },
        'rollback'    => function() {
            // Remove customer and sale entry

            $customer = Customer::search(['name', '=', 'Test customer'])
                ->read(['id'])
                ->first();

            Customer::id($customer['id'])->delete(true);
            SaleEntry::search(['customer_id', '=', $customer['id']])->delete(true);
        }
    ],

    '0103' => [
        'description' => 'Tests that action add-receivables create receivables for entries if does not exist',
        'arrange'     => function() {
            // Create customer, price, product and two sale entries

            $customer = Customer::create([
                'name'                => 'Test customer',
                'partner_identity_id' => 0
            ])
                ->read(['id'])
                ->first();

            $product_one = Product::create([
                'name'             => 'Test product (1111.111111.11111111.111)',
                'label'            => 'Test product',
                'sku'              => '1111.111111.11111111.111',
                'product_model_id' => 0
            ])
                ->read(['id'])
                ->first();

            $price_one = Price::create([
                'price'         => 29.99,
                'vat_rate'      => 0.99,
                'price_list_id' => 0,
                'product_id'    => $product_one['id']
            ])
                ->read(['id'])
                ->first();

            $sale_entry_one = SaleEntry::create([
                'product_id'  => $product_one['id'],
                'customer_id' => $customer['id'],
                'price_id'    => $price_one['id'],
                'unit_price'  => 25.99
            ])
                ->read(['id'])
                ->first();

            $product_two = Product::create([
                'name'             => 'Test product (2222.22222.22222222.222)',
                'label'            => 'Test product',
                'sku'              => '2222.22222.22222222.222',
                'product_model_id' => 0
            ])
                ->read(['id'])
                ->first();

            $price_two = Price::create([
                'price'         => 40.0,
                'vat_rate'      => 0.99,
                'price_list_id' => 0,
                'product_id'    => $product_two['id']
            ])
                ->read(['id'])
                ->first();

            $sale_entry_two = SaleEntry::create([
                'product_id'  => $product_two['id'],
                'customer_id' => $customer['id'],
                'price_id'    => $price_two['id'],
                'unit_price'  => 40.0
            ])
                ->read(['id'])
                ->first();

            return [$sale_entry_one['id'], $sale_entry_two['id']];
        },
        'act'         => function($sale_entry_ids) {
            // Run action
            eQual::run(
                'do',
                'sale_saleentry_add-receivables',
                ['ids' => $sale_entry_ids]
            );
        },
        'assert'      => function() {
            // Assert that receivable created with the correct values

            $customer = Customer::search(['name', '=', 'Test customer'])
                ->read(['id'])
                ->first();

            $entries = SaleEntry::search(['customer_id', '=', $customer['id']])
                ->read(['price_id', 'unit_price', 'qty', 'product_id', 'receivable_id'])
                ->toArray();

            $price_one = Price::id($entries[0]['price_id'])
                ->read(['id', 'vat_rate'])
                ->first();

            $receivable_one = Receivable::id($entries[0]['receivable_id'])
                ->read([
                    'price_id',
                    'vat_rate',
                    'unit_price',
                    'qty',
                    'product_id',
                    'description'
                ])
                ->first();

            $price_two = Price::id($entries[1]['price_id'])
                ->read(['id', 'vat_rate'])
                ->first();

            $receivable_two = Receivable::id($entries[1]['receivable_id'])
                ->read([
                    'price_id',
                    'vat_rate',
                    'unit_price',
                    'qty',
                    'product_id',
                    'description'
                ])
                ->first();

            $is_receivable_one_ok = $receivable_one['price_id'] === $price_one['id']
                && $receivable_one['vat_rate'] === $price_one['vat_rate']
                && $receivable_one['unit_price'] === $entries[0]['unit_price']
                && $receivable_one['qty'] === $entries[0]['qty']
                && $receivable_one['product_id'] === $entries[0]['product_id']
                && $receivable_one['description'] === 'Reference Sale entry product.';

            $is_receivable_two_ok = $receivable_two['price_id'] === $price_two['id']
                && $receivable_two['vat_rate'] === $price_two['vat_rate']
                && $receivable_two['unit_price'] === $entries[1]['unit_price']
                && $receivable_two['qty'] === $entries[1]['qty']
                && $receivable_two['product_id'] === $entries[1]['product_id']
                && $receivable_two['description'] === 'Reference Sale entry product.';

            return $is_receivable_one_ok && $is_receivable_two_ok;
        },
        'rollback'    => function() {
            // Remove customer, receivable queue, price, product, sale entry and receivable

            $customer = Customer::search(['name', '=', 'Test customer'])
                ->read(['id'])
                ->first();

            $entries = SaleEntry::search(['customer_id', '=', $customer['id']])
                ->read(['price_id', 'product_id'])
                ->toArray();

            Customer::id($customer['id'])->delete(true);
            ReceivablesQueue::search(['customer_id', '=', $customer['id']])->delete(true);

            Price::id($entries[0]['price_id'])->delete(true);
            Product::id($entries[0]['product_id'])->delete(true);
            Receivable::search(['product_id', '=', $entries[0]['product_id']])->delete(true);

            Price::id($entries[1]['price_id'])->delete(true);
            Product::id($entries[1]['product_id'])->delete(true);
            Receivable::search(['product_id', '=', $entries[1]['product_id']])->delete(true);

            SaleEntry::search(['customer_id', '=', $customer['id']])->delete(true);
        }
    ],
];
