<?php

namespace inventory\product\service;

use equal\orm\Model;

class ServiceProviderDetailCategory extends Model {

    public static function getColumns()
    {
        return [
            'name' => [
                'type'              => 'string',
                'description'       => '',
                'required'          => true,
                'multilang'         => true
            ],

            'description' => [
                'type'              => 'string',
                'description'       => 'Short presentation of the detail category.',
                'multilang'         => true
            ],

            'services_providers_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'inventory\product\service\ServiceProvider',
                'foreign_field'     => 'service_provider_detail_category_id',
                'description'       => 'Service provider that belongs to the category and owns certains details.'
            ],

            'details_ids' => [
                'type'              => 'many2many',
                'foreign_object'    => 'inventory\product\service\Detail',
                'foreign_field'     => 'services_providers_details_categories_ids',
                'rel_table'         => 'inventory_rel_service_provider_detail_category_detail',
                'rel_foreign_key'   => 'detail_id',
                'rel_local_key'     => 'serviceProviderDetailCategory_id',
                'description'       => 'List of product models assigned to this tag.'
            ]
        ];
    }
}
