<?php
/*
    This file is part of the eQual framework <http://www.github.com/cedricfrancoys/equal>
    Some Rights Reserved, Cedric Francoys, 2010-2021
    Licensed under GNU GPL 3 license <http://www.gnu.org/licenses/>
*/

namespace inventory\service;

use equal\orm\Model;

class ServiceProvider extends Model {

    public static function getColumns()
    {
        return [
            'name' => [
                'type'              => 'string',
                'unique'            => true,
                'required'          => true,
                'description'       => 'Unique identifier of the service provider.'
            ],

            'description' => [
                'type'              => 'string',
                'description'       => 'Short presentation of the Service Provider.'
            ],

            'login_url'   => [
                'type'              => 'string',
                'description'       => 'Url for signing in.'
            ],
            'service_provider_category_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'inventory\service\ServiceProviderCategory',
                'description'       => 'Category attached the service provider.'
            ],

            'services_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'inventory\service\Service',
                'foreign_field'     => 'service_provider_id',
                'description'       => 'Services of the provider.'
            ],

        ];
    }
}
