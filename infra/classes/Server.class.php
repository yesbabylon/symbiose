<?php
/*
    This file is part of the eQual framework <http://www.github.com/cedricfrancoys/equal>
    Some Rights Reserved, Cedric Francoys, 2010-2021
    Licensed under GNU GPL 3 license <http://www.gnu.org/licenses/>
*/
namespace infra;

use equal\orm\Model;

class Server extends Model {

    public static function getColumns() {
        return [
            'label' => [
                'type'              => 'string',
                'unique'            => true
            ],
            'description' => [
                'type'              => 'string',
                'description'       => 'Informations about a server.',
                'multilang'         => true
            ],
            'host' => [
                'type'              => 'string',
                'description'       => 'Host of the server.'
            ],
            'type' => [
                'type'              => 'string',
                'description'       => 'Type of the server.'
            ],
            'IPV4' => [
                'type'              => 'string',
                'description'       => 'IP adress of the server (32 bits)'
            ],
            'IPV6' => [
                'type'              => 'string',
                'description'       => 'IP adress of the server (128 bits)'
            ],
            'access_ids' => [                
                'type'              => 'one2many',
                'foreign_object'    => 'infra\Access', 
                'foreign_field'     => 'server_id', 
                'description'       => 'Access informations to the server.'
            ],
            'details_ids' => [
                'type'              => 'one2many', 
                'foreign_object'    => 'infra\Detail', 
                'foreign_field'     => 'server_id',
                'description'       => 'Details about the service.'
            ],
            'product_id' => [
                'type'              => 'many2one', 
                'foreign_object'    => 'infra\Product', 
                'description'       => 'Product attached to the server.'
            ],
        ];
    }

}