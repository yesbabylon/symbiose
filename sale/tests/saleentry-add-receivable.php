<?php

use sale\SaleEntry;
use sale\receivable\ReceivablesQueue;
use sale\customer\Customer;
use sale\price\Price;
use sale\catalog\Product;
use sale\receivable\Receivable;

$tests = [
    '0101' => [
        'description' => 'Tests that action add-receivable throws if sale entry does not exist',
        'return'      => 'integer',
        'expected'    => QN_ERROR_UNKNOWN_OBJECT,
        'test'        => function() {
            $error = 0;
            try {
                // Run action
                eQual::run('do', 'sale_saleentry_add-receivable', ['id' => '-1']);
            } catch (Exception $e) {
                $error = $e->getCode();
            }

            return $error;
        }
    ],

    '0102' => [
        'description' => 'Tests that action add-receivable throws if price does not exist',
        'arrange'     => function() {
            $customer = Customer::create([
                'name' => 'Test customer',
                'partner_identity_id' => 0
            ])
                ->read(['id'])
                ->first();

            $sale_entry = SaleEntry::create([
                'customer_id' => $customer['id'],
                'price_id'    => '-1'
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
                    'sale_saleentry_add-receivable',
                    ['id' => $sale_entry_id]
                );
            } catch (Exception $e) {
                $error = $e->getCode();
            }

            return $error;
        },
        'assert'      => function($error) {
            return QN_ERROR_UNKNOWN_OBJECT === $error;
        },
        'rollback'    => function() {
            // Remove customer, receivable queue and sale entry

            $customer = Customer::search(['name', '=', 'Test customer'])
                ->read(['id'])
                ->first();

            Customer::id($customer['id'])->delete(true);
            ReceivablesQueue::search(['customer_id', '=', $customer['id']])->delete(true);
            SaleEntry::search(['customer_id', '=', $customer['id']])->delete(true);
        }
    ],

    '0103' => [
        'description' => 'Tests that action add-receivable create a queue for customer if does not exist',
        'arrange'     => function() {
            // Create customer, price, product and sale entry

            $customer = Customer::create([
                'name'                => 'Test customer',
                'partner_identity_id' => 0
            ])
                ->read(['id'])
                ->first();

            $price = Price::create([
                'price'         => 29.99,
                'vat_rate'      => 0.99,
                'price_list_id' => 0,
                'product_id'    => 0
            ])
                ->read(['id'])
                ->first();

            $product = Product::create([
                'name'             => 'Test product (1111.111111.11111111.111)',
                'label'            => 'Test product',
                'sku'              => '1111.111111.11111111.111',
                'product_model_id' => 0
            ])
                ->read(['id'])
                ->first();

            $sale_entry = SaleEntry::create([
                'product_id'  => $product['id'],
                'customer_id' => $customer['id'],
                'price_id'    => $price['id']
            ])
                ->read(['id'])
                ->first();

            return $sale_entry['id'];
        },
        'act'         => function($sale_entry_id) {
            // Run action
            eQual::run(
                'do',
                'sale_saleentry_add-receivable',
                ['id' => $sale_entry_id]
            );
        },
        'assert'      => function() {
            // Assert that a receivable queue is created for the customer

            $customer = Customer::search(['name', '=', 'Test customer'])
                ->read(['id'])
                ->first();

            $queues = ReceivablesQueue::search(['customer_id', '=', $customer['id']])
                ->ids();

            return count($queues) === 1;
        },
        'rollback'    => function() {
            // Remove customer, receivable queue, price, product, sale entry and receivable

            $customer = Customer::search(['name', '=', 'Test customer'])
                ->read(['id'])
                ->first();

            $entry = SaleEntry::search(['customer_id', '=', $customer['id']])
                ->read(['price_id', 'product_id'])
                ->first();

            Customer::id($customer['id'])->delete(true);
            ReceivablesQueue::search(['customer_id', '=', $customer['id']])->delete(true);
            Price::id($entry['price_id'])->delete(true);
            Product::id($entry['product_id'])->delete(true);
            SaleEntry::search(['customer_id', '=', $customer['id']])->delete(true);
            Receivable::search(['product_id', '=', $entry['product_id']])->delete(true);
        }
    ],

    '0104' => [
        'description' => 'Tests that action add-receivable does not create a queue for customer if already exist',
        'arrange'     => function() {
            // Create customer, receivable queue, price, product and sale entry

            $customer = Customer::create([
                'name'                => 'Test customer',
                'partner_identity_id' => 0
            ])
                ->read(['id'])
                ->first();

            ReceivablesQueue::create([
                'customer_id' => $customer['id']
            ])
                ->read(['id'])
                ->first();

            $price = Price::create([
                'price'         => 29.99,
                'vat_rate'      => 0.99,
                'price_list_id' => 0,
                'product_id'    => 0
            ])
                ->read(['id'])
                ->first();

            $product = Product::create([
                'name'             => 'Test product (1111.111111.11111111.111)',
                'label'            => 'Test product',
                'sku'              => '1111.111111.11111111.111',
                'product_model_id' => 0
            ])
                ->read(['id'])
                ->first();

            $sale_entry = SaleEntry::create([
                'product_id'  => $product['id'],
                'customer_id' => $customer['id'],
                'price_id'    => $price['id']
            ])
                ->read(['id'])
                ->first();

            return $sale_entry['id'];
        },
        'act'         => function($sale_entry_id) {
            // Run action
            eQual::run(
                'do',
                'sale_saleentry_add-receivable',
                ['id' => $sale_entry_id]
            );
        },
        'assert'      => function() {
            // Assert that a receivable queue is not created for the customer because one already exist

            $customer = Customer::search(['name', '=', 'Test customer'])
                ->read(['id'])
                ->first();

            $queues = ReceivablesQueue::search(['customer_id', '=', $customer['id']])
                ->ids();

            return count($queues) === 1;
        },
        'rollback'    => function() {
            // Remove customer, receivable queue, price, product, sale entry and receivable

            $customer = Customer::search(['name', '=', 'Test customer'])
                ->read(['id'])
                ->first();

            $entry = SaleEntry::search(['customer_id', '=', $customer['id']])
                ->read(['price_id', 'product_id'])
                ->first();

            Customer::id($customer['id'])->delete(true);
            ReceivablesQueue::search(['customer_id', '=', $customer['id']])->delete(true);
            Price::id($entry['price_id'])->delete(true);
            Product::id($entry['product_id'])->delete(true);
            SaleEntry::search(['customer_id', '=', $customer['id']])->delete(true);
            Receivable::search(['product_id', '=', $entry['product_id']])->delete(true);
        }
    ],

    '0105' => [
        'description' => 'Tests that action add-receivable create a receivable for entry if does not exist',
        'arrange'     => function() {
            // Create customer, price, product and sale entry

            $customer = Customer::create([
                'name'                => 'Test customer',
                'partner_identity_id' => 0
            ])
                ->read(['id'])
                ->first();

            $price = Price::create([
                'price'         => 29.99,
                'vat_rate'      => 0.99,
                'price_list_id' => 0,
                'product_id'    => 0
            ])
                ->read(['id'])
                ->first();

            $product = Product::create([
                'name'             => 'Test product (1111.111111.11111111.111)',
                'label'            => 'Test product',
                'sku'              => '1111.111111.11111111.111',
                'product_model_id' => 0
            ])
                ->read(['id'])
                ->first();

            $sale_entry = SaleEntry::create([
                'product_id'  => $product['id'],
                'customer_id' => $customer['id'],
                'price_id'    => $price['id'],
                'unit_price'  => 25.99
            ])
                ->read(['id'])
                ->first();

            return $sale_entry['id'];
        },
        'act'         => function($sale_entry_id) {
            // Run action
            eQual::run(
                'do',
                'sale_saleentry_add-receivable',
                ['id' => $sale_entry_id]
            );
        },
        'assert'      => function() {
            // Assert that receivable created with the correct values

            $customer = Customer::search(['name', '=', 'Test customer'])
                ->read(['id'])
                ->first();

            $entry = SaleEntry::search(['customer_id', '=', $customer['id']])
                ->read(['price_id', 'unit_price', 'qty', 'product_id', 'receivable_id'])
                ->first();

            $price = Price::id($entry['price_id'])
                ->read(['id', 'vat_rate'])
                ->first();

            $receivable = Receivable::id($entry['receivable_id'])
                ->read([
                    'price_id',
                    'vat_rate',
                    'unit_price',
                    'qty',
                    'product_id',
                    'description'
                ])
                ->first();

            return $receivable['price_id'] === $price['id']
                && $receivable['vat_rate'] === $price['vat_rate']
                && $receivable['unit_price'] === $entry['unit_price']
                && $receivable['qty'] === $entry['qty']
                && $receivable['product_id'] === $entry['product_id']
                && $receivable['description'] === 'Reference Sale entry product.';
        },
        'rollback'    => function() {
            // Remove customer, receivable queue, price, product, sale entry and receivable

            $customer = Customer::search(['name', '=', 'Test customer'])
                ->read(['id'])
                ->first();

            $entry = SaleEntry::search(['customer_id', '=', $customer['id']])
                ->read(['price_id', 'product_id'])
                ->first();

            Customer::id($customer['id'])->delete(true);
            ReceivablesQueue::search(['customer_id', '=', $customer['id']])->delete(true);
            Price::id($entry['price_id'])->delete(true);
            Product::id($entry['product_id'])->delete(true);
            SaleEntry::search(['customer_id', '=', $customer['id']])->delete(true);
            Receivable::search(['product_id', '=', $entry['product_id']])->delete(true);
        }
    ],

    '0106' => [
        'description' => 'Tests that action add-receivable pass entry has_receivable to true',
        'arrange'     => function() {
            // Create customer, price, product and sale entry

            $customer = Customer::create([
                'name'                => 'Test customer',
                'partner_identity_id' => 0
            ])
                ->read(['id'])
                ->first();

            $price = Price::create([
                'price'         => 29.99,
                'vat_rate'      => 0.99,
                'price_list_id' => 0,
                'product_id'    => 0
            ])
                ->read(['id'])
                ->first();

            $product = Product::create([
                'name'             => 'Test product (1111.111111.11111111.111)',
                'label'            => 'Test product',
                'sku'              => '1111.111111.11111111.111',
                'product_model_id' => 0
            ])
                ->read(['id'])
                ->first();

            $sale_entry = SaleEntry::create([
                'product_id'  => $product['id'],
                'customer_id' => $customer['id'],
                'price_id'    => $price['id'],
                'unit_price'  => 25.99
            ])
                ->read(['id'])
                ->first();

            return $sale_entry['id'];
        },
        'act'         => function($sale_entry_id) {
            // Run action
            eQual::run(
                'do',
                'sale_saleentry_add-receivable',
                ['id' => $sale_entry_id]
            );
        },
        'assert'      => function() {
            // Assert that when a receivable is created the sale entry is updated to create a link

            $customer = Customer::search(['name', '=', 'Test customer'])
                ->read(['id'])
                ->first();

            $entry = SaleEntry::search(['customer_id', '=', $customer['id']])
                ->read(['has_receivable', 'receivable_id'])
                ->first();

            return $entry['has_receivable'] === true
                && !empty($entry['receivable_id'])
                && $entry['receivable_id'] > 0;
        },
        'rollback'    => function() {
            // Remove customer, receivable queue, price, product, sale entry and receivable

            $customer = Customer::search(['name', '=', 'Test customer'])
                ->read(['id'])
                ->first();

            $entry = SaleEntry::search(['customer_id', '=', $customer['id']])
                ->read(['price_id', 'product_id'])
                ->first();

            Customer::id($customer['id'])->delete(true);
            ReceivablesQueue::search(['customer_id', '=', $customer['id']])->delete(true);
            Price::id($entry['price_id'])->delete(true);
            Product::id($entry['product_id'])->delete(true);
            SaleEntry::search(['customer_id', '=', $customer['id']])->delete(true);
            Receivable::search(['product_id', '=', $entry['product_id']])->delete(true);
        }
    ]
];
