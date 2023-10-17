<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace qursus;

use equal\orm\Model;

class UserAccess extends Model {

    public static function getColumns() {
        return [
            /* virtual field for generating verification URL */
            'code' => [
                'type'              => 'computed',                
                'result_type'       => 'integer',
                'function'          => 'qursus\UserAccess::getCode',
                'store'             => true,
                'description'       => 'Unique random identifier.'
            ],

            /* virtual field for retrieving Pack based on verification URL */
            'code_alpha' => [
                'type'              => 'computed',
                'result_type'       => 'string',
                'function'          => 'qursus\UserAccess::getCodeAlpha',
                'store'             => true,
                'description'       => 'Alpha code for retrieval by URL.'
            ],

            'pack_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'qursus\Pack',
                'description'       => 'Program the user is granted access to.',
                'required'          => true,
                'ondelete'          => 'cascade'         // delete access when parent module is deleted
            ],

            'master_user_id' => [
                'type'              => 'integer',
                'description'       => 'External user identifier of the master account (for multiaccounts).',
                'default'           => 1
            ],

            'user_id' => [
                'type'              => 'integer',
                'description'       => 'External user identifier that is granted access.',
                'default'           => 0
            ],

            'is_complete' => [
                'type'              => 'computed',
                'result_type'       => 'boolean',
                'function'          => 'qursus\UserAccess::getIsComplete',
                'description'       => 'The user has finished the program.',
                'store'             => true
            ]

        ];
    }

    public function getUnique() {
        return [
            ['pack_id', 'user_id']
        ];
    }

    public static function getIsComplete($om, $oids, $lang) {
        $result = [];

        $accesses = $om->read(__CLASS__, $oids, ['pack_id', 'user_id', 'code', 'code_alpha'], $lang);

        foreach($accesses as $aid => $access) {
            // read related pack modules ids
            $packs = $om->read('qursus\Pack', $access['pack_id'], ['modules_ids'], $lang);
            $pack = array_pop($packs);
            $statuses_ids = $om->search('qursus\UserStatus', [ ['pack_id', '=', $access['pack_id']], ['user_id', '=', $access['user_id']] ]);
            if(!$statuses_ids || count($pack['modules_ids']) > count($statuses_ids)) {
                $result[$aid] = false;
                continue;
            }
            $complete = true;
            $statuses = $om->read('qursus\UserStatus', $statuses_ids, ['is_complete'], $lang);
            foreach($statuses as $sid => $status) {
                $complete = $complete & $status['is_complete'];
                if(!$complete) {
                    break;
                }
            }
            $result[$aid] = $complete;
        }

        return $result;
    }


    /**
     * Generate a unique pseudo-random value for the Pack.
     */
    public static function getCode($om, $oids, $lang) {
        $result = [];

        $accesses = $om->read(__CLASS__, $oids, ['pack_id', 'user_id'], $lang);

        foreach($accesses as $oid => $access) {
            trigger_error("ORM::generating code for {$access['pack_id']}:{$access['user_id']}", QN_REPORT_DEBUG);
            $result[$oid] = (intval($access['user_id']) * 100) + (intval($access['pack_id'])) + 19995;
        }

        return $result;
    }

    /**
     * Compute a alpha code of 4 chars (3 letters + 1 digit) based on numeric code (unique)
     * example : nMa1
     */
    public static function getCodeAlpha($om, $oids, $lang) {
        $result = [];
        $accesses = $om->read(__CLASS__, $oids, ['code'], $lang);

        foreach($accesses as $oid => $access) {
            $code = $access['code'];
            $a = $code % 10;				    // 0 to 9
            $b = floor($code/10) % 26;		    // 0 to 25
            $c = floor($code/10/26) % 26;		// 0 to 25
            $d = floor($code/10/26/26) % 26;	// 0 to 25

            $result[$oid] = chr(ord('a') + $d).chr(ord('A') + $c).chr(ord('a') + $b).strval($a);
        }
        return $result;
    }

}