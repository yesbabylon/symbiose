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
            ]

        ];
    }

}