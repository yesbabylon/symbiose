<?php
/*
    This file is part of the eQual framework <http://www.github.com/cedricfrancoys/equal>
    Some Rights Reserved, Cedric Francoys, 2010-2021
    Licensed under GNU GPL 3 license <http://www.gnu.org/licenses/>
*/

namespace inventory\product\service;

use equal\orm\Model;

class Service extends Model {

    public static function getColumns()
    {
        return [
            'name' => [
                'type'              => 'string',
                'unique'            => true,
                'description'       => 'Unique identifier of the service. (ex: Google API, mailtrap.io, Sparkpost, Mailchimp, ...)'
            ],

            'description' => [
                'type'              => 'string',
                'description'       => 'Informations about a service.',
                'multilang'         => true
            ],

            'access_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'inventory\product\Access',
                'foreign_field'     => 'service_id',
                'description'       => 'Access informations to the service.'
            ],

            'details_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'inventory\product\service\Detail',
                'foreign_field'     => 'service_id',
                'description'       => 'Details about the service.'
            ],

            'service_provider_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'inventory\product\service\ServiceProvider',
                'description'       => 'Details about the service provider.'
            ],

            'product_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'inventory\product\Product',
                'description'       => 'Details about the service.'
            ],
        ];
    }
}
