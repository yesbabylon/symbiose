<?php
/*
    This file is part of the eQual framework <http://www.github.com/cedricfrancoys/equal>
    Some Rights Reserved, Cedric Francoys, 2010-2021
    Licensed under GNU GPL 3 license <http://www.gnu.org/licenses/>
*/

namespace inventory\product\service;

use equal\orm\Model;

class Detail extends Model {

    public static function getColumns()
    {
        return [
            'name' => [
                'type'              => 'string',
                'unique'            => true,
                'description'       => 'Unique identifier of the detail (ex: code, key, nic, ...).'
            ],

            'description' => [
                'type'              => 'string',
                'description'       => 'Short presentation of the detail element.',
                'multilang'         => true
            ],

            'value' => [
                'type'              => 'string',
                'description'       => 'Server attached to the product.',
                'multilang'         => true
                // est-ce correct ? Et je sais que potentiellement il y aura encore une table de transition.
            ],

            'service_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'inventory\product\service\Service',
                'description'       => 'Detail attached to a service.'
            ],

            'services_providers_details_categories_ids' => [
                'type'              => 'many2many',
                'foreign_object'    => 'inventory\product\service\ServiceProviderDetailCategory',
                'foreign_field'     => 'details_ids',
                'rel_table'         => 'inventory_rel_service_provider_detail_category_detail',
                'rel_foreign_key'   => 'serviceProviderDetailCategory_id',
                'rel_local_key'     => 'detail_id',
                'description'       => 'List of product models assigned to this tag.'
            ]
        ];
    }
}
