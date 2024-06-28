<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2024
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

namespace communication\template;

use equal\orm\Model;

class TemplateCategory extends Model {

    public static function getColumns() {
        return [

            'name' => [
                'type'              => 'string',
                'description'       => "Short label to ease identification of the category.",
                'dependents'        => ['templates_ids' => ['name']],
                'required'          => true,
                'unique'            => true
            ],

            'description' => [
                'type'              => 'string',
                'description'       => "Short description of category and intended usage.",
                'multilang'         => true
            ],

            'templates_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'communication\template\Template',
                'foreign_field'     => 'category_id',
                'description'       => "Templates that are related to this category, if any."
            ]

        ];
    }
}