<?php
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