<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace lodging\identity;

class User extends \identity\User {

    public static function getColumns() {
        return [

            'centers_ids' => [
                'type'              => 'many2many',
                'foreign_object'    => 'lodging\identity\Center',
                'foreign_field'     => 'users_ids',
                'rel_table'         => 'lodging_identity_rel_center_user',
                'rel_foreign_key'   => 'center_id',
                'rel_local_key'     => 'user_id'
            ],

            'center_offices_ids' => [
                'type'              => 'many2many',
                'foreign_object'    => 'lodging\identity\CenterOffice',
                'foreign_field'     => 'users_ids',
                'rel_table'         => 'lodging_identity_rel_center_office_user',
                'rel_foreign_key'   => 'center_office_id',
                'rel_local_key'     => 'user_id',
                'onupdate'          => 'onupdateCenterOfficesIds'
            ]

        ];
    }


    public static function onupdateCenterOfficesIds($om, $oids, $lang) {

        $users = $om->read(__CLASS__, $oids, ['centers_ids', 'center_offices_ids.centers_ids'], $lang);
        if($users > 0) {

            foreach($users as $uid => $user) {
                // pass-1 remove previous centers_ids
                $om->write(__CLASS__, $uid, ['centers_ids' => array_map(function($id) { return "-{$id}";}, $user['centers_ids'])], $lang);
                // pass-2 add new centers_ids
                $centers_ids = [];
                foreach($user['center_offices_ids.centers_ids'] as $oid => $office) {
                    $centers_ids = array_merge($centers_ids, $office['centers_ids']);
                }
                $om->write(__CLASS__, $uid, ['centers_ids' => $centers_ids], $lang);
            }
        }
    }
}