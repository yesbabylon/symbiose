<?php

use infra\service\SubscriptionEntry;
use sale\SaleEntry;

$tests = [
    '0101' => [
        'description' => 'Infrastructure subscription entries rely on the technical model discriminator.',
        'return'      => 'boolean',
        'expected'    => true,
        'test'        => function() {
            $columns = SubscriptionEntry::getColumns();

            return !isset($columns['object_class'])
                && is_a(SubscriptionEntry::class, SaleEntry::class, true);
        }
    ]
];
