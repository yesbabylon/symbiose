<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2024
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

namespace inventory\service;

use equal\orm\Model;

class ServiceModel  extends Model {


    public static function getDescription() {
        return 'The Service Model manages and organizes service models, their billing, subscriptions, and associations with providers.';
    }

    public static function getColumns()
    {
        return [
            'name' => [
                'type'              => 'string',
                'unique'            => true,
                'required'          => true,
                'description'       => 'Unique identifier of the service model. (ex: Google API, mailtrap.io).'
            ],

            'description' => [
                'type'              => 'string',
                'usage'             => 'text/plain',
                'description'       => 'Information about a service model.',
            ],

            'has_subscription' => [
                'type'              => 'boolean',
                'description'       => 'The service model has a subscription.',
                'default'           => false
            ],

            'is_billable' => [
                'type'              => 'boolean',
                'description'       => 'The service model is billable.',
                'visible'           => ['has_subscription', '=', true],
                'default'           => false,
            ],

            'is_auto_renew' => [
                'type'              => 'boolean',
                'description'       => 'The service model is auto renew.',
                'visible'           => ['has_subscription', '=', true],
                'default'           => false
            ],

            'has_external_provider' => [
                'type'              => 'boolean',
                'description'       => 'The service model has external provider.',
                'default'           =>  false
            ],

            'service_provider_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'inventory\service\ServiceProvider',
                'description'       => 'The service provider to which the service model belongs.',
                'visible'           => ['has_external_provider', '=', true]
            ],

            'services_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'inventory\service\Service',
                'foreign_field'     => 'service_model_id',
                'ondetach'          => 'delete',
                'description'       => 'The list of services associated with the service model.'
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

        return $result;

    }

}