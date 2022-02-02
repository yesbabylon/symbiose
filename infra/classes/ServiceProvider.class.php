<?php
/*
    This file is part of the eQual framework <http://www.github.com/cedricfrancoys/equal>
    Some Rights Reserved, Cedric Francoys, 2010-2021
    Licensed under GNU GPL 3 license <http://www.gnu.org/licenses/>
*/
namespace infra;

use equal\orm\Model;

class ServiceProvider extends Model {

    public static function getColumns() {
        return [
            'name' => [
                'type'              => 'string',
                'unique'            => true
            ],
            'description' => [
                'type'              => 'string',
                'description'       => 'Short presentation of the Service Provider.',
                'multilang'         => true
            ],
            'login_url'   => [
                'type'              => 'string',
                'description'       => 'Url of the service provider.'
            ],
            'services_ids' => [
                'type'              => 'one2many', 
                'foreign_object'    => 'infra\Service', 
                'foreign_field'     => 'service_provider_id',
                'description'       => 'Services attached to the provider.'
            ]
        ];
    }

}