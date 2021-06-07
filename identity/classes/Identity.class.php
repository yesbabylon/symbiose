<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace identity;
use equal\orm\Model;

/**
 * This class is meant to be used as an interface for other entities (organisation and partner).
 * An identity is either a legal or natural person (Legal persons are Organisations).
 * An organisation usually has several partners of various kind (contact, employee, provider, customer, ...).
 * Partners are Identity objects as well.
 * A Partner always relates to an organisation.
 * A user is always attached to a Partner.
 */
class Identity extends Model {

    public static function getName() {
        return "Identity";
    }

    public static function getDescription() {
        return "An Identity is either a legal or natural person: organisations are legal persons and users, contacts and employees are natural persons.";
    }

    public static function getColumns() {
        return [
            'name' => [
                'type'             => 'alias',
                'alias'            => 'display_name'
            ],

            'display_name' => [
                'type'              => 'computed',
                'function'          => 'identity\Identity::getDisplayName',
                'result_type'       => 'string',
                'store'             => true,
                'description'       => 'The display name of the identity.'
            ],

            'type' => [
                'type'              => 'string',
                'selection'         => [
                                        'I'  => 'individual (natural person)',
                                        'SE' => 'self-employed',
                                        'C'  => 'company',
                                        'NP' => 'non-profit',
                                        'PA' => 'public-administration'
                ],
                'description'       => 'Type of organisation.',
                'required'          => true
            ],

            /*
                Fields specific to organisations
            */
            'legal_name' => [
                'type'              => 'string',
                'description'       => 'Full name of the organisation (legal business name).',
                'visible'           => [ ['type', '<>', 'I'] ]
            ],
            'short_name' => [
                'type'          => 'string',
                'description'   => 'Usual name to be used as a memo for identifying the organisation type (i.e. an acronym or short name).',
                'visible'           => [ ['type', '<>', 'I'] ]
            ],
            'description' => [
                'type'              => 'string',
                'description'       => 'A short reminder to help user identify the organisation (e.g. "Human Resources Consultancy Firm").',
                'visible'           => [ ['type', '<>', 'I'] ]
            ],
            'has_VAT' => [  
                'type'              => 'string',
                'default'           => true,
                'description'       => 'Does the this organisation have a VAT number?',
                'visible'           => [ ['type', '<>', 'I'] ]
            ],
            'VAT_number' => [
                'type'              => 'string',
                'description'       => 'Value Added Tax identification number, if any.',
                'visible'           => [ ['has_VAT', '=', true], ['type', '<>', 'I'] ]
            ],
            'registration_number' => [
                'type'              => 'string',
                'description'       => 'Organisation registration number (company number).',
                'visible'           => [ ['type', '<>', 'I'] ]
            ],

            /*
                Fields specific to citizen: children organisations and parent company, if any
            */            
            'citizen_identification' => [
                'type'              => 'string',
                'description'       => 'Citizen registration number, if any.',
                'visible'           => [ ['type', '=', 'I'] ]
            ],            

            /*
                Relational fields specific to organisations: children organisations and parent company, if any
            */
            'children_id' => [
                'type'              => 'one2many',
                'foreign_object'    => 'identity\Identity',
                'foreign_field'     => 'parent_id',
                'domain'            => ['type', '<>', 'I'],                
                'description'       => 'Children organisations owned by the company, if any.',
                'visible'           => [ ['type', '<>', 'I'] ]
            ],
            'parent_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'identity\Identity',
                'domain'            => ['type', '<>', 'I'],
                'description'       => 'Parent company of which the organisation is a branch, if any.',
                'visible'           => [ ['type', '<>', 'I'] ]
            ],

            'employees_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'identity\Partner',
                'foreign_field'     => 'owner_identity_id',
                'domain'            => ['relationship', '=', 'employee'],
                'description'       => 'List of employees of the organisation, if any.' ,
                'visible'           => [ ['type', '<>', 'I'] ]
            ],
            'customers_ids' => [ 
                'type'              => 'one2many', 
                'foreign_object'    => 'identity\Partner',
                'foreign_field'     => 'owner_identity_id',
                'domain'            => ['relationship', '=', 'customer'],
                'description'       => 'List of customers of the organisation, if any.',
                'visible'           => [ ['type', '<>', 'I'] ]
            ],
            'providers_ids' => [
                'type'              => 'one2many', 
                'foreign_object'    => 'identity\Partner',
                'foreign_field'     => 'owner_identity_id',
                'domain'            => ['relationship', '=', 'provider'],
                'description'       => 'List of providers of the organisation, if any.',
                'visible'           => [ ['type', '<>', 'I'] ]
            ],

            // Any Identity can have several contacts
            'contacts_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'identity\Partner',
                'foreign_field'     => 'owner_identity_id',                
                'domain'            => ['relationship', '=', 'contact'],
                'description'       => 'List of contacts related to the organisation (not necessarily employees), if any.' 
            ],


            /*
                Description of the Identity address.
                For organisations this is the official (legal) address (typically headquarters, but not necessarily)
            */
            'address_street' => [
                'type'              => 'string',
                'description'       => 'Street and number.'
            ],
            'address_dispatch' => [
                'type'              => 'string',
                'description'       => 'Optional info for mail dispatch (appartment, box, floor, ...).'
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
                'description'       => 'Country.' 
            ],

            /*
                Additional official contact details.
                For individuals these are personnal contact details, whereas for companies these are official (registered) details.
            */
            'email' => [
                'type'              => 'string',
                'usage'             => 'email',
                'description'       => "Contact email address."
            ],
            'phone' => [
                'type'              => 'string',
                'usage'             => 'phone',
                'description'       => "Contact phone number."
            ],
            // Companies can also have an official website.
            'website' => [
                'type'          => 'string',
                'usage'         => 'url',
                'description'   => 'Organisation main official website URL, if any.',
                'visible'           => [ ['type', '<>', 'I'] ]                
            ],  


            /*
                For organisations, there is a reference person: a person who is entitled to legally represent the organisation (typically the director, the manager, the CEO, ...).
                These contact details are commonly requested by service providers for validating the identity of an organisation.
            */
            'reference_partner_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'identity\Partner',
                'domain'            => ['relationship', '=', 'contact'],                
                'description'       => 'Contact (natural person) that can legally represent the organisation.' ,
                'visible'           => [ ['type', '<>', 'I'] ]
            ],


            /*
                Contact details.
                For individuals, these are the contact details of the person herself.
            */
            'firstname' => [
                'type'              => 'string',
                'description'       => "Full name of the contact (must be a person, not a role).",
                'visible'           => [ ['type', '=', 'I'] ]
            ],
            'lastname' => [
                'type'              => 'string',
                'description'       => 'Reference contact surname.',
                'visible'           => [ ['type', '=', 'I'] ]                
            ],
            'gender' => [
                'type'              => 'string',
                'selection'         => ['M' => 'Male', 'F' => 'Female', 'X' => 'Non-binary'],
                'description'       => 'Reference contact gender.',
                'visible'           => [ ['type', '=', 'I'] ]                
            ],
            'title' => [
                'type'              => 'string',
                'selection'         => ['Dr' => 'Doctor', 'Ms' => 'Miss', 'Mrs' => 'Misses', 'Mr' => 'Mister', 'Pr' => 'Professor'],
                'description'       => 'Reference contact title.',
                'visible'           => [ ['type', '=', 'I'] ]                
            ],
            'date_of_birth' => [
                'type'              => 'date',
                'description'       => 'Date of birth.',
                'visible'           => [ ['type', '=', 'I'] ]                
            ],
            'lang' => [
                'type'              => 'string',
                'usage'             => 'language/iso-639:2',
                'description'       => 'Prefered spoken language.'
            ],



        ];
    }


    /**
     * For organisations the display name is the legal name
     * For individuals, the display name is the concatenation of first and last names
     */
    public static function getDisplayName($om, $oids, $lang) {
        $result = [];
        $res = $om->read(__CLASS__, $oids, ['type', 'firstname', 'lastname', 'legal_name', 'short_name']);
        foreach($res as $oid => $odata) {
            $display_name = self::_computeDisplayName($odata, $lang);
            $result[$oid] = $display_name;
        }
        return $result;              
    }


    public static function _computeDisplayName($fields, $lang) {
        $parts = [];
        if( isset($fields['type'])  ) {
            if( $fields['type'] == 'I'  ) {
                if( isset($fields['firstname']) && strlen($fields['firstname']) ) {
                    $parts[] = $fields['firstname'];
                }
                if( isset($fields['lastname']) && strlen($fields['lastname'])) {
                    $parts[] = $fields['lastname'];
                }
            }
            else {
                if( isset($fields['short_name']) && strlen($fields['short_name'])) {
                    $parts[] = $fields['short_name'];
                }
                else if( isset($fields['legal_name']) && strlen($fields['legal_name'])) {
                    $parts[] = $fields['legal_name'];
                }
            }

        }
        return implode(' ', $parts);
    }
    

    public static function getConstraints() {
        return [
            'legal_name' =>  [
                'missing' => [
                    'message'       => 'legal name is mandatory for organisations.',
                    'function'      => function ($legal_name, $values) {
                        $res = false;
                        if( strlen($legal_name) > 0 ) {
                            $res = true;
                        }
                        else {
                            if( isset($values['type']) && $values['type'] == 'I' ) {
                                $res = true;
                            }
                        }
                        return $res;
                    }
                ],
                'too_short' => [
                    'message'       => 'Firstname must be 2 chars long at minimum.',
                    'function'      => function ($firstname, $values) {
                        return (bool) (strlen($firstname) >= 2);
                    }
                ]
            ],
            'firstname' =>  [
                'missing' => [
                    'message'       => 'Firstname is mandatory for individuals.',
                    'function'      => function ($firstname, $values) {
                        $res = false;
                        if( strlen($firstname) > 0 ) {
                            $res = true;
                        }
                        else {
                            if( isset($values['type']) && $values['type'] != 'I' ) {
                                $res = true;
                            }
                        }
                        return $res;
                    }
                ],
                'too_short' => [
                    'message'       => 'Firstname must be 2 chars long at minimum.',
                    'function'      => function ($firstname, $values) {
                        return (bool) (strlen($firstname) >= 2);
                    }
                ],
                'invalid_chars' => [
                    'message'       => 'Firstname must contain only naming glyphs.',
                    'function'      => function ($firstname, $values) {
                        return (bool) (preg_match('/^[\w\'\-,.][^0-9_!¡?÷?¿\/\\+=@#$%ˆ&*(){}|~<>;:[\]]{1,}$/u', $firstname));                        
                    }
                ]
            ]

        ];
    }    
}