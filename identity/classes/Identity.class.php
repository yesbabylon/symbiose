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
                'function'          => 'calcDisplayName',
                'result_type'       => 'string',
                'store'             => true,
                'description'       => 'The display name of the identity.',
                'help'              => "
                    The display name is a computed field that returns a concatenated string containing either the firstname+lastname, or the legal name of the Identity, based on the kind of Identity.\n
                    For instance, 'display_name', for a company with \"My Company\" as legal name will return \"My Company\". \n
                    Whereas, for an individual having \"John\" as firstname and \"Smith\" as lastname, it returns \"John Smith\".
                "
            ],

            'type_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'identity\IdentityType',
                'onupdate'          => 'onupdateTypeId',
                'default'           => 1,                                    // default is 'I' individual
                'description'       => 'Type of identity.'
            ],

            'type' => [
                'type'              => 'string',
                'selection'         => [
                    'I'  => 'Individual (natural person)',
                    'SE' => 'Self-employed',
                    'C'  => 'Company',
                    'NP' => 'Non-profit organisation',
                    'PA' => 'Public administration'
                ],
                'default'           => 'I',
                'readonly'          => true,                                // has to be changed through type_id
                'description'       => 'Code of the type of identity.'
            ],

            'description' => [
                'type'              => 'string',
                'usage'             => 'text/plain',
                'description'       => 'A short reminder to help user identify the targeted person and its specifics.'
            ],

            'bank_account_iban' => [
                'type'              => 'string',
                'usage'             => 'uri/urn:iban',
                'description'       => "Number of the bank account of the Identity, if any.",
                'visible'           => [ ['has_parent', '=', false] ]
            ],

            'bank_account_bic' => [
                'type'              => 'string',
                'description'       => "Identitifer of the Bank related to the Identity's bank account, when set.",
                'visible'           => [ ['has_parent', '=', false] ]
            ],

            'signature' => [
                'type'              => 'string',
                'usage'             => 'text/html',
                'description'       => 'Identity signature to append to communications.',
                'multilang'         => true
            ],

            /*
                Fields specific to organisations
            */
            'legal_name' => [
                'type'              => 'string',
                'description'       => 'Full name of the Identity.',
                'visible'           => [ ['type', '<>', 'I'] ],
                'onupdate'          => 'onupdateName'
            ],
            'short_name' => [
                'type'          => 'string',
                'description'   => 'Usual name to be used as a memo for identifying the organisation (acronym or short name).',
                'visible'           => [ ['type', '<>', 'I'] ],
                'onupdate'          => 'onupdateName'
            ],
            'has_vat' => [
                'type'              => 'boolean',
                'description'       => 'Does the organisation have a VAT number?',
                'visible'           => [ ['type', '<>', 'I'], ['has_parent', '=', false] ],
                'default'           => false
            ],
            'vat_number' => [
                'type'              => 'string',
                'description'       => 'Value Added Tax identification number, if any.',
                'visible'           => [ ['has_vat', '=', true], ['type', '<>', 'I'], ['has_parent', '=', false] ]
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

            'nationality' => [
                'type'              => 'string',
                'usage'             => 'country/iso-3166:2',
                'description'       => 'The country the person is citizen of.',
                'default'           => 'BE'
            ],

            /*
                Relational fields specific to organisations: children organisations and parent company, if any
            */
            'children_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'identity\Identity',
                'foreign_field'     => 'parent_id',
                'domain'            => ['type', '<>', 'I'],
                'description'       => 'Children departments of the organisation, if any.',
                'visible'           => [ ['type', '<>', 'I'] ]
            ],

            'has_parent' => [
                'type'              => 'boolean',
                'description'       => 'Does the identity have a parent organisation?',
                'visible'           => [ ['type', '<>', 'I'] ],
                'default'           => false
            ],

            'parent_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'identity\Identity',
                'domain'            => ['type', '<>', 'I'],
                'description'       => 'Parent company of which the organisation is a branch (department), if any.',
                'visible'           => [ ['has_parent', '=', true] ]
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
                'domain'            => [ ['partner_identity_id', '<>', 'object.id'], ['relationship', '=', 'contact'] ],
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
                'description'       => 'Country.',
                'default'           => 'BE'
            ],

            /*
                Additional official contact details.
                For individuals these are personnal contact details, whereas for companies these are official (registered) details.
            */
            'email' => [
                'type'              => 'string',
                'usage'             => 'email',
                'onupdate'          => 'onupdateEmail',
                'description'       => "Identity main email address."
            ],

            'phone' => [
                'type'              => 'string',
                'usage'             => 'phone',
                'onupdate'          => 'onupdatePhone',
                'description'       => "Identity main phone number (mobile or landline)."
            ],

            'mobile' => [
                'type'              => 'string',
                'usage'             => 'phone',
                'onupdate'          => 'onupdateMobile',
                'description'       => "Identity mobile phone number."
            ],

            'fax' => [
                'type'              => 'string',
                'usage'             => 'phone',
                'description'       => "Identity main fax number."
            ],

            // Companies can also have an official website.
            'website' => [
                'type'              => 'string',
                'usage'             => 'url',
                'description'       => 'Organisation main official website URL, if any.',
                'visible'           => ['type', '<>', 'I']
            ],

            // an identity can have several addresses
            'addresses_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'identity\Address',
                'foreign_field'     => 'identity_id',
                'description'       => 'List of addresses related to the identity.',
            ],

            /*
                For organisations, there might be a reference person: a person who is entitled to legally represent the organisation (typically the director, the manager, the CEO, ...).
                These contact details are commonly requested by service providers for validating the identity of an organisation.
            */
            'reference_partner_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'identity\Partner',
                'domain'            => ['relationship', '=', 'contact'],
                'description'       => 'Contact (natural person) that can legally represent the organisation.',
                'onupdate'          => 'onupdateReferencePartnerId',
                'visible'           => [ ['type', '<>', 'I'], ['type', '<>', 'SE'] ]
            ],

            /*
                For individuals, the identity might be related to a user.
            */
            'user_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'identity\User',
                'description'       => 'User associated to this identity.',
                'visible'           => ['type', '=', 'I']
            ],

            /*
                Contact details.
                For individuals, these are the contact details of the person herself.
            */
            'firstname' => [
                'type'              => 'string',
                'description'       => "Full name of the contact (must be a person, not a role).",
                'visible'           => ['type', '=', 'I'],
                'onupdate'          => 'onupdateName'
            ],

            'lastname' => [
                'type'              => 'string',
                'description'       => 'Reference contact surname.',
                'visible'           => ['type', '=', 'I'],
                'onupdate'          => 'onupdateName'
            ],

            'gender' => [
                'type'              => 'string',
                'selection'         => ['M' => 'Male', 'F' => 'Female', 'X' => 'Non-binary'],
                'description'       => 'Reference contact gender.',
                'visible'           => ['type', '=', 'I']
            ],

            'title' => [
                'type'              => 'string',
                'selection'         => ['Dr' => 'Doctor', 'Ms' => 'Miss', 'Mrs' => 'Misses', 'Mr' => 'Mister', 'Pr' => 'Professor'],
                'description'       => 'Reference contact title.',
                'visible'           => ['type', '=', 'I']
            ],

            'date_of_birth' => [
                'type'              => 'date',
                'description'       => 'Date of birth.',
                'visible'           => ['type', '=', 'I']
            ],

            'lang_id' => [
                'type'              => 'many2one',
                'foreign_object'    => \core\Lang::getType(),
                'description'       => "Preferred language of the identity.",
                'default'           => 2,
                'onupdate'          => 'onupdateLangId'
            ],

            // field for retrieving all partners related to the identity
            'partners_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'identity\Partner',
                'foreign_field'     => 'partner_identity_id',
                'description'       => 'Partnerships that relate to the identity.',
                'domain'            => ['owner_identity_id', '<>', 'object.id']
            ],

            'flag_latepayer' => [
                'type'              => 'boolean',
                'default'           => false,
                'description'       => 'Mark a customer as bad payer.'
            ],

            'flag_damage' => [
                'type'              => 'boolean',
                'default'           => false,
                'description'       => 'Mark a customer with a damage history.'
            ],

            'flag_nuisance' => [
                'type'              => 'boolean',
                'default'           => false,
                'description'       => 'Mark a customer with a disturbances history.'
            ]

        ];
    }

    /**
     * For organisations the display name is the legal name
     * For individuals, the display name is the concatenation of first and last names
     */
    public static function calcDisplayName($om, $oids, $lang) {
        $result = [];
        $res = $om->read(self::getType(), $oids, ['type_id', 'firstname', 'lastname', 'legal_name', 'short_name']);
        foreach($res as $oid => $odata) {
            $parts = [];
            if( isset($odata['type_id'])  ) {
                if( $odata['type_id'] == 1  ) {
                    if( isset($odata['firstname']) && strlen($odata['firstname']) ) {
                        $parts[] = ucfirst($odata['firstname']);
                    }
                    if( isset($odata['lastname']) && strlen($odata['lastname'])) {
                        $parts[] = mb_strtoupper($odata['lastname']);
                    }
                }
                if( $odata['type_id'] != 1 || empty($parts) ) {
                    if( isset($odata['short_name']) && strlen($odata['short_name'])) {
                        $parts[] = $odata['short_name'];
                    }
                    else if( isset($odata['legal_name']) && strlen($odata['legal_name'])) {
                        $parts[] = $odata['legal_name'];
                    }
                }
            }
            $result[$oid] = implode(' ', $parts);
        }
        return $result;
    }

    public static function onupdatePhone($om, $oids, $values, $lang) {
        $identities = $om->read(self::getType(), $oids, ['partners_ids']);
        foreach($identities as $oid => $odata) {
            $om->update('identity\Partner', $odata['partners_ids'], [ 'phone' => null ], $lang);
        }
    }

    public static function onupdateMobile($om, $oids, $values, $lang) {
        $identities = $om->read(self::getType(), $oids, ['partners_ids']);
        foreach($identities as $oid => $odata) {
            $om->update('identity\Partner', $odata['partners_ids'], [ 'mobile' => null ], $lang);
        }
    }

    public static function onupdateEmail($om, $oids, $values, $lang) {
        $identities = $om->read(self::getType(), $oids, ['partners_ids']);
        foreach($identities as $oid => $odata) {
            $om->update('identity\Partner', $odata['partners_ids'], [ 'email' => null ], $lang);
        }
    }

    public static function onupdateName($om, $oids, $values, $lang) {
        $om->update(self::getType(), $oids, [ 'display_name' => null ], $lang);
        $res = $om->read(self::getType(), $oids, ['partners_ids']);
        $partners_ids = [];
        foreach($res as $oid => $odata) {
            $partners_ids = array_merge($partners_ids, $odata['partners_ids']);
        }
        // force re-computing of related partners names
        $om->update('identity\Partner', $partners_ids, [ 'name' => null ], $lang);
        $om->read('identity\Partner', $partners_ids, ['name'], $lang);
    }


    public static function onupdateTypeId($om, $oids, $values, $lang) {
        $res = $om->read(self::getType(), $oids, ['type_id', 'type_id.code', 'partners_ids']);
        if($res > 0) {
            $partners_ids = [];
            foreach($res as $oid => $odata) {
                $values = [ 'type' => $odata['type_id.code'], 'display_name' => null];
                if($odata['type_id'] == 1 ) {
                    $values['legal_name'] = '';
                }
                else {
                    $values['firstname'] = '';
                    $values['lastname'] = '';
                }
                $partners_ids = array_merge($partners_ids, $odata['partners_ids']);
                $om->update(self::getType(), $oid, $values, $lang);
            }
            $om->read(self::getType(), $oids, ['display_name'], $lang);
            // force re-computing of related partners names
            $om->update('identity\Partner', $partners_ids, [ 'name' => null ], $lang);
            $om->read('identity\Partner', $partners_ids, ['name'], $lang);
        }
    }

    /**
     * When lang_id is updated, perform cascading trought the partners to update related lang_id
     */
    public static function onupdateLangId($om, $oids, $values, $lang) {
        $res = $om->read(self::getType(), $oids, ['partners_ids', 'lang_id']);

        if($res > 0 && count($res)) {
            foreach($res as $oid => $odata) {
                $om->update('identity\Partner', $odata['partners_ids'], ['lang_id' => $odata['lang_id']]);
            }
        }
    }

    /**
     * When a reference partner is given, add it to the identity's contacts list.
     */
    public static function onupdateReferencePartnerId($om, $oids, $values, $lang) {
        $identities = $om->read(self::getType(), $oids, ['reference_partner_id', 'reference_partner_id.partner_identity_id', 'contacts_ids.partner_identity_id'], $lang);

        if($identities > 0) {
            foreach($identities as $oid => $identity) {
                if(!in_array($identity['reference_partner_id.partner_identity_id'], array_map( function($a) { return $a['partner_identity_id']; }, $identity['contacts_ids.partner_identity_id']))) {
                    // create a contact with the customer as 'booking' contact
                    $om->create('identity\Partner', [
                        'owner_identity_id'     => $oid,
                        'partner_identity_id'   => $identity['reference_partner_id.partner_identity_id'],
                        'relationship'          => 'contact'
                    ]);
                }
            }
        }
    }


    /**
     * Signature for single object change from views.
     *
     * @param  Object   $om        Object Manager instance.
     * @param  Array    $event     Associative array holding changed fields as keys, and their related new values.
     * @param  Array    $values    Copy of the current (partial) state of the object (fields depend on the view).
     * @param  String   $lang      Language (char 2) in which multilang field are to be processed.
     * @return Array    Associative array mapping fields with their resulting values.
     */
    public static function onchange($om, $event, $values, $lang='en') {
        $result = [];

        if(isset($event['type_id'])) {
            $types = $om->read('identity\IdentityType', $event['type_id'], ['code']);
            if($types > 0) {
                $type = reset($types);
                $result['type'] = $type['code'];
            }
        }

        return $result;
    }

    /**
     * Check wether an object can be updated, and perform some additional operations if necessary.
     * This method can be overriden to define a more precise set of tests.
     *
     * @param  object   $om         ObjectManager instance.
     * @param  array    $oids       List of objects identifiers.
     * @param  array    $values     Associative array holding the new values to be assigned.
     * @param  string   $lang       Language in which multilang fields are being updated.
     * @return array    Returns an associative array mapping fields with their error messages. En empty array means that object has been successfully processed and can be updated.
     */
    public static function canupdate($om, $oids, $values, $lang='en') {
        if(isset($values['type_id'])) {
            $identities = $om->read(get_called_class(), $oids, [ 'firstname', 'lastname', 'legal_name' ], $lang);
            foreach($identities as $oid => $identity) {
                if($values['type_id'] == 1) {
                    $firstname = '';
                    $lastname = '';
                    if(isset($values['firstname'])) {
                        $firstname = $values['firstname'];
                    }
                    else {
                        $firstname = $identity['firstname'];
                    }
                    if(isset($values['lastname'])) {
                        $lastname = $values['lastname'];
                    }
                    else {
                        $lastname = $identity['lastname'];
                    }

                    if(!strlen($firstname) ) {
                        return ['firstname' => ['missing' => 'Firstname cannot be empty for natural person.']];
                    }
                    if(!strlen($lastname) ) {
                        return ['lastname' => ['missing' => 'Lastname cannot be empty for natural person.']];
                    }
                }
                else {
                    $legal_name = '';
                    if(isset($values['legal_name'])) {
                        $legal_name = $values['legal_name'];
                    }
                    else {
                        $legal_name = $identity['legal_name'];
                    }
                    if(!strlen($legal_name)) {
                        return ['legal_name' => ['missing' => 'Legal name cannot be empty for legal person.']];
                    }
                }
            }
        }
        return parent::canupdate($om, $oids, $values, $lang);
    }

    public static function getConstraints() {
        return [
            'legal_name' =>  [
                'too_short' => [
                    'message'       => 'Legal name must be minimum 2 chars long.',
                    'function'      => function ($legal_name, $values) {
                        return !( strlen($legal_name) < 2 && isset($values['type_id']) && $values['type_id'] != 1 );
                    }
                ],
                'too_long' => [
                    'message'       => 'Legal name must be maximum 70 chars long.',
                    'function'      => function ($legal_name, $values) {
                        return !( strlen($legal_name) > 70 && isset($values['type_id']) && $values['type_id'] != 1 );
                    }
                ],
                'invalid_chars' => [
                    'message'       => 'Legal name must contain only naming glyphs.',
                    'function'      => function ($legal_name, $values) {
                        if( isset($values['type_id']) && $values['type_id'] == 1 ) {
                            return true;
                        }
                        // authorized : a-z, 0-9, '/', '-', ',', '.', ''', '&'
                        return (bool) (preg_match('/^[\w\'\-,.&][^_!¡?÷?¿\\+=@#$%ˆ*{}|~<>;:[\]]{1,}$/u', $legal_name));
                    }
                ]
            ],
            'firstname' =>  [
                'too_short' => [
                    'message'       => 'Firstname must be 2 chars long at minimum.',
                    'function'      => function ($firstname, $values) {
                        return !( strlen($firstname) < 2 && isset($values['type_id']) && $values['type_id'] == 1 );
                    }
                ],
                'invalid_chars' => [
                    'message'       => 'Firstname must contain only naming glyphs.',
                    'function'      => function ($firstname, $values) {
                        if( isset($values['type_id']) && $values['type_id'] != 1 ) {
                            return true;
                        }
                        return (bool) (preg_match('/^[\w\'\-,.][^0-9_!¡?÷?¿\/\\+=@#$%ˆ&*(){}|~<>;:[\]]{1,}$/u', $firstname));
                    }
                ]
            ],
            'lastname' =>  [
                'too_short' => [
                    'message'       => 'Lastname must be 2 chars long at minimum.',
                    'function'      => function ($lastname, $values) {
                        return !( strlen($lastname) < 2 && isset($values['type_id']) && $values['type_id'] == 1 );
                    }
                ],
                'invalid_chars' => [
                    'message'       => 'Lastname must contain only naming glyphs.',
                    'function'      => function ($lastname, $values) {
                        if( isset($values['type_id']) && $values['type_id'] != 1 ) {
                            return true;
                        }
                        return (bool) (preg_match('/^[\w\'\-,.][^0-9_!¡?÷?¿\/\\+=@#$%ˆ&*(){}|~<>;:[\]]{1,}$/u', $lastname));
                    }
                ]
            ]
        ];
    }
}