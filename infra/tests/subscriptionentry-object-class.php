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

            $subscription_entry = new ReflectionClass(SubscriptionEntry::class);

            return !isset($columns['object_class'])
                && $subscription_entry->isSubclassOf(SaleEntry::class);
        }
    ]
];
