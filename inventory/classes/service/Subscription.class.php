<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2024
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

namespace inventory\service;

use sale\customer\Customer;
use sale\subscription\Subscription as SaleSubscription;

class Subscription extends SaleSubscription  {

    public static function getColumns(): array {
        return [
            'customer_id' => [
                'type'              => 'computed',
                'result_type'       => 'many2one',
                'foreign_object'    => 'sale\customer\Customer',
                'description'       => 'The Customer concerned by the subscription (information found in service).',
                'visible'           => [
                    ['service_id', '<>', null],
                    ['is_internal', '=', false]
                ],
                'store'             => true,
                'instant'           => true,
                'function'          => 'calcCustomerId'
            ],

            'is_billable' => [
                'type'              => 'computed',
                'result_type'       => 'boolean',
                'description'       => 'Can be billed to the customer (information found in service).',
                'visible'           => ['service_id', '<>', null],
                'store'             => true,
                'instant'           => true,
                'function'          => 'calcIsBillable'
            ],

            'subscription_entries_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'inventory\service\SubscriptionEntry',
                'foreign_field'     => 'subscription_id',
                'ondetach'          => 'delete',
                'description'       => 'Subscription entries of the subscription.'
            ],

            'service_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'inventory\service\Service',
                'description'       => 'Service attached to a subscription.',
                'required'          => true,
                'onupdate'          => 'onupdateServiceId',
                'dependents'        => ['has_external_provider', 'is_billable', 'is_internal', 'customer_id']
            ],

            'is_internal' => [
                'type'              => 'computed',
                'result_type'       => 'boolean',
                'description'       => 'Is the subscription for own organisation (information found in service).',
                'visible'           => ['service_id', '<>', null],
                'store'             => true,
                'instant'           => true,
                'function'          => 'calcIsInternal'
            ],

            'has_external_provider' => [
                'type'              => 'computed',
                'result_type'       => 'boolean',
                'description'       => 'The subscription has an external provider (information found in service).',
                'visible'           => ['service_id', '<>', null],
                'store'             => true,
                'instant'           => true,
                'dependents'        => ['service_provider_id'],
                'function'          => 'calcHasExternalProvider'
            ],

            'service_provider_id' => [
                'type'              => 'computed',
                'result_type'       => 'many2one',
                'foreign_object'    => 'inventory\service\ServiceProvider',
                'description'       => 'The service provider to which the service belongs.',
                'visible'           => ['has_external_provider', '=', true],
                'store'             => true,
                'function'          => 'calcServiceProviderId'
            ]

        ];
    }

    public static function onchange($event, $values): array {
        $result = parent::onchange($event, $values);

        if(isset($event['service_id']) && strlen($event['service_id']) > 0){
            $service = Service::search(['id', '=', $event['service_id']])
                ->read(['has_external_provider', 'is_billable', 'is_internal', 'customer_id', 'service_provider_id'])
                ->first();

            $result['has_external_provider'] = $service['has_external_provider'];
            $result['is_billable'] = $service['is_billable'];
            $result['is_internal'] = $service['is_internal'];

            $customer = Customer::id($service['customer_id'])
                ->read(['id', 'name'])
                ->first();
            $result['customer_id'] = $customer;

            $service_provider = ServiceProvider::id($service['service_provider_id'])
                ->read(['id', 'name'])
                ->first();
            $result['service_provider_id'] = $service_provider;
        }

        return $result;
    }

    public static function onupdateServiceId($self): void {
        $self->read(['service_id']);
        foreach($self as $id) {
            Service::id($id['service_id'])->update(['has_subscription' => true]);
        }
    }

    public static function calcIsInternal($self): array {
        return self::calcFromService($self, 'is_internal');
    }

    public static function calcIsBillable($self): array {
        return self::calcFromService($self, 'is_billable');
    }

    public static function calcHasExternalProvider($self): array {
        return self::calcFromService($self, 'has_external_provider');
    }

    public static function calcServiceProviderId($self): array {
        return self::calcFromService($self, 'service_provider_id');
    }

    public static function calcCustomerId($self): array {
        return self::calcFromService($self, 'customer_id');
    }

    private static function calcFromService($self, $column): array {
        $result = [];
        $self->read(['service_id' => [$column]]);
        foreach($self as $id => $subscription) {
            if(isset($subscription['service_id'][$column])) {
                $result[$id] = $subscription['service_id'][$column];
            }
        }

        return $result;
    }
}
