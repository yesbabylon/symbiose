<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace inventory;

use equal\orm\Model;

class Provider extends Model {
    public static function getColumns() {
        /**
        *
        */
        return [
            'name' => [
                'type'              => 'string',
                'description'       => "Provider name or memo.",
                'required'          => true
            ],
            'description' => [
                'type'              => 'string',
                'description'       => "Short description of the provider.",
                'required'          => true
            ],
            'login_url' => [
                'type'              => 'string',
                'description'       => "URL for signing in."
            ],

            
        ];
    }
}