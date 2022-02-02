<?php
/*
    This file is part of the eQual framework <http://www.github.com/cedricfrancoys/equal>
    Some Rights Reserved, Cedric Francoys, 2010-2021
    Licensed under GNU GPL 3 license <http://www.gnu.org/licenses/>
*/
namespace infra;

use equal\orm\Model;

class Group extends Model {

    public static function getColumns() {
        return [
            'name' => [
                'type'              => 'string',
                'unique'            => true
            ],
            'description' => [
                'type'              => 'string',
                'description'       => 'Informations about a service.',
                'multilang'         => true
            ],
            'access_ids' => [                
                'type'              => 'one2many',
                'foreign_object'    => 'infra\Access', 
                'foreign_field'     => 'service_id', 
                'description'       => 'Access informations to the service.'
            ],
            'details_ids' => [
                'type'              => 'one2many', 
                'foreign_object'    => 'infra\Detail', 
                'foreign_field'     => 'service_id',
                'description'       => 'Details about the service.'
            ],
            'service_provider_id' => [
                'type'              => 'many2one', 
                'foreign_object'    => 'infra\ServiceProvider', 
                'description'       => 'Details about the service provider.'
            ],
            'product_id' => [
                'type'              => 'many2one', 
                'foreign_object'    => 'infra\Product', 
                'description'       => 'Details about the service.'
            ],
        ];
    }

}