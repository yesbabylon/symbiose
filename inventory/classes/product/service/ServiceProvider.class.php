<?php
/*
    This file is part of the eQual framework <http://www.github.com/cedricfrancoys/equal>
    Some Rights Reserved, Cedric Francoys, 2010-2021
    Licensed under GNU GPL 3 license <http://www.gnu.org/licenses/>
*/

namespace inventory\product\service;

use equal\orm\Model;

class ServiceProvider extends Model {

    public static function getColumns()
    {
        return [
            'name' => [
                'type'              => 'string',
                'unique'            => true,
                'description'       => 'Unique identifier of the server, (ex: Google API, mailtrap.io, Sparkpost, Mailchimp, ...)'
            ],

            'description' => [
                'type'              => 'string',
                'description'       => 'Short presentation of the Service Provider.',
                'multilang'         => true
            ],

            'login_url'   => [
                'type'              => 'string',
                'description'       => 'Url for signing in.'
            ],

            'services_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'inventory\product\service\Service',
                'foreign_field'     => 'service_provider_id',
                'description'       => 'Services attached to the provider.'
            ],

            'service_provider_category_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'inventory\product\service\ServiceProviderCategory',
                'description'       => 'category in which the service provider belongs.'
            ],

            'service_provider_detail_category_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'inventory\product\service\ServiceProviderDetailCategory',
                'description'       => 'category in which the service provider belongs.'
            ]
        ];
    }
}
