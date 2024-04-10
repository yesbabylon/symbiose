<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace identity;

class Contact extends Partner {

    public function getTable() {
        // force table name to use distinct tables and ID columns
        return 'identity_contact';
    }

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
            ]

        ];
    }
    public static function onafterupdate($self, $values) {
        parent::onafterupdate($self, $values);

        $self->read(['partner_identity_id' => ['id', 'contact_id']]);
        foreach($self as $id => $contact) {
            if(is_null($contact['partner_identity_id']['contact_id'])) {
                Identity::id($contact['partner_identity_id']['id'])->update(['contact_id' => $id]);
            }
        }
    }

}