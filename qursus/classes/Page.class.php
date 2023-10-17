<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace qursus;

use equal\orm\Model;

class Page extends Model {

    public static function getColumns() {
        return [
            'identifier' => [
                'type'              => 'integer',
                'description'       => 'Unique identifier of the page within the chapter.',
                'default'           => 1
            ],

            'order' => [
                'type'              => 'integer',
                'description'       => 'Position of the page in the chapter.',
                'default'           => 1
            ],

            'next_active' => [
                'type'              => 'computed',
                'description'       => "JSON formatted array of visibility domain for 'next' button.",
                'function'          => 'calcNextActive',
                'result_type'       => 'string',
                'store'             => true
            ],

            'next_active_rule' => [
                'type'              => 'string',
                'selection'         => [
                    'always visible'            => 'always visible',
                    '$page.submitted = true'    => 'page submitted',
                    '$page.selection > 0'       => 'item selected',
                    '$page.actions_counter > 0' => '1 or more actions',
                    '$page.actions_counter > 1' => '2 or more actions',
                    '$page.actions_counter > 2' => '3 or more actions',
                    '$page.actions_counter > 3' => '4 or more actions',
                    '$page.actions_counter > 4' => '5 or more actions',
                    '$page.actions_counter > 5' => '6 or more actions',
                    '$page.actions_counter > 6' => '7 or more actions'
                ],
                'default'           => 'always visible',
                'onupdate'          => 'onupdateNextActive'
            ],

            'leaves' => [
                'type'              => 'alias',
                'alias'             => 'leaves_ids'
            ],

            'leaves_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'qursus\Leaf',
                'foreign_field'     => 'page_id',
                'order'             => 'order',
                'sort'              => 'asc',
                'ondetach'          => 'delete'
            ],

            'sections' => [
                'type'              => 'alias',
                'alias'             => 'sections_ids'
            ],

            'sections_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'qursus\Section',
                'foreign_field'     => 'page_id',
                'order'             => 'order',
                'sort'              => 'asc',
                'ondetach'          => 'delete'
            ],

            'chapter_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'qursus\Chapter',
                'description'       => 'Chapter the page relates to, if any.',
                'ondelete'          => 'cascade',         // delete chapter when parent module is deleted
                'onupdate'          => 'onupdateChapterId'
            ],

            'section_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'qursus\Section',
                'description'       => 'Section the page relates to, if any.',
                'ondelete'          => 'cascade'         // delete chapter when parent module is deleted
            ]

        ];
    }

    public static function calcNextActive($om, $oids, $lang) {
        $result = [];

        $pages = $om->read(__CLASS__, $oids, ['identifier', 'next_active_rule'], $lang);

        foreach($pages as $oid => $page) {
            if($page['next_active_rule'] == 'always visible') {
                $result[$oid] = "[]";
            }
            else {
                $rule = str_replace('$identifier', $page['identifier'], $page['next_active_rule']);
                list($operand, $operator, $value) = explode(' ', $rule);
                if(!is_numeric($value) && !in_array($value, ['true', 'false'])) {
                    $value = "'$value'";
                }
                $result[$oid] = "['$operand','$operator',$value]";
            }
        }

        return $result;
    }

    public static function onupdateNextActive($om, $oids, $values, $lang) {
        $om->write(__CLASS__, $oids, ['next_active' => null], $lang);
    }

    public static function onupdateChapterId($om, $oids, $values, $lang) {
        $pages = $om->read(__CLASS__, $oids, ['chapter_id'], $lang);

        foreach($pages as $oid => $page) {
            Chapter::onupdatePagesIds($om, $page['chapter_id'], $values, $lang);
        }
    }

}