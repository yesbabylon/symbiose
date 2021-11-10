<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace communication;
use equal\orm\Model;

class Template extends Model {
    public static function getColumns() {
        /**
         */

        return [
            'name' => [
                'type'              => 'string',
                'description'       => "Code of the template (allows duplicates).",
                'required'          => true
            ],

            'description' => [
                'type'              => 'string',
                'description'       => "Role and intended usage of the template.",
                'multilang'         => true
            ],

            'value' => [
                'type'              => 'string',
                'usage'             => 'markup/html',
                'description'       => "Template body (html).",
                'multilang'         => true
            ],

            'category_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'communication\TemplateCategory',
                'description'       => "The category the template belongs to.",
                'required'          => true
            ],

            'type' => [
                'type'              => 'string',
                'selection'         => [ 'quote', 'contract', 'invoice' ],
                'description'       => 'The context in which the template is meant to be used.'
            ]

        ];
    }

}