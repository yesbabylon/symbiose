<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace qursus;

use equal\orm\Model;

class Leaf extends Model {

    public static function getColumns() {
        return [
            'identifier' => [
                'type'              => 'integer',
                'description'       => 'Unique identifier of the leaf within the page.',
                'onupdate'          => 'onupdateVisibility',
                'default'           => 1
            ],

            'order' => [
                'type'              => 'integer',
                'description'       => 'Position of the leaf in the page.',
                'default'           => 1
            ],

            'visible' => [
                'type'              => 'computed',
                'function'          => 'calcVisible',
                'result_type'       => 'string',
                'store'             => true,
            ],

            'visibility_rule' => [
                'type'              => 'string',
                'selection'         => [
                    'always visible'                    => 'always visible',
                    '$page.selection = $identifier'     => 'selection matches identifier',
                    '$page.submitted = true'            => 'page submitted',
                    '$page.submitted = false'           => 'page not submitted'
                ],
                'default'           => 'always visible',
                'onupdate'          => 'onupdateVisibility'
            ],
            
            'groups' => [
                'type'              => 'alias',
                'alias'             => 'groups_ids'
            ],

            'groups_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'qursus\Group',
                'foreign_field'     => 'leaf_id',
                'order'             => 'order',
                'sort'              => 'asc',
                'ondetach'          => 'delete'
            ],

            'background_image' => [
                'type'              => 'string',
                'description'       => "URL of the background image."
            ],

            'background_stretch' => [
                'type'              => 'boolean',
                'description'       => "JSON formatted array of visibility domain for leaf.",
                'default'           => false
            ],

            'background_opacity' => [
                'type'              => 'float',
                'description'       => "Opacity of the background (from 0 to 1).",
                'default'           => 0.5
            ],
    
            'contrast' => [
                'type'              => 'string',
                'selection'         => ['dark', 'light'],
                'default'           => 'light'
            ],

            'page_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'qursus\Page',
                'description'       => 'Page the leaf relates to.',
                'ondelete'          => 'cascade'         // delete leaf when parent page is deleted
            ]

        ];
    }

    public static function calcVisible($om, $oids, $lang) {
        $result = [];

        $leaves = $om->read(__CLASS__, $oids, ['identifier', 'visibility_rule'], $lang);

        foreach($leaves as $oid => $leaf) {
            if($leaf['visibility_rule'] == 'always visible') {
                $result[$oid] = "[]";                
            }
            else {
                $rule = str_replace('$identifier', $leaf['identifier'], $leaf['visibility_rule']);
                list($operand, $operator, $value) = explode(' ', $rule);
                if(!is_numeric($value) && !in_array($value, ['true', 'false'])) {
                    $value = "'$value'";
                }
                $result[$oid] = "['$operand','$operator',$value]";
            }
        }

        return $result;
    }    
    
    public static function onupdateVisibility($om, $oids, $values, $lang) {
        $om->write(__CLASS__, $oids, ['visible' => null], $lang);
    }
}