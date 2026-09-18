<?php

use equal\services\Container;
use identity\Identity;
use identity\IdentityAbstract;
use purchase\supplier\Supplier;
use sale\customer\Customer;

$tests = [
    '0101' => [
        'description' => 'Propagate Identity fields to linked facets.',
        'help'        => "Creates one Identity linked to a Customer and a Supplier, 
            updates its email, 
            then verifies that the same value is stored on the Identity and both facets.",
        'arrange'     => function() {
            $suffix = str_replace('.', '', uniqid('', true));
            $identity = Identity::create([
                    'type_id'                => 1,
                    'firstname'              => 'Test',
                    'lastname'               => 'Facet',
                    'citizen_identification' => "facet-sync-{$suffix}",
                    'email'                  => "canonical.{$suffix}@example.com"
                ])
                ->read(['id'])
                ->first();
            $customer = Customer::create(['identity_id' => $identity['id']])->first();
            $supplier = Supplier::create(['identity_id' => $identity['id']])->first();

            return [
                'identity_id' => $identity['id'],
                'customer_id' => $customer['id'],
                'supplier_id' => $supplier['id']
            ];
        },
        'act'         => function($args) {
            $email = 'identity-update@example.com';
            Identity::id($args['identity_id'])->update(['email' => $email]);

            return array_merge($args, [
                'expected_email' => $email,
                'identity'       => Identity::id($args['identity_id'])->read(['email'])->first(),
                'customer'       => Customer::id($args['customer_id'])->read(['email'])->first(),
                'supplier'       => Supplier::id($args['supplier_id'])->read(['email'])->first()
            ]);
        },
        'assert'      => function($args) {
            return $args['identity']['email'] === $args['expected_email']
                && $args['customer']['email'] === $args['expected_email']
                && $args['supplier']['email'] === $args['expected_email'];
        },
        'rollback'    => function($args) {
            Identity::id($args['identity_id'])->update(['customer_id' => null, 'supplier_id' => null]);
            Customer::id($args['customer_id'])->delete(true);
            Supplier::id($args['supplier_id'])->delete(true);
            Identity::id($args['identity_id'])->delete(true);
        }
    ],

    '0102' => [
        'description' => 'Propagate a Facet field through Identity.',
        'help'        => 'Updates the email of a linked Customer, then verifies that it is copied to the canonical Identity and propagated from there to the linked Supplier.',
        'arrange'     => function() {
            $suffix = str_replace('.', '', uniqid('', true));
            $identity = Identity::create([
                    'type_id'                => 1,
                    'firstname'              => 'Test',
                    'lastname'               => 'Facet',
                    'citizen_identification' => "facet-sync-{$suffix}",
                    'email'                  => "canonical.{$suffix}@example.com"
                ])
                ->read(['id'])
                ->first(true);
            $customer = Customer::create(['identity_id' => $identity['id']])->read(['id'])->first(true);
            $supplier = Supplier::create(['identity_id' => $identity['id']])->read(['id'])->first(true);

            return [
                'identity_id' => $identity['id'],
                'customer_id' => $customer['id'],
                'supplier_id' => $supplier['id']
            ];
        },
        'act'         => function($args) {
            $email = 'facet-update@example.com';
            Customer::id($args['customer_id'])->update(['email' => $email]);

            return array_merge($args, [
                'expected_email' => $email,
                'identity'       => Identity::id($args['identity_id'])->read(['email'])->first(true),
                'customer'       => Customer::id($args['customer_id'])->read(['email'])->first(true),
                'supplier'       => Supplier::id($args['supplier_id'])->read(['email'])->first(true)
            ]);
        },
        'assert'      => function($args) {
            return $args['identity']['email'] === $args['expected_email']
                && $args['customer']['email'] === $args['expected_email']
                && $args['supplier']['email'] === $args['expected_email'];
        },
        'rollback'    => function($args) {
            Identity::id($args['identity_id'])->update(['customer_id' => null, 'supplier_id' => null]);
            Customer::id($args['customer_id'])->delete(true);
            Supplier::id($args['supplier_id'])->delete(true);
            Identity::id($args['identity_id'])->delete(true);
        }
    ],

    '0103' => [
        'description' => 'Keep Facet-specific fields isolated.',
        'help'        => 'Updates the Customer-only flag_latepayer field, then verifies that the common email remains unchanged on the Customer, Identity and Supplier.',
        'arrange'     => function() {
            $suffix = str_replace('.', '', uniqid('', true));
            $email = "canonical.{$suffix}@example.com";
            $identity = Identity::create([
                    'type_id'                => 1,
                    'firstname'              => 'Test',
                    'lastname'               => 'Facet',
                    'citizen_identification' => "facet-sync-{$suffix}",
                    'email'                  => $email
                ])
                ->read(['id'])
                ->first(true);
            $customer = Customer::create(['identity_id' => $identity['id']])->read(['id'])->first(true);
            $supplier = Supplier::create(['identity_id' => $identity['id']])->read(['id'])->first(true);

            return [
                'identity_id'    => $identity['id'],
                'customer_id'    => $customer['id'],
                'supplier_id'    => $supplier['id'],
                'expected_email' => $email
            ];
        },
        'act'         => function($args) {
            Customer::id($args['customer_id'])->update(['flag_latepayer' => true]);

            return array_merge($args, [
                'identity' => Identity::id($args['identity_id'])->read(['email'])->first(true),
                'customer' => Customer::id($args['customer_id'])->read(['email', 'flag_latepayer'])->first(true),
                'supplier' => Supplier::id($args['supplier_id'])->read(['email'])->first(true)
            ]);
        },
        'assert'      => function($args) {
            return $args['customer']['flag_latepayer'] === true
                && $args['identity']['email'] === $args['expected_email']
                && $args['customer']['email'] === $args['expected_email']
                && $args['supplier']['email'] === $args['expected_email'];
        },
        'rollback'    => function($args) {
            Identity::id($args['identity_id'])->update(['customer_id' => null, 'supplier_id' => null]);
            Customer::id($args['customer_id'])->delete(true);
            Supplier::id($args['supplier_id'])->delete(true);
            Identity::id($args['identity_id'])->delete(true);
        }
    ],

    '0104' => [
        'description' => 'Propagate an explicit null value.',
        'help'        => 'Explicitly sets the Customer email to null, then verifies that null is treated as a real change and propagated to the Identity and linked Supplier.',
        'arrange'     => function() {
            $suffix = str_replace('.', '', uniqid('', true));
            $identity = Identity::create([
                    'type_id'                => 1,
                    'firstname'              => 'Test',
                    'lastname'               => 'Facet',
                    'citizen_identification' => "facet-sync-{$suffix}",
                    'email'                  => "canonical.{$suffix}@example.com"
                ])
                ->read(['id'])
                ->first(true);
            $customer = Customer::create(['identity_id' => $identity['id']])->read(['id'])->first(true);
            $supplier = Supplier::create(['identity_id' => $identity['id']])->read(['id'])->first(true);

            return [
                'identity_id' => $identity['id'],
                'customer_id' => $customer['id'],
                'supplier_id' => $supplier['id']
            ];
        },
        'act'         => function($args) {
            Customer::id($args['customer_id'])->update(['email' => null]);

            return array_merge($args, [
                'identity' => Identity::id($args['identity_id'])->read(['email'])->first(true),
                'customer' => Customer::id($args['customer_id'])->read(['email'])->first(true),
                'supplier' => Supplier::id($args['supplier_id'])->read(['email'])->first(true)
            ]);
        },
        'assert'      => function($args) {
            return is_null($args['identity']['email'])
                && is_null($args['customer']['email'])
                && is_null($args['supplier']['email']);
        },
        'rollback'    => function($args) {
            Identity::id($args['identity_id'])->update(['customer_id' => null, 'supplier_id' => null]);
            Customer::id($args['customer_id'])->delete(true);
            Supplier::id($args['supplier_id'])->delete(true);
            Identity::id($args['identity_id'])->delete(true);
        }
    ],

    '0105' => [
        'description' => 'Reattach a Facet without explicit fields.',
        'help'        => 'Moves a Customer from a source Identity to a target Identity using only identity_id. It verifies that canonical fields come from the target, the old backlink is cleared and the new backlink is set.',
        'arrange'     => function() {
            $suffix = str_replace('.', '', uniqid('', true));
            $source = Identity::create([
                    'type_id'                => 1,
                    'firstname'              => 'Source',
                    'lastname'               => 'Facet',
                    'citizen_identification' => "facet-source-{$suffix}",
                    'email'                  => 'source@example.com'
                ])
                ->read(['id'])
                ->first(true);
            $target = Identity::create([
                    'type_id'                => 1,
                    'firstname'              => 'Target',
                    'lastname'               => 'Facet',
                    'citizen_identification' => "facet-target-{$suffix}",
                    'email'                  => 'target@example.com'
                ])
                ->read(['id'])
                ->first(true);
            $customer = Customer::create(['identity_id' => $source['id']])->read(['id'])->first(true);
            $supplier = Supplier::create(['identity_id' => $target['id']])->read(['id'])->first(true);

            return [
                'source_identity_id' => $source['id'],
                'target_identity_id' => $target['id'],
                'customer_id'        => $customer['id'],
                'supplier_id'        => $supplier['id']
            ];
        },
        'act'         => function($args) {
            Customer::id($args['customer_id'])->update(['identity_id' => $args['target_identity_id']]);

            return array_merge($args, [
                'customer'       => Customer::id($args['customer_id'])
                    ->read(['identity_id', 'firstname', 'email'])
                    ->first(true),
                'source_identity' => Identity::id($args['source_identity_id'])->read(['customer_id'])->first(true),
                'target_identity' => Identity::id($args['target_identity_id'])->read(['customer_id'])->first(true)
            ]);
        },
        'assert'      => function($args) {
            return $args['customer']['identity_id'] === $args['target_identity_id']
                && $args['customer']['firstname'] === 'Target'
                && $args['customer']['email'] === 'target@example.com'
                && is_null($args['source_identity']['customer_id'])
                && $args['target_identity']['customer_id'] === $args['customer_id'];
        },
        'rollback'    => function($args) {
            Identity::ids([$args['source_identity_id'], $args['target_identity_id']])
                ->update(['customer_id' => null, 'supplier_id' => null]);
            Customer::id($args['customer_id'])->delete(true);
            Supplier::id($args['supplier_id'])->delete(true);
            Identity::ids([$args['source_identity_id'], $args['target_identity_id']])->delete(true);
        }
    ],

    '0106' => [
        'description' => 'Reattach a Facet with an explicit field.',
        'help'        => 'Moves a Customer to another Identity while explicitly changing its email. It verifies that the explicit email updates the target Identity and all its facets, while other common fields come from the target.',
        'arrange'     => function() {
            $suffix = str_replace('.', '', uniqid('', true));
            $source = Identity::create([
                    'type_id'                => 1,
                    'firstname'              => 'Source',
                    'lastname'               => 'Facet',
                    'citizen_identification' => "facet-source-{$suffix}",
                    'email'                  => 'source@example.com'
                ])
                ->read(['id'])
                ->first(true);
            $target = Identity::create([
                    'type_id'                => 1,
                    'firstname'              => 'Target',
                    'lastname'               => 'Facet',
                    'citizen_identification' => "facet-target-{$suffix}",
                    'email'                  => 'target@example.com'
                ])
                ->read(['id'])
                ->first(true);
            $customer = Customer::create(['identity_id' => $source['id']])->read(['id'])->first(true);
            $supplier = Supplier::create(['identity_id' => $target['id']])->read(['id'])->first(true);

            return [
                'source_identity_id' => $source['id'],
                'target_identity_id' => $target['id'],
                'customer_id'        => $customer['id'],
                'supplier_id'        => $supplier['id']
            ];
        },
        'act'         => function($args) {
            $email = 'explicit@example.com';
            Customer::id($args['customer_id'])->update([
                'identity_id' => $args['target_identity_id'],
                'email'       => $email
            ]);

            return array_merge($args, [
                'expected_email'  => $email,
                'customer'        => Customer::id($args['customer_id'])
                    ->read(['identity_id', 'firstname', 'email'])
                    ->first(true),
                'supplier'        => Supplier::id($args['supplier_id'])->read(['email'])->first(true),
                'source_identity' => Identity::id($args['source_identity_id'])->read(['customer_id'])->first(true),
                'target_identity' => Identity::id($args['target_identity_id'])
                    ->read(['customer_id', 'email'])
                    ->first(true)
            ]);
        },
        'assert'      => function($args) {
            return $args['customer']['identity_id'] === $args['target_identity_id']
                && $args['customer']['firstname'] === 'Target'
                && $args['customer']['email'] === $args['expected_email']
                && $args['supplier']['email'] === $args['expected_email']
                && is_null($args['source_identity']['customer_id'])
                && $args['target_identity']['customer_id'] === $args['customer_id']
                && $args['target_identity']['email'] === $args['expected_email'];
        },
        'rollback'    => function($args) {
            Identity::ids([$args['source_identity_id'], $args['target_identity_id']])
                ->update(['customer_id' => null, 'supplier_id' => null]);
            Customer::id($args['customer_id'])->delete(true);
            Supplier::id($args['supplier_id'])->delete(true);
            Identity::ids([$args['source_identity_id'], $args['target_identity_id']])->delete(true);
        }
    ],

    '0107' => [
        'description' => 'Create Identity from an unlinked Facet.',
        'help'        => 'Creates a Customer without identity_id, then verifies that a new Identity is created, initialized with the Customer common fields and linked back to that Customer.',
        'arrange'     => function() {
            $suffix = str_replace('.', '', uniqid('', true));

            return [
                'type_id'                => 1,
                'firstname'              => 'Created',
                'lastname'               => 'Facet',
                'citizen_identification' => "facet-create-{$suffix}",
                'email'                  => "created.{$suffix}@example.com"
            ];
        },
        'act'         => function($values) {
            $customer = Customer::create($values)
                ->read(['id', 'identity_id'])
                ->first(true);
            $identity = Identity::id($customer['identity_id'])
                ->read(['customer_id', 'firstname', 'lastname', 'citizen_identification', 'email'])
                ->first(true);

            return [
                'values'      => $values,
                'customer_id' => $customer['id'],
                'identity_id' => $customer['identity_id'],
                'identity'    => $identity
            ];
        },
        'assert'      => function($args) {
            return $args['identity']['customer_id'] === $args['customer_id']
                && $args['identity']['firstname'] === $args['values']['firstname']
                && $args['identity']['lastname'] === $args['values']['lastname']
                && $args['identity']['citizen_identification'] === $args['values']['citizen_identification']
                && $args['identity']['email'] === $args['values']['email'];
        },
        'rollback'    => function($args) {
            Identity::id($args['identity_id'])->update(['customer_id' => null]);
            Customer::id($args['customer_id'])->delete(true);
            Identity::id($args['identity_id'])->delete(true);
        }
    ],

    '0108' => [
        'description' => 'Realign a Facet from Identity.',
        'help'        => 'Introduces inconsistencies in a Customer while ORM events are disabled, runs sync_from_identity, then compares every common field with the canonical Identity.',
        'arrange'     => function() {
            $suffix = str_replace('.', '', uniqid('', true));
            $identity = Identity::create([
                    'type_id'                => 1,
                    'firstname'              => 'Test',
                    'lastname'               => 'Facet',
                    'citizen_identification' => "facet-sync-{$suffix}",
                    'email'                  => "canonical.{$suffix}@example.com",
                    'phone'                  => '+32000000000',
                    'address_city'           => 'Brussels'
                ])
                ->read(['id'])
                ->first(true);
            $customer = Customer::create(['identity_id' => $identity['id']])->read(['id'])->first(true);
            $supplier = Supplier::create(['identity_id' => $identity['id']])->read(['id'])->first(true);

            return [
                'identity_id' => $identity['id'],
                'customer_id' => $customer['id'],
                'supplier_id' => $supplier['id']
            ];
        },
        'act'         => function($args) {
            $orm = Container::getInstance()->get('orm');
            $events = $orm->disableEvents();
            try {
                Customer::id($args['customer_id'])->update([
                    'firstname'    => 'Drifted',
                    'email'        => 'drifted@example.com',
                    'phone'        => '+32999999999',
                    'address_city' => 'Drifted city'
                ]);
            }
            finally {
                $orm->enableEvents($events);
            }

            $drifted = Customer::id($args['customer_id'])
                ->read(['firstname', 'email', 'phone', 'address_city'])
                ->first(true);
            Customer::id($args['customer_id'])->do('sync_from_identity');

            $reflection = new ReflectionClass(IdentityAbstract::class);
            $common_fields = $reflection->getReflectionConstant('COMMON_IDENTITY_FIELDS')->getValue();
            $identity = Identity::id($args['identity_id'])->read($common_fields)->first(true);
            $customer = Customer::id($args['customer_id'])->read($common_fields)->first(true);

            $is_synchronized = true;
            foreach($common_fields as $field) {
                if(($identity[$field] ?? null) !== ($customer[$field] ?? null)) {
                    $is_synchronized = false;
                    break;
                }
            }

            return array_merge($args, [
                'drifted'        => $drifted,
                'is_synchronized' => $is_synchronized
            ]);
        },
        'assert'      => function($args) {
            return $args['drifted']['firstname'] === 'Drifted'
                && $args['drifted']['email'] === 'drifted@example.com'
                && $args['drifted']['phone'] === '+32999999999'
                && $args['drifted']['address_city'] === 'Drifted city'
                && $args['is_synchronized'];
        },
        'rollback'    => function($args) {
            Identity::id($args['identity_id'])->update(['customer_id' => null, 'supplier_id' => null]);
            Customer::id($args['customer_id'])->delete(true);
            Supplier::id($args['supplier_id'])->delete(true);
            Identity::id($args['identity_id'])->delete(true);
        }
    ]
];
