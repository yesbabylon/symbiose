<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

namespace learn;

use equal\orm\Model;

class UserAccess extends Model
{

    public static function getColumns()
    {
        return [
            /* virtual field for generating verification URL */
            'code'       => [
                'type'        => 'computed',
                'result_type' => 'integer',
                'function'    => 'calcCode',
                'store'       => true,
                'description' => 'Unique random identifier.'
            ],

            /* virtual field for retrieving Course based on verification URL */
            'code_alpha' => [
                'type'        => 'computed',
                'result_type' => 'string',
                'function'    => 'calcCodeAlpha',
                'store'       => true,
                'description' => 'Alpha code for retrieval by URL.'
            ],

            'course_id' => [
                'type'           => 'many2one',
                'foreign_object' => 'learn\Course',
                'description'    => 'Program the user is granted access to.',
                'required'       => true,
                'ondelete'       => 'cascade'
                // delete access when parent course is deleted
            ],

            'master_user_id' => [
                'type'        => 'integer',
                'description' => 'External user identifier of the master account (for multiaccounts). If a company has bought a course for its employees, the master account is the one that has bought the course.',
                'default'     => 1
            ],

            'user_id' => [
                'type'        => 'integer',
                'description' => 'External user identifier that is granted access.',
                'default'     => 0
            ],

            'is_complete' => [
                'type'        => 'computed',
                'result_type' => 'boolean',
                'function'    => 'calcIsComplete',
                'description' => 'The user has finished the course.',
                'store'       => true
            ]

        ];
    }

    public function getUnique()
    {
        return [
            ['course_id','user_id']
        ];
    }

    public static function calcIsComplete($om, $oids, $lang)
    {
        $result = [];

        $accesses = $om->read(__CLASS__, $oids, ['course_id',
            'user_id',
            'code',
            'code_alpha'], $lang);

        foreach ($accesses as $aid => $access) {
            // read related course modules ids
            $courses = $om->read('learn\Course', $access['course_id'], ['modules_ids'], $lang);
            $course = array_pop($courses);
            $statuses_ids = $om->search('learn\UserStatus', [['course_id',
                '=',
                $access['course_id']],
                ['user_id',
                    '=',
                    $access['user_id']]]);
            if (!$statuses_ids || count($course['modules_ids']) > count($statuses_ids)) {
                $result[$aid] = false;
                continue;
            }
            $complete = true;
            $statuses = $om->read('learn\UserStatus', $statuses_ids, ['is_complete'], $lang);
            foreach ($statuses as $sid => $status) {
                $complete = $complete & $status['is_complete'];
                if (!$complete) {
                    break;
                }
            }
            $result[$aid] = $complete;
        }

        return $result;
    }


    /**
     * Generate a unique pseudo-random value for the Course.
     */
    public static function calcCode($om, $oids, $lang)
    {
        $result = [];

        $accesses = $om->read(__CLASS__, $oids, ['course_id',
            'user_id'], $lang);

        foreach ($accesses as $oid => $access) {
            trigger_error("ORM::generating code for {$access['course_id']}:{$access['user_id']}", QN_REPORT_DEBUG);
            $result[$oid] = (intval($access['user_id']) * 100) + (intval($access['course_id'])) + 19995;
        }

        return $result;
    }

    /**
     * Compute a alpha code of 4 chars (3 letters + 1 digit) based on numeric code (unique)
     * example : nMa1
     */
    public static function calcCodeAlpha($om, $oids, $lang)
    {
        $result = [];
        $accesses = $om->read(__CLASS__, $oids, ['code'], $lang);

        foreach ($accesses as $oid => $access) {
            $code = $access['code'];
            $a = $code % 10;                    // 0 to 9
            $b = floor($code / 10) % 26;            // 0 to 25
            $c = floor($code / 10 / 26) % 26;        // 0 to 25
            $d = floor($code / 10 / 26 / 26) % 26;    // 0 to 25

            $result[$oid] = chr(ord('a') + $d) . chr(ord('A') + $c) . chr(ord('a') + $b) . strval($a);
        }
        return $result;
    }

}