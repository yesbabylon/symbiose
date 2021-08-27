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
                'rel_table'         => 'sale_identity_rel_center_user',
                'rel_foreign_key'   => 'center_id',
                'rel_local_key'     => 'user_id'
            ]

        ];
    }

}