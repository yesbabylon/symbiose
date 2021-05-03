<?php
namespace symbiose\inventory\asset;


class Product extends \qinoa\orm\Model {
    public static function getColumns() {
        /**
        *
        */
        return [
            'name' => [
                'type'              => 'string',
                'description'       => "the name of the product, by convention FQDN (if applicable)"
            ],
            'description' => [
                'type'              => 'string',
                'description'       => "short presentation of the product"
            ],
            'instances_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'symbiose\inventory\asset\Instance',
                'foreign_field'     => 'product_id',
                'description'       => "instances of the product (dev, staging, prod)"
            ],
            'services_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'symbiose\inventory\asset\Service',
                'foreign_field'     => 'product_id',                
                'description'       => "services used by the product (mass-mailing, SSL certificate, API providers, ...)"
            ]
        ];
    }
}