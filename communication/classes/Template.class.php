<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2024
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

namespace communication;

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
                'foreign_object'    => 'communication\TemplateCategory',
                'description'       => "The category the template belongs to.",
                'dependents'        => ['name'],
                'required'          => true
            ],

            'type' => [
                'type'              => 'string',
                'selection'         => [ 'quote', 'option', 'contract', 'funding', 'invoice' ],
                'description'       => 'The context in which the template is meant to be used.',
                'default'           => 'quote',
                'dependents'        => ['name'],
                'required'          => true
            ],

            'parts_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'communication\TemplatePart',
                'foreign_field'     => 'template_id',
                'description'       => 'List of templates parts related to the template.',
                'ondetach'          => 'delete'
            ],

            'attachments_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'communication\TemplateAttachment',
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
