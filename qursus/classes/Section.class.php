<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace qursus;

use equal\orm\Model;

class Section extends Model {

    public static function getColumns() {
        return [
            'identifier' => [
                'type'              => 'integer',
                'description'       => 'Unique id of the section within the page.',
                'default'           => 1
            ],

            'order' => [
                'type'              => 'integer',
                'description'       => 'Position of the section in the page.',
                'default'           => 1
            ],

            'name' => [            
                'type'              => 'computed',
                'function'          => 'qursus\Section::getDisplayName',
                'result_type'       => 'string',
                'store'             => true,
            ],


            'pages' => [
                'type'              => 'alias',
                'alias'             => 'pages_ids'
            ],

            'pages_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'qursus\Page',
                'foreign_field'     => 'section_id',
                'order'             => 'order',
                'sort'              => 'asc',
                'ondetach'          => 'delete'
            ],

            'page_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'qursus\Page',
                'description'       => 'Parent page.',
                'ondelete'          => 'cascade'         // delete section when parent page is deleted
            ]

        ];
    }
    

    public static function getDisplayName($om, $oids, $lang) {
        $result = [];

        $sections = $om->read(__CLASS__, $oids, ['id', 'identifier'], $lang);

        foreach($sections as $oid => $section) {
            $result[$oid] = sprintf("%d-%d", $section['id'], $section['identifier']);
        }

        return $result;
    }

}