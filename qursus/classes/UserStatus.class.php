<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace qursus;

use equal\orm\Model;

class UserStatus extends Model {

    public static function getColumns() {
        return [

            'pack_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'qursus\Pack',
                'description'       => 'Pack identifier (for computing completeness of a whole pack).',
                'required'          => true,
                'ondelete'          => 'cascade'         // delete status when parent pack is deleted
            ],

            'module_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'qursus\Module',
                'description'       => 'Module the status relates to.',
                'required'          => true,
                'ondelete'          => 'cascade'         // delete status when parent module is deleted
            ],

            'user_id' => [
                'type'              => 'integer',
                'description'       => 'External user identifier.',
                'default'           => 1                
            ],

            'chapter_index' => [            
                'type'              => 'integer',
                'description'       => 'Chapter index within the module.',
                'default'           => 0
            ],

            'page_index' => [
                'type'              => 'integer',
                'description'       => 'Page index within the chapter.',
                'default'           => 0
            ],

            'page_count' => [
                'type'              => 'integer',
                'description'       => 'Number of pages reviewed so far.',
                'default'           => 1
            ],

            'is_complete' => [
                'type'              => 'boolean',
                'description'       => 'The user has finished the module.',
                'default'           => false,
                'onupdate'          => 'qursus\UserStatus::onupdateIsComplete'                
            ]

        ];
    }    

    public function getUnique() {
        return [
            ['module_id', 'user_id']
        ];
    }    

    public static function onupdateIsComplete($orm, $oids, $values, $lang) {
        
        $statuses = $orm->read(__CLASS__, $oids, ['pack_id', 'user_id'], $lang);

        if($statuses && count($statuses)) {
            foreach($statuses as $oid => $status) {
                $pack_id = $status['pack_id'];
                $user_id = $status['user_id'];
                $ids = $orm->search('qursus\UserAccess', [ ['user_id', '=', $user_id], ['pack_id', '=', $pack_id] ]);
                if($ids && count($ids)) {
                    // reset related UserAccess is_complete                
                    $orm->write('qursus\UserAccess', $ids, ['is_complete' => null], $lang);
                    // and force immediate refresh
                    $orm->read('qursus\UserAccess', $ids, ['is_complete'], $lang);
                }
            }
        }
        
    }

}