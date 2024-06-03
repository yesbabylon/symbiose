<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2024
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

namespace inventory\service;

use purchase\supplier\Supplier;

class ServiceProvider extends Supplier {

    public static function getDescription() {
        return 'ServiceProvider class extends Supplier, managing providers login URL, category, and associated services.';
    }

    public static function getColumns()
    {
        return [

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
