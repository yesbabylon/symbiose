<?php

use core\User;
use sale\catalog\Product;
use sale\price\Price;
use timetrack\Project;
use timetrack\TimeEntrySaleModel;
use timetrack\TimeEntry;

$tests = [
    '0101' => [
        'description' => 'Tests that action create-quick throws if user does not exist',
        'return'      => 'integer',
        'expected'    => QN_ERROR_UNKNOWN_OBJECT,
        'test'        => function() {
            $error = 0;
            try {
                // Run action
                eQual::run(
                    'do',
                    'timetrack_timeentry_create-quick',
                    ['user_id' => -1, 'project_id' => 1, 'origin' => 'backlog']
                );
            } catch(Exception $e) {
                $error = $e->getCode();
            }

            return $error;
        }
    ],

    '0102' => [
        'description' => 'Tests that action create-quick creates with given user, project and origin',
        'arrange'     => function() {
            // Create user, project
            $user = User::create([
                'name'     => 'Test user',
                'login'    => 'testUser@devmail.com',
                'password' => 'password'
            ])
                ->read(['id'])
                ->first();

            return $user['id'];
        },
        'act'         => function($user_id) {
            // Run action
            eQual::run(
                'do',
                'timetrack_timeentry_create-quick',
                [
                    'user_id'    => $user_id,
                    'project_id' => 0,
                    'origin'     => 'email'
                ]
            );

            return $user_id;
        },
        'assert'      => function($user_id) {
            $time_entries = TimeEntry::search(['user_id', '=', $user_id])
                ->read(['project_id', 'origin', 'is_billable', 'product_id', 'price_id', 'unit_price']);

            $time_entry = $time_entries->first();

            return count($time_entries) === 1
                && $time_entry['project_id'] === 0
                && $time_entry['origin'] === 'email'
                && is_null($time_entry['product_id'])
                && is_null($time_entry['price_id'])
                && is_null($time_entry['unit_price'])
                && $time_entry['is_billable'] === false;
        },
        'rollback'    => function() {
            // Remove user and time entry

            $user = User::search(['name', '=', 'Test user'])
                ->read(['id'])
                ->first();

            User::id($user['id'])->delete(true);
            TimeEntry::search(['user_id', '=', $user['id']])->delete(true);
        }
    ],

    '0103' => [
        'description' => 'Tests that action create-quick creates with model matching only origin',
        'arrange'     => function() {
            // Create user and time entry sale model

            $user = User::create([
                'name'     => 'Test user',
                'login'    => 'testUser@devmail.com',
                'password' => 'password'
            ])
                ->read(['id'])
                ->first();

            $project = Project::create(['name' => 'Test project'])
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

            $price = Price::create([
                'name'          => 'Test price',
                'price'         => 29.99,
                'vat_rate'      => 0.99,
                'price_list_id' => 0,
                'product_id'    => $product['id']
            ])
                ->read(['id'])
                ->first();

            $unit_price = 44.0;

            TimeEntrySaleModel::create([
                'name'       => 'Test sale model',
                'origin'     => 'email',
                'product_id' => $product['id'],
                'price_id'   => $price['id'],
                'unit_price' => $unit_price
            ]);

            return [
                $user['id'],
                $project['id'],
                $product['id'],
                $price['id'],
                $unit_price
            ];
        },
        'act'         => function($data) {
            list($user_id, $project_id) = $data;

            // Run action
            eQual::run(
                'do',
                'timetrack_timeentry_create-quick',
                [
                    'user_id'    => $user_id,
                    'project_id' => $project_id,
                    'origin'     => 'email'
                ]
            );

            return $data;
        },
        'assert'      => function($data) {
            // Assert that time entry was created with model product_id, price_id and unit_price

            list($user_id, $project_id, $product_id, $price_id, $unit_price) = $data;

            $time_entry = TimeEntry::search(['user_id', '=', $user_id])
                ->read(['product_id', 'price_id', 'unit_price', 'is_billable'])
                ->first();

            return $time_entry['product_id'] === $product_id
                && $time_entry['price_id'] === $price_id
                && $time_entry['unit_price'] === $unit_price
                && $time_entry['is_billable'];
        },
        'rollback'    => function() {
            // Remove user, time entry sale model, time entry, product and price

            $user = User::search(['name', '=', 'Test user'])
                ->read(['id'])
                ->first();

            User::id($user['id'])->delete(true);
            Project::search(['name', '=', 'Test project'])->delete(true);
            TimeEntrySaleModel::search(['name', '=', 'Test sale model'])->delete(true);
            TimeEntry::search(['user_id', '=', $user['id']])->delete(true);

            $product = Product::search(['label', '=', 'Test product'])
                ->read(['id'])
                ->first();

            Product::id($product['id'])->delete(true);
            Price::search(['product_id', '=', $product['id']])->delete(true);
        },
    ],

    '0104' => [
        'description' => 'Tests that action create-quick creates with model matching origin and project',
        'arrange'     => function() {
            // Create user, a time entry sale model not linked to any project and time entry sale model link to a project

            $user = User::create([
                'name'     => 'Test user',
                'login'    => 'testUser@devmail.com',
                'password' => 'password'
            ])
                ->read(['id'])
                ->first();

            $product_one = Product::create([
                'name'             => 'Test product 1 (1111.111111.11111111.111)',
                'label'            => 'Test product 1',
                'sku'              => '1111.111111.11111111.111',
                'product_model_id' => 0
            ])
                ->read(['id'])
                ->first();

            $price_one = Price::create([
                'name'          => 'Test price 1',
                'price'         => 29.99,
                'vat_rate'      => 0.99,
                'price_list_id' => 0,
                'product_id'    => $product_one['id']
            ])
                ->read(['id'])
                ->first();

            $unit_price_one = 44.0;

            TimeEntrySaleModel::create([
                'name'        => 'Test sale model 1',
                'origin'      => 'email',
                'product_id'  => $product_one['id'],
                'price_id'    => $price_one['id'],
                'unit_price'  => $unit_price_one
            ])
                ->read(['id'])
                ->first();

            $project = Project::create(['name' => 'Test project'])
                ->read(['id'])
                ->first();

            $product_two = Product::create([
                'name'             => 'Test product 2 (2222.222222.22222222.222)',
                'label'            => 'Test product 2',
                'sku'              => '2222.222222.22222222.222',
                'product_model_id' => 0
            ])
                ->read(['id'])
                ->first();

            $price_two = Price::create([
                'name'          => 'Test price 2',
                'price'         => 29.99,
                'vat_rate'      => 0.99,
                'price_list_id' => 0,
                'product_id'    => $product_two['id']
            ])
                ->read(['id'])
                ->first();

            $unit_price_two = 44.0;

            TimeEntrySaleModel::create([
                'name'        => 'Test sale model 2',
                'origin'      => 'email',
                'product_id'  => $product_two['id'],
                'price_id'    => $price_two['id'],
                'unit_price'  => $unit_price_two,
                'projects_ids' => [$project['id']]
            ])
                ->read(['id'])
                ->first();

            return [
                $user['id'],
                $project['id'],
                $product_two['id'],
                $price_two['id'],
                $unit_price_two
            ];
        },
        'act'         => function($data) {
            list($user_id, $project_id) = $data;

            // Run action
            eQual::run(
                'do',
                'timetrack_timeentry_create-quick',
                [
                    'user_id'    => $user_id,
                    'project_id' => $project_id,
                    'origin'     => 'email'
                ]
            );

            return $data;
        },
        'assert'      => function($data) {
            // Assert that time entry was created with model product_id, price_id and unit_price of the model linked to the project

            list($user_id, $project_id, $product_id, $price_id, $unit_price) = $data;

            $time_entry = TimeEntry::search(['user_id', '=', $user_id])
                ->read(['product_id', 'price_id', 'unit_price', 'is_billable'])
                ->first();

            return $time_entry['product_id'] === $product_id
                && $time_entry['price_id'] === $price_id
                && $time_entry['unit_price'] === $unit_price
                && $time_entry['is_billable'];
        },
        'rollback'    => function() {
            // Remove user, time entry, project, time entry sale models, products and prices

            $user = User::search(['name', '=', 'Test user'])
                ->read(['id'])
                ->first();

            User::id($user['id'])->delete(true);
            TimeEntry::search(['user_id', '=', $user['id']])->delete(true);

            Product::search(['label', '=', 'Test product 1'])->delete(true);
            Price::search(['name', '=', 'Test price 1'])->delete(true);
            TimeEntrySaleModel::search(['name', '=', 'Test sale model 1'])->delete(true);

            Project::search(['name', '=', 'Test project'])->delete(true);
            Product::search(['label', '=', 'Test product 2'])->delete(true);
            Price::search(['name', '=', 'Test price 2'])->delete(true);
            TimeEntrySaleModel::search(['name', '=', 'Test sale model 2'])->delete(true);
        },
    ]
];
