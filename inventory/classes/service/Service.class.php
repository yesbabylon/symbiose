<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2024
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

namespace inventory\service;

use equal\orm\Model;
use inventory\Product;

class Service extends Model {

    public static function getDescription() {
        return 'Inventory Services are designed to facilitate the management of billing, renewals, product-client associations, provider integration, documentation, and software or access management.';
    }

    public static function getColumns()
    {
        return [
            'name' => [
                'type'              => 'computed',
                'function'          => 'calcName',
                'result_type'       => 'string',
                'description'       => 'The display name of the service based on the service model and product.',
                'store'             => true,
                'instant'           => true,
                'readonly'          => true,
            ],

            'description' => [
                'type'              => 'string',
                'usage'             => 'text/plain',
                'description'       => 'Information about a service.',
            ],

            'service_model_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'inventory\service\ServiceModel',
                'description'       => 'The service model to which the service belongs.',
                'required'          => true,
                'dependents'        => ['has_subscription', 'is_billable']
            ],

            'has_subscription' => [
                'type'              => 'boolean',
                'description'       => 'The service has a subscription.',
                'default'           => false,
                'dependencies'      => ['is_billable','is_internal'],
            ],

            'is_billable' => [
                'type'              => 'boolean',
                'description'       => 'The service is billable.',
                'visible'           => ['has_subscription', '=', true],
                'default'           => false,
            ],

            'is_auto_renew' => [
                'type'              => 'boolean',
                'description'       => 'The service is auto renew.',
                'visible'           => ['has_subscription', '=', true],
                'default'           => false
            ],

            'has_external_provider' => [
                'type'              => 'computed',
                'result_type'       => 'boolean',
                'description'       => 'The service has external provider (computed by Service Model)..',
                'function'          => 'calcHasExternalProvider',
                'readonly'          => true,
                'store'             => true,
                'instant'           => true
            ],

            'service_provider_id' => [
                'type'              => 'computed',
                'result_type'       => 'many2one',
                'foreign_object'    => 'inventory\service\ServiceProvider',
                'description'       => 'The service provider to which the service belongs (computed by Service Model)..',
                'visible'           => ['has_external_provider', '=', true],
                'function'          => 'calcServiceProvider',
                'readonly'          => true,
                'store'             => true,
                'instant'           => true
            ],

            'product_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'inventory\Product',
                'ondelete'          => 'cascade',
                'description'       => 'The product to which the service belongs.',
                'dependencies'      => ['is_internal', 'customer_id'],
                'required'          => true
            ],

            'is_internal' => [
                'type'              => 'computed',
                'result_type'       => 'boolean',
                'description'       => 'The product of the service is internal.',
                'visible'           => ['product_id', '<>', null],
                'function'          => 'calcIsInternal',
                'store'             => true,
                'instant'           => true
            ],

            'customer_id' => [
                'type'              => 'computed',
                'result_type'       => 'many2one',
                'foreign_object'    => 'sale\customer\Customer',
                'description'       => 'The customer associated with the product.',
                'function'          => 'calcCustomerId',
                'visible'           => ['is_internal', '=', false],
                'store'             => true,
                'instant'           => true
            ],

            'details_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'inventory\service\Detail',
                'foreign_field'     => 'service_id',
                'description'       => 'List of details about the service.'
            ],

            'subscriptions_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'inventory\service\Subscription',
                'foreign_field'     => 'service_id',
                'description'       => 'The subscriptions about the service belongs.'
            ],

            'accesses_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'inventory\Access',
                'foreign_field'     => 'service_id',
                'description'       => 'Access to connect to the service.'
            ],

            'softwares_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'inventory\Software',
                'foreign_field'     => 'service_id',
                'description'       => 'Software associated with the service.'
            ],

        ];
    }

    public static function onchange($event, $values) {
        $result = [];

        if(isset($event['service_model_id']) && $event['service_model_id'] > 0){
            $service_model = ServiceModel::id($event['service_model_id'])
                ->read([
                        'has_external_provider',
                        'service_provider_id' => ['id','name'],
                        'has_subscription',
                        'is_billable',
                        'is_auto_renew'
                    ])
                ->first();

            $result['has_external_provider'] = $service_model['has_external_provider'];
            $result['service_provider_id'] = $service_model['service_provider_id'];
            $result['has_subscription'] = $service_model['has_subscription'];
            $result['is_billable'] = $service_model['is_billable'];
            $result['is_auto_renew'] = $service_model['is_auto_renew'];
        }

        if(isset($event['service_model_id']) || isset($event['product_id'])) {
            $result['name'] = self::computeName($event['service_model_id'] ?? $values['service_model_id'], $event['product_id'] ?? $values['product_id']);
        }

        if(isset($event['product_id']) && $event['product_id'] > 0){
            $product = Product::id($event['product_id'])
                ->read(['is_internal', 'customer_id' => ['id', 'name']])
                ->first();

            $result['is_internal'] = $product['is_internal'];
            $result['customer_id'] = $product['customer_id'];
        }

        return $result;

    }

    public static function calcServiceProvider($self) {
        return self::calcFromServiceModel($self, 'service_provider_id');
    }

    public static function calcHasExternalProvider($self) {
        return self::calcFromServiceModel($self, 'has_external_provider');
    }

    private static function calcFromServiceModel($self, $column): array {
        $result = [];
        $self->read(['service_model_id' => [$column]]);
        foreach($self as $id => $service) {
            if(isset($service['service_model_id'][$column])) {
                $result[$id] = $service['service_model_id'][$column];
            }
        }
        return $result;
    }

    public static function calcName($self) {
        $result = [];
        $self->read(['service_model_id', 'product_id']);
        foreach($self as $id => $service) {
            $result[$id] = self::computeName($service['service_model_id'], $service['product_id']);
        }
        return $result;
    }

    private static function computeName($service_model_id, $service_product_id) {
        $name = '';

        $service_model = ServiceModel::id($service_model_id)->read(['id', 'name'])->first();
        $product = Product::id($service_product_id)->read(['id', 'name'])->first();

        if(isset($service_model)) {
            $name = '['.$service_model['name'];
        }
        if(isset($product)) {
            if(strlen($name)) {
                $name .= ' - ';
            }
            $name .= $product['name'] . ']';
        }
        else {
            $name .= ']';
        }

        return $name;
    }

    public static function calcIsInternal($self) {
        $result = [];
        $self->read(['product_id' => ['is_internal']]);
        foreach($self as $id => $service) {
            $result[$id] = $service['product_id']['is_internal'];
        }
        return $result;
    }

    public static function calcCustomerId($self) {
        $result = [];
        $self->read(['product_id' => ['customer_id']]);
        foreach($self as $id => $service) {
            $result[$id] = $service['product_id']['customer_id'];
        }
        return $result;
    }

}