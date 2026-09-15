<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2026
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace identity;

use equal\orm\Model;

/**
 * Common schema for a canonical identity and its role projections.
 */
abstract class IdentityAbstract extends Model {

    /**
     * Explicit registry of supported backlinks.
     *
     * Legacy roles keep partner_identity_id until their own package migration.
     */
    protected const MAP_FIELDS_FACETS =  [
            'organization_id' => [
                'class'          => 'identity\Organization',
            ],
            'contact_id' => [
                'class'          => 'identity\Contact',
            ],
            'customer_contact_id' => [
                'class'          => 'sale\customer\Contact',
            ],
            'employee_id' => [
                'class'          => 'hr\employee\Employee',
            ],
            'customer_id' => [
                'class'          => 'sale\customer\Customer',
            ],
            'supplier_id' => [
                'class'          => 'purchase\supplier\Supplier',
            ]
        ];


    /**
     * Fields whose canonical value is held by Identity.
     */
    protected const COMMON_IDENTITY_FIELDS = [
        'type_id',
        'bank_account_iban', 'bank_account_bic', 'bank_country', 'bank_name',
        'legal_name', 'short_name', 'has_vat', 'vat_number', 'registration_number',
        'citizen_identification', 'nationality',
        'firstname', 'lastname', 'gender', 'title', 'date_of_birth', 'lang_id',
        'address_street', 'address_dispatch', 'address_city', 'address_zip',
        'address_state', 'address_country',
        'email', 'email_alt', 'phone', 'phone_alt', 'mobile', 'fax', 'website',
        'image_document_id', 'signature'
    ];

    public static function getColumns() {
        return [

            'name' => [
                'type'              => 'computed',
                'result_type'       => 'string',
                'function'          => 'calcName',
                'store'             => true,
                'instant'           => true,
                'description'       => 'The display name of the identity.',
                'help'              => "The display name is a computed field that returns a concatenated string containing either the firstname+lastname, or the legal name of the Identity, based on the kind of Identity.\n
                    For instance, 'name', for a company with \"My Company\" as legal_name will return \"My Company\". \n
                    Whereas, for an individual having \"John\" as firstname and \"Smith\" as lastname, it will return \"John Smith\"."
            ],

            'type_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'identity\IdentityType',
                'default'           => 1,
                'dependents'        => ['type', 'name'],
                'description'       => 'Type of identity.'
            ],

            'type' => [
                'type'              => 'computed',
                'result_type'       => 'string',
                'store'             => true,
                'instant'           => true,
                'readonly'          => true,
                'description'       => 'Code of the type of identity.',
                'relation'          => ['type_id' => 'code']
            ],

            'description' => [
                'type'              => 'string',
                'usage'             => 'text/plain',
                'description'       => 'A short reminder to help identify the person or organization.'
            ],

            'bank_account_iban' => [
                'type'              => 'string',
                'usage'             => 'uri/urn.iban',
                'description'       => 'Number of the main bank account of the identity, if any.'
            ],

            'bank_account_bic' => [
                'type'              => 'string',
                'description'       => 'Identifier of the bank related to the main bank account.'
            ],

            'bank_country' => [
                'type'              => 'string',
                'usage'             => 'country/iso-3166:2',
                'description'       => 'Country where the identity holds its bank account.',
                'readonly'          => true
            ],

            'bank_name' => [
                'type'              => 'string',
                'description'       => 'Name of the bank where the identity holds its bank account.'
            ],

            'legal_name' => [
                'type'              => 'string',
                'description'       => 'Full legal name of the identity.',
                'visible'           => [['type', '<>', 'IN']],
                'dependents'        => ['name']
            ],

            'short_name' => [
                'type'              => 'string',
                'description'       => 'Usual or abbreviated name of the organization.',
                'visible'           => [['type', '<>', 'IN']],
                'dependents'        => ['name']
            ],

            'has_vat' => [
                'type'              => 'boolean',
                'description'       => 'Does the organization have a VAT number?',
                'visible'           => [['type', '<>', 'IN']],
                'default'           => false
            ],

            'vat_number' => [
                'type'              => 'string',
                'description'       => 'Value Added Tax identification number, if any.',
                'visible'           => [['has_vat', '=', true], ['type', '<>', 'IN']]
            ],

            'registration_number' => [
                'type'              => 'string',
                'description'       => 'Organization registration number.',
                'visible'           => [['type', '<>', 'IN']]
            ],

            'citizen_identification' => [
                'type'              => 'string',
                'description'       => 'Citizen registration number, if any.',
                'visible'           => [['type', '=', 'IN']]
            ],

            'nationality' => [
                'type'              => 'string',
                'usage'             => 'country/iso-3166:2',
                'description'       => 'Country the person is a citizen of.',
                'default'           => 'BE'
            ],

            'firstname' => [
                'type'              => 'string',
                'description'       => 'Firstname of the natural person.',
                'visible'           => ['type', '=', 'IN'],
                'dependents'        => ['name']
            ],

            'lastname' => [
                'type'              => 'string',
                'description'       => 'Lastname of the natural person.',
                'visible'           => ['type', '=', 'IN'],
                'dependents'        => ['name']
            ],

            'gender' => [
                'type'              => 'string',
                'selection'         => ['M' => 'Male', 'F' => 'Female', 'X' => 'Non-binary'],
                'description'       => 'Gender of the natural person.',
                'visible'           => ['type', '=', 'IN']
            ],

            'title' => [
                'type'              => 'string',
                'selection'         => ['Dr' => 'Doctor', 'Ms' => 'Miss', 'Mrs' => 'Misses', 'Mr' => 'Mister', 'Pr' => 'Professor'],
                'description'       => 'Title of the natural person.',
                'visible'           => ['type', '=', 'IN']
            ],

            'date_of_birth' => [
                'type'              => 'date',
                'description'       => 'Date of birth.',
                'visible'           => ['type', '=', 'IN']
            ],

            'lang_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'core\Lang',
                'description'       => 'Preferred language of the identity.',
                'default'           => 1
            ],

            'address_street' => [
                'type'              => 'string',
                'description'       => 'Street and number.'
            ],

            'address_dispatch' => [
                'type'              => 'string',
                'description'       => 'Optional mail dispatch information.'
            ],

            'address_city' => [
                'type'              => 'string',
                'description'       => 'City.'
            ],

            'address_zip' => [
                'type'              => 'string',
                'description'       => 'Postal code.'
            ],

            'address_state' => [
                'type'              => 'string',
                'description'       => 'State or region.'
            ],

            'address_country' => [
                'type'              => 'string',
                'usage'             => 'country/iso-3166:2',
                'description'       => 'Country.',
                'default'           => 'BE'
            ],

            'address' => [
                'type'              => 'string',
                'description'       => 'Main address from the related identity.',
                'readonly'          => true
            ],

            'email' => [
                'type'              => 'string',
                'usage'             => 'email',
                'description'       => 'Main email address.'
            ],

            'email_alt' => [
                'type'              => 'string',
                'usage'             => 'email',
                'description'       => 'Secondary email address.'
            ],

            'phone' => [
                'type'              => 'string',
                'usage'             => 'phone',
                'description'       => 'Main phone number.'
            ],

            'phone_alt' => [
                'type'              => 'string',
                'usage'             => 'phone',
                'description'       => 'Secondary phone number.'
            ],

            'mobile' => [
                'type'              => 'string',
                'usage'             => 'phone',
                'description'       => 'Mobile phone number.'
            ],

            'fax' => [
                'type'              => 'string',
                'usage'             => 'phone',
                'description'       => 'Main fax number.'
            ],

            'website' => [
                'type'              => 'string',
                'usage'             => 'uri/url',
                'description'       => 'Official website URL, if any.',
                'visible'           => ['type', '<>', 'IN']
            ],

            'image_document_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'documents\Document',
                'description'       => 'Logo or picture of the identity.',
                'help'              => 'Company logo for organizations or profile image for natural persons.'
            ],

            'signature' => [
                'type'              => 'string',
                'usage'             => 'text/html',
                'description'       => 'Signature to append to communications.',
                'multilang'         => true
            ],

            'is_active' => [
                'type'              => 'boolean',
                'description'       => 'Is the identity or role active?',
                'default'           => true
            ]

        ];
    }



    /**
     * Stored projections that must be invalidated after a silent synchronization.
     */
    protected static function getIdentityProjectionFields() {
        return ['name', 'type', 'address'];
    }

    protected static function computeCommonIdentityValues($values) {
        $result = [];
        foreach(self::COMMON_IDENTITY_FIELDS as $field) {
            if(array_key_exists($field, $values)) {
                $result[$field] = $values[$field];
            }
        }
        return $result;
    }

    public static function getConstraints() {
        return [
            'legal_name' => [
                'too_short' => [
                    'message'  => 'Legal name must be minimum 2 chars long.',
                    'function' => function($legal_name, $values) {
                        return !(strlen($legal_name) < 2 && isset($values['type_id']) && $values['type_id'] != 1);
                    }
                ],
                'too_long' => [
                    'message'  => 'Legal name must be maximum 80 chars long.',
                    'function' => function($legal_name, $values) {
                        return !(strlen($legal_name) > 80 && isset($values['type_id']) && $values['type_id'] != 1);
                    }
                ],
                'invalid_chars' => [
                    'message'  => 'Legal name must contain only naming glyphs.',
                    'function' => function($legal_name, $values) {
                        if(isset($values['type_id']) && $values['type_id'] == 1) {
                            return true;
                        }
                        return (bool) preg_match('/^[\w\'\-,.&][^_!¡?÷?¿\\+=@#$%ˆ*{}|~<>;:[\]]{1,}$/u', $legal_name);
                    }
                ]
            ],
            'firstname' => [
                'too_short' => [
                    'message'  => 'Firstname must be 2 chars long at minimum.',
                    'function' => function($firstname, $values) {
                        return !(strlen($firstname) < 2 && isset($values['type_id']) && $values['type_id'] == 1);
                    }
                ],
                'invalid_chars' => [
                    'message'  => 'Firstname must contain only naming glyphs.',
                    'function' => function($firstname, $values) {
                        if(isset($values['type_id']) && $values['type_id'] != 1) {
                            return true;
                        }
                        return (bool) preg_match('/^[\w\'\-,.][^0-9_!¡?÷?¿\/\\+=@#$%ˆ&*(){}|~<>;:[\]]{1,}$/u', $firstname);
                    }
                ]
            ],
            'lastname' => [
                'too_short' => [
                    'message'  => 'Lastname must be 2 chars long at minimum.',
                    'function' => function($lastname, $values) {
                        return !(strlen($lastname) < 2 && isset($values['type_id']) && $values['type_id'] == 1);
                    }
                ],
                'invalid_chars' => [
                    'message'  => 'Lastname must contain only naming glyphs.',
                    'function' => function($lastname, $values) {
                        if(isset($values['type_id']) && $values['type_id'] != 1) {
                            return true;
                        }
                        return (bool) preg_match('/^[\w\'\-,.][^0-9_!¡?÷?¿\/\\+=@#$%ˆ&*(){}|~<>;:[\]]{1,}$/u', $lastname);
                    }
                ]
            ]
        ];
    }

    /**
     * For organizations the name is the legal name.
     * For individuals, the name is the concatenation of first and last names.
     */
    protected static function calcName($self) {
        $result = [];
        $self->read(['state', 'type', 'firstname', 'lastname', 'legal_name', 'short_name']);
        foreach($self as $id => $identity) {
            if($identity['state'] == 'draft') {
                continue;
            }
            $parts = [];
            if($identity['type'] == 'IN') {
                if(isset($identity['firstname']) && strlen($identity['firstname']) > 0) {
                    $parts[] = ucfirst($identity['firstname']);
                }
                if(isset($identity['lastname']) && strlen($identity['lastname']) > 0) {
                    $parts[] = mb_strtoupper($identity['lastname']);
                }
            }
            if(empty($parts) ) {
                if(isset($identity['legal_name']) && strlen($identity['legal_name']) > 0) {
                    $parts[] = $identity['legal_name'];
                }
                elseif(isset($identity['short_name']) && strlen($identity['short_name']) > 0) {
                    $parts[] = $identity['short_name'];
                }
            }
            if(empty($parts)) {
                continue;
            }
            $result[$id] = implode(' ', $parts);
        }
        return $result;
    }
}
