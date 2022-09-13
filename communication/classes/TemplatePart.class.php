<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace communication;
use equal\orm\Model;

class TemplatePart extends Model {
    public static function getColumns() {
        /**
         */

        return [
            'name' => [
                'type'              => 'string',
                'description'       => "Code of the template part.",
                'required'          => true
            ],

            'value' => [
                'type'              => 'string',
                'usage'             => 'text/html',
                'description'       => "Template body (html).",
                'multilang'         => true
            ],

            'template_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'communication\Template',
                'description'       => "The template the part belongs to.",
                'required'          => true
            ]

        ];
    }

}