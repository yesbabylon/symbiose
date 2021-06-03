<?php
namespace sale\product;
use equal\orm\Model;

class Option extends Model {
    public static function getColumns() {
        /**
         */

        return [
            'name' => [
                'type'              => 'string',
                'description'       => 'Unique name of this option.'
            ],
            'description' => [
                'type'              => 'string',
                'description'       => "Short description of the option."
            ],
            'family_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\product\Family',
                'description'       => "Product Family this option belongs to.",
                'required'          => true
            ]
        ];
    }
}