<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace identity;

class User extends \core\User {

    public static function getName() {
        return 'User';
    }

    public static function getColumns() {
        return [

            'name' => [
                'type'              => 'computed',
                'function'          => 'calcName',
                'result_type'       => 'string',
                'store'             => true,
                'description'       => 'The display name of the user.'
            ],

            'identity_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'identity\Identity',
                'domain'            => ['type', '=', 'I'],
                'description'       => 'The contact related to the user.',
                'dependencies'      => ['name']
            ],

            'setting_values_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'core\setting\SettingValue',
                'foreign_field'     => 'user_id',
                'description'       => 'List of settings that relate to the user.'
            ],

            /* the organization the user is part of (multi-company support) */
            'organisation_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'identity\Organisation',
                'description'       => "The organization the user relates to (defaults to current).",
                'default'           => 1
            ]
        ];
    }

    public static function calcName($self) {
        $result = [];
        $self->read(['login', 'identity_id' => ['name']]);
        foreach($self as $id => $user) {
            if(isset($user['identity_id']['name']) && strlen($user['identity_id']['name']) ) {
                $result[$id] = $user['identity_id']['name'];
            }
            else {
                $result[$id] = $user['login'];
            }
        }
        return $result;
    }

}