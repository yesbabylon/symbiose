<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2024
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

namespace identity;

class Organisation extends Identity {

    public static function getName() {
        return 'Organisation';
    }

    public function getTable() {
        return 'identity_organisation';
    }

    public static function getDescription() {
        return 'Organizations are the legal entities to which the ERP is dedicated. By convention, the main Organization uses ID 1.';
    }

    public static function getColumns() {
        return [
            'type_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'identity\IdentityType',
                'onupdate'          => 'onupdateTypeId',
                'description'       => 'Type of identity.',
                'default'           => 3
            ],

            'type' => [
                'type'              => 'string',
                'default'           => 'C',
                'readonly'          => true
            ],

            'image_document_id' => [
                'type'           => 'many2one',
                'foreign_object' => 'documents\Document',
                'description'    => 'Organisation logo or picture.',
                'help'           => 'This image is used for organisation profile and within the invoice header.'
            ]

        ];
    }
}
