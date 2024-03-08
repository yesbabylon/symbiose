<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace learn;

use equal\orm\Model;

class Group extends Model {

    public static function getColumns() {
        return [
            'identifier' => [
                'type'              => 'integer',
                'description'       => 'Unique identifier of the group within the leaf.',
                'onupdate'          => 'onupdateVisibility',
                'default'           => 1
            ],

            'order' => [
                'type'              => 'integer',
                'description'       => 'Position of the group in the leaf.',
                'default'           => 1
            ],

            'direction' => [
                'type'              => 'string',
                'selection'         => ['horizontal', 'vertical'],
                'default'           => 'vertical'
            ],

            'row_span' => [
                'type'              => 'integer',
                'default'           => 1,
                'description'       => "Height of the group, in rows (default = 1, max = 8)"
            ],

            'visible' => [
                'type'              => 'computed',
                'function'          => 'calcVisible',
                'result_type'       => 'string',
                'store'             => true
            ],

            'visibility_rule' => [
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
                'onupdate'          => 'onupdateVisibility'
            ],

            'fixed' => [
                'type'              => 'boolean',
                'default'           => false,
                'description'       => "If true, group is always visible."
            ],

            'widgets' => [
                'type'              => 'alias',
                'alias'             => 'widgets_ids'
            ],

            'widgets_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'learn\Widget',
                'foreign_field'     => 'group_id',
                'order'             => 'order',
                'sort'              => 'asc',
                'ondetach'          => 'delete'
            ],

            'leaf_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'learn\Leaf',
                'description'       => 'Leaf the group relates to.',
                'ondelete'          => 'cascade'         // delete group when parent leaf is deleted
            ]

        ];
    }


    public static function calcVisible($om, $oids, $lang) {
        $result = [];

        $groups = $om->read(__CLASS__, $oids, ['identifier', 'visibility_rule'], $lang);

        foreach($groups as $oid => $group) {
            if($group['visibility_rule'] == 'always visible') {
                $result[$oid] = "[]";
            }
            else {
                $rule = str_replace('$identifier', $group['identifier'], $group['visibility_rule']);
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