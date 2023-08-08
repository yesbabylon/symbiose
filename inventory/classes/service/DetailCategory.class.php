<?php

namespace inventory\service;

use equal\orm\Model;

class DetailCategory extends Model {

    public static function getColumns()
    {
        return [
            'name' => [
                'type'              => 'string',
                'description'       => 'Name of the detail category.',
                'unique'            => true,
                'required'          => true
            ],

            'description' => [
                'type'              => 'string',
                'description'       => 'Short presentation of the detail category.'
            ],

            'details_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'inventory\service\Detail',
                'foreign_field'     => 'detail_category_id',
                'description'       => 'The details of the detail category .'
            ]
        ];
    }
}
