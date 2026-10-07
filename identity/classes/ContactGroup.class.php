<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2026
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace identity;

use equal\orm\Model;

class ContactGroup extends Model {

    public static function getName() {
        return 'Contact Group';
    }

    public static function getDescription() {
        return 'Contact groups organize contacts independently from user access groups.';
    }

    public static function getColumns() {
        return [
            'name' => [
                'type'        => 'string',
                'description' => 'Name of the contact group.',
                'required'    => true,
                'unique'      => true
            ],

            'description' => [
                'type'        => 'string',
                'usage'       => 'text/plain',
                'description' => 'Purpose of the contact group.'
            ],

            'is_internal' => [
                'type'              => 'boolean',
                'description'       => 'Mark the contact group as relating to the organization.',
                'default'           => false
            ],

            'contacts_ids' => [
                'type'            => 'many2many',
                'foreign_object'  => 'identity\Contact',
                'foreign_field'   => 'contact_groups_ids',
                'rel_table'       => 'identity_rel_contact_group_contact',
                'rel_foreign_key' => 'contact_id',
                'rel_local_key'   => 'contact_group_id',
                'description'     => 'Contacts that belong to the group.'
            ]
        ];
    }

}
