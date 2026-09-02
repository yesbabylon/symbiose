<?php

use infra\service\SubscriptionEntry;
use sale\SaleEntry;

$tests = [
    '0101' => [
        'description' => 'Infrastructure subscription entries identify themselves as their SaleEntry origin class.',
        'return'      => 'boolean',
        'expected'    => true,
        'test'        => function() {
            $columns = SubscriptionEntry::getColumns();
            $object_class = $columns['object_class']['default'] ?? null;

            return $object_class === SubscriptionEntry::class
                && is_a($object_class, SaleEntry::class, true);
        }
    ]
];
