<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace lodging\identity;

class CenterOffice extends \identity\Establishment {

    public static function getName() {
        return 'Center management office';
    }

    public static function getDescription() {
        return 'Allow support for management of centers by distinct offices.';
    }

    public function getTable() {
        // force table name to use distinct tables and ID columns
        return 'lodging_identity_centeroffice';
    }

    public static function getColumns() {

        return [

            'name' => [
                'type'              => 'string',
                'description'       => 'Name of the Office.'
            ],

            'code' => [
                'type'              => 'string',
                'description'       => 'Numeric identifier of group (1 hex. digit).',
                'usage'             => 'numeric/hexadecimal:1'
            ],

            'centers_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'lodging\identity\Center',
                'foreign_field'     => 'center_office_id',
                'description'       => 'List of centers attached to the group.'
            ],

            'users_ids' => [
                'type'              => 'many2many',
                'foreign_object'    => 'lodging\identity\User',
                'foreign_field'     => 'center_offices_ids',
                'rel_table'         => 'lodging_identity_rel_center_office_user',
                'rel_foreign_key'   => 'user_id',
                'rel_local_key'     => 'center_office_id'
            ],

            'signature' => [
                'type'              => 'string',
                'usage'             => 'markup/html',
                'description'       => 'Office signature to append to communications.',
                'multilang'         => true
            ]

        ];
    }
}