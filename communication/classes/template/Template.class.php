<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2024
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

namespace communication\template;

use equal\orm\Model;

class Template extends Model {

    public static function getColumns() {
        return [

            'name' => [
                'type'              => 'computed',
                'result_type'       => 'string',
                'description'       => "Complete code of the template.",
                'function'          => 'calcName',
                'store'             => true,
                'instant'           => true,
                'readonly'          => true
            ],

            'code' => [
                'type'              => 'string',
                'description'       => "Code of the template (allows duplicates).",
                'required'          => true,
                'dependents'        => ['name']
            ],

            'description' => [
                'type'              => 'string',
                'usage'             => 'text/plain',
                'description'       => "Role and intended usage of the template.",
                'multilang'         => true
            ],

            'category_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'communication\template\TemplateCategory',
                'description'       => "The category the template belongs to.",
                'dependents'        => ['name'],
                'required'          => true
            ],

            'type_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'communication\template\TemplateType',
                'description'       => "The type the template refers to.",
                'dependents'        => ['type', 'name'],
                'required'          => true
            ],

            'category' => [
                'type'              => 'computed',
                'result_type'       => 'string',
                'description'       => 'The code of the assigned category (for filtering).',
                'relation'          => ['category_id' => 'code'],
                'store'             => true,
                'readonly'          => true
            ],

            'type' => [
                'type'              => 'computed',
                'result_type'       => 'string',
                'description'       => 'The code of the assigned type (for filtering).',
                'relation'          => ['type_id' => 'code'],
                'store'             => true,
                'readonly'          => true
            ],

            'parts_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'communication\template\TemplatePart',
                'foreign_field'     => 'template_id',
                'description'       => 'List of templates parts related to the template.',
                'ondetach'          => 'delete'
            ],

            'attachments_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'communication\template\TemplateAttachment',
                'foreign_field'     => 'template_id',
                'description'       => 'List of attachments related to the template, if any.'
            ]

        ];
    }

    public static function calcName($self) {
        $result = [];
        $self->read(['code', 'type', 'category_id' => ['name']]);
        foreach($self as $id => $template) {
            $result[$id] = $template['category_id']['name'].'.'.$template['type'].'.'.$template['code'];
        }

        return $result;
    }
}
