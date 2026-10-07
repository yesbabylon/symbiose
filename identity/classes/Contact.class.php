<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace identity;

class Contact extends IdentityFacetAbstract {

    public static function getName() {
        return "Contact";
    }

    public static function getDescription() {
        return "Contacts are persons that are attached to an identity.";
    }

    public static function getColumns() {

        return [

            'relationship' => [
                'type'              => 'string',
                'default'           => 'contact',
                'help'              => "The partnership should remain 'contact'."
            ],

            'position' => [
                'type'              => 'string',
                'description'       => 'Position of the contact (natural person) within the target organisation (legal person), e.g. \'director\', \'CEO\', \'Regional manager\'.',
                'visible'           => [ ['relationship', '=', 'contact'] ]
            ],

            'is_internal' => [
                'type'              => 'boolean',
                'description'       => 'Mark the contact as relating to the organization.',
                'default'           => false
            ],

            'contact_groups_ids' => [
                'type'            => 'many2many',
                'foreign_object'  => 'identity\ContactGroup',
                'foreign_field'   => 'contacts_ids',
                'rel_table'       => 'identity_rel_contact_group_contact',
                'rel_foreign_key' => 'contact_group_id',
                'rel_local_key'   => 'contact_id',
                'description'     => 'Contact groups to which the identity belongs.'
            ],

        ];
    }

    public function getUniques(): array {
        return [['identity_id']];
    }

}
