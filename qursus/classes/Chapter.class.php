<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace qursus;

use equal\orm\Model;

class Chapter extends Model {

    public static function getColumns() {
        return [
            'identifier' => [
                'type'              => 'integer',
                'description'       => 'Unique identifier of the chapter within the module.',
                'default'           => 1
            ],

            'order' => [
                'type'              => 'integer',
                'description'       => 'Position of the chapter in the module.',
                'default'           => 1
            ],

            'name' => [
                'type'              => 'alias',
                'alias'             => 'title'
            ],

            'title' => [
                'type'              => 'string',
                'required'          => true,
                'multilang'         => true
            ],

            'description' => [
                'type'              => 'string',
                'usage'             => 'text/plain',
                'multilang'         => true
            ],

            'module_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'qursus\Module',
                'description'       => 'Module the chapter relates to.',
                'required'          => true,
                'ondelete'          => 'cascade',         // delete chapter when parent module is deleted
                'onupdate'          => 'onupdateModuleId'
            ],

            'page_count' => [
                'type'              => 'computed',
                'description'       => "Total amount of pages in the chapter.",
                'function'          => 'calcPageCount',
                'result_type'       => 'integer',
                'store'             => true
            ],

            'pages' => [
                'type'              => 'alias',
                'alias'             => 'pages_ids'
            ],

            'pages_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'qursus\Page',
                'foreign_field'     => 'chapter_id',
                'order'             => 'order',
                'sort'              => 'asc',
                'ondetach'          => 'delete',
                'onupdate'          => 'onupdatePagesIds'
            ]

        ];
    }

    public static function calcPageCount($om, $oids, $lang) {
        $result = [];

        $chapters = $om->read(__CLASS__, $oids, ['pages_ids'], $lang);

        foreach($chapters as $oid => $chapter) {
            $result[$oid] = count($chapter['pages_ids']);
        }

        return $result;
    }


    public static function onupdatePagesIds($orm, $oids, $values, $lang) {
        // force refresh page_count
        $orm->write(__CLASS__, $oids, ['page_count' => null], $lang);

        // refresh parent modules (will trigger back Chapter::calcPageCount)
        $chapters = $orm->read(__CLASS__, $oids, ['module_id'], $lang);
        $modules_ids = [];
        foreach($chapters as $oid => $chapter) {
            // force refresh page_count
            $orm->write('qursus\Module', $chapter['module_id'], ['page_count' => null], $lang);
            $modules_ids[$chapter['module_id']] = true;
        }
        $orm->read('qursus\Module', array_keys($modules_ids), ['page_count'], $lang);
    }


    public static function onupdateModuleId($om, $oids, $values, $lang) {
        $chapters = $om->read(__CLASS__, $oids, ['module_id'], $lang);

        foreach($chapters as $oid => $chapter) {
            Module::onchangeChaptersIds($om, $chapter['module_id'], $lang);
        }
    }


}