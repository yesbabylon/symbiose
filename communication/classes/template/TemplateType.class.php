<?php
/*
    Developed by Yesbabylon – https://yesbabylon.com
    (c) 2025–2026 Yesbabylon SA
    Licensed under the GNU AGPL v3 License – https://www.gnu.org/licenses/agpl-3.0.html
*/

namespace communication\template;

use equal\orm\Model;

class TemplateType extends Model {

    public static function getColumns() {
        return [

            'name' => [
                'type'              => 'string',
                'description'       => "Short label of the type.",
                'dependents'        => ['templates_ids' => ['name']],
                'required'          => true,
                'multilang'         => true
            ],

            'code' => [
                'type'              => 'string',
                'description'       => "Unique code for identifying the type.",
                'required'          => true,
                'unique'            => true
            ],

            'description' => [
                'type'              => 'string',
                'description'       => "Short description of the type and intended usage.",
                'multilang'         => true
            ],

            'templates_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'communication\template\Template',
                'foreign_field'     => 'type_id',
                'description'       => "Templates that are related to this category, if any."
            ]

        ];
    }
}
