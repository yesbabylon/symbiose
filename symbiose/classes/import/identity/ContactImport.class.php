<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2026
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

namespace symbiose\import\identity;

use symbiose\import\DataImport;

class ContactImport extends DataImport {

    public static function getName() {
        return "Contact import";
    }

    public static function getDescription() {
        return "A contact import allows to import contacts from an uploaded document.";
    }

    public static function getColumns() {
        return [

            'import_type' => [
                'type'              => 'string',
                'description'       => 'Forced type of import.',
                'default'           => 'contacts_import'
            ],

            'data' => [
                'type'              => 'binary',
                'description'       => 'Raw binary data of the uploaded document',
                'help'              => 'This field is meant to be used for the subsequent document creation, and is emptied once the document creation is confirmed.',
                'onupdate'          => 'onupdateData'
            ]

        ];
    }

    public static function getParseXlsConfig() {
        return [
            'default' => [
                'firstname',
                'lastname',
                'email',
                'phone',
                'mobile',
                'address_street',
                'address_dispatch',
                'address_city',
                'address_zip',
                'address_state',
                'address_country'
            ]
        ];
    }

    public static function getParseCsvConfig() {
        return [
            'firstname',
            'lastname',
            'email',
            'phone',
            'mobile',
            'address_street',
            'address_dispatch',
            'address_city',
            'address_zip',
            'address_state',
            'address_country'
        ];
    }
}
