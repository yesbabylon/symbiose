<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace identity;
use equal\orm\Model;

class Establishment extends Model {

    public static function getName() {
        return "Establishment unit";
    }

    public static function getDescription() {
        return "A corporation always have a single headquarters address, but can have several places of operations or offices (establishment units).";
    }

    public static function getColumns() {

        return [
            'name' => [
                'type'              => 'string',
                'description'       => "Name of the establishment unit.",
                'required'          => true
            ],

            'legal_name' => [
                'type'              => 'string',
                'description'       => "Official name of the establishment (as displayed in the address).",
                'required'          => true
            ],

            'description' => [
                'type'              => 'string',
                'usage'             => 'text/plain',
                'description'       => 'Details about the property for inner communications.'
            ],

            /* parent organisation */
            'organisation_id' => [
                'type'              => 'many2one',
                'foreign_object'    => Identity::getType(),
                'description'       => "The organisation the establishment belongs to.",
                'required'          => true
            ],

            'phone' => [
                'type'              => 'string',
                'usage'             => 'phone',
                'description'       => 'Official contact phone number.'
            ],

            'fax' => [
                'type'              => 'string',
                'usage'             => 'phone',
                'description'       => "Identity main fax number."
            ],

            'email' => [
                'type'              => 'string',
                'usage'             => 'email',
                'description'       => 'Official contact email address for the establishment.'
            ],

            'address_street' => [
                'type'              => 'string',
                'description'       => 'Street and number of the estalishment address.',
                'required'          => true
            ],

            'address_dispatch' => [
                'type'              => 'string',
                'description'       => 'Optional info for mail dispatch (appartment, box, floor, ...).'
            ],

            'address_city' => [
                'type'              => 'string',
                'description'       => 'City in which establishment is located.'
            ],

            'address_zip' => [
                'type'              => 'string',
                'description'       => 'Postal code of the establishment address.'
            ],

            'address_state' => [
                'type'              => 'string',
                'description'       => 'State or region.'
            ],

            'address_country' => [
                'type'              => 'string',
                'usage'             => 'country/iso-3166:2',
                'description'       => 'Country in which the establishment is located (ISO 3166).'
            ],

            'registration_number' => [
                'type'              => 'string',
                'description'       => 'Establishment registration number (establishment unit number), if any.'
            ],

            'bank_account_iban' => [
                'type'              => 'string',
                'description'       => 'Number of the bank account of the Establishment.'
            ],

            'bank_account_bic' => [
                'type'              => 'string',
                'description'       => 'Identitifer of the Bank related to the bank account.'
            ],

            'analytic_section_id' => [
                'type'              => 'many2one',
                'foreign_object'    => \finance\accounting\AnalyticSection::getType(),
                'description'       => "Related analytic section, if any."
            ],

            'signature' => [
                'type'              => 'string',
                'usage'             => 'text/html',
                'description'       => 'Establishment signature to append to communications.',
                'multilang'         => true
            ]

        ];
    }

    public static function getConstraints() {
        return [
            'bank_account_iban' =>  [
                'invalid_account' => [
                    'message'       => 'Bank account must be a valid IBAN number.',
                    'function'      => function ($account, $values) {
                        return (bool) (preg_match('/^[A-Z]{2}[0-9]{2}(?:[0-9]{4}){3,4}(?!(?:[0-9]){3})(?:[0-9]{1,2})?$/', $account));
                    }
                ]
            ],
            'bank_account_bic' =>  [
                'invalid_code' => [
                    'message'       => 'Bank identifier must be a valid BIC code.',
                    'function'      => function ($identifier, $values) {
                        return (bool) (preg_match('/^[A-Z]{6,6}[A-Z2-9][A-NP-Z0-9]([A-Z0-9]{3,3}){0,1}$/', $identifier));
                    }
                ]
            ]
        ];
    }
}