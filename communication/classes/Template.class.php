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
                'type'              => 'computed',
                'result_type'       => 'string',
                'description'       => "Code of the template (allows duplicates).",
                'function'          => 'communication\Template::getDisplayName',
                'store'             => true
            ],

            'code' => [
                'type'              => 'string',
                'description'       => "Code of the template (allows duplicates).",
                'required'          => true,
                'onchange'          => 'communication\Template::onChangeCode'
            ],

            'description' => [
                'type'              => 'string',
                'description'       => "Role and intended usage of the template.",
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
                'description'       => 'The context in which the template is meant to be used.',
                'onchange'          => 'communication\Template::onChangeType'
            ],

            'parts_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'communication\TemplatePart',
                'foreign_field'     => 'template_id',
                'description'       => 'List of templates parts related to the template.'
            ],

            'attachments_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'communication\TemplateAttachment',
                'foreign_field'     => 'template_id',
                'description'       => 'List of attachments related to the template, if any.'
            ]

        ];
    }

    public static function getDisplayName($om, $oids, $lang) {
        $result = [];

        $templates = $om->read(__CLASS__, $oids, ['code', 'type', 'category_id.name'], $lang);

        foreach($templates as $oid => $template) {
            $result[$oid] = $template['category_id.name'].'.'.$template['type'].'.'.$template['code'];
        }
        return $result;
    }

    public static function onChangeCode($orm, $oids, $lang) {
        $orm->write(__CLASS__, $oids, ['name' => null], $lang);
        $orm->read(__CLASS__, $oids, ['name'], $lang);
    }

    public static function onChangeType($orm, $oids, $lang) {
        $orm->write(__CLASS__, $oids, ['name' => null], $lang);
        $orm->read(__CLASS__, $oids, ['name'], $lang);
    }

}