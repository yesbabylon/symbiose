<?php
/*
    This file is part of the eQual framework <http://www.github.com/cedricfrancoys/equal>
    Some Rights Reserved, Cedric Francoys, 2010-2021
    Licensed under GNU GPL 3 license <http://www.gnu.org/licenses/>
*/

namespace inventory\service;

use equal\orm\Model;
use inventory\Product;

class Service extends Model {

    public static function getColumns()
    {
        return [
            'name' => [
                'type'              => 'string',
                'unique'            => true,
                'required'          => true,
                'description'       => 'Unique identifier of the service. (ex: Google API, mailtrap.io).'
            ],

            'description' => [
                'type'              => 'string',
                'usage'             => 'text/plain',
                'description'       => 'Information about a service.',
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
                'description'       => 'The customer of the service product.',
                'function'          => 'calcCustomerId',
                'visible'           => ['is_internal', '=', false],
                'store'             => true,
                'instant'           => true
            ],

            'has_external_provider' => [
                'type'              => 'boolean',
                'description'       => 'The service has external provider.',
                'default'           =>  false
            ],

            'service_provider_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'inventory\service\ServiceProvider',
                'description'       => 'The service provider to which the service belongs.',
                'visible'           => ['has_external_provider', '=', true]
            ],

            'details_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'inventory\service\Detail',
                'foreign_field'     => 'service_id',
                'description'       => 'Details about the service.'
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
                'description'       => 'Access about to the service.'
            ],

            'softwares_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'inventory\server\Software',
                'foreign_field'     => 'service_id',
                'description'       => 'Access about to the service.'
            ],

        ];
    }

    public static function onchange($event) {
        $result = [];

        if(isset($event['has_external_provider'])){
            $result['service_provider_id'] = '';
        }

        if(isset($event['has_subscription'])){
                $result['is_auto_renew'] = false;
                $result['is_billable'] = false;
        }

        if(isset($event['product_id']) && $event['product_id'] > 0){
            $product = Product::search(['id', '=', $event['product_id']])
                ->read(['is_internal', 'customer_id' => ['id', 'name']])
                ->first();

            $result['is_internal'] = $product['is_internal'];
            $result['customer_id'] = $product['customer_id'];
        }

        return $result;

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