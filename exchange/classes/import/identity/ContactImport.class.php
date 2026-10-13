<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2026
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

namespace exchange\import\identity;

use exchange\import\DataImport;

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
