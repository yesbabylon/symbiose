<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace identity;

use equal\orm\Model;

/**
 * This class is meant to be used as an interface for other entities (organization and partner).
 */
abstract class IdentityAbstract extends Model {

    public static function getColumns() {
        return [

            'organization_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'identity\Organization',
                'description'       => 'The organization the identity refers to.'
            ],

            'name' => [
                'type'              => 'string',
                'description'       => 'The display name of the identity.',
                'help'              => "The display name is a computed field that returns a concatenated string containing either the firstname+lastname, or the legal name of the Identity, based on the kind of Identity.\n
                    For instance, 'name', for a company with \"My Company\" as legal name will return \"My Company\". \n
                    Whereas, for an individual having \"John\" as firstname and \"Smith\" as lastname, it will return \"John Smith\"."
            ],

            'hash_sha256' => [
                'type'              => 'string',
                'usage'             => 'text/plain:64',
                'description'       => 'SHA256 hash of the identity.',
                'help'              => 'Generated SHA256 hash is based on unique identity attributes (registration_number and citizen_identification).
                    This hash is also used to identify (external) identities when citizen_identification cannot be stored for data privacy compliancy reasons.',
                'readonly'          => true
            ],

            'identity_slug' => [
                'type'              => 'string',
                'description'       => 'Slug for helping identifying duplicates.',
                'help'              => '{type-legal_name-zip-country}'
            ],

            'slug_hash' => [
                'type'              => 'string',
                'usage'             => 'text/plain:32',
                'description'       => 'Slug for helping identifying duplicates.'
            ],

            'type_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'identity\IdentityType',
                // default is 'IN' individual
                'default'           => 1,
                'dependents'        => ['type', 'name', 'identity_slug', 'slug_hash'],
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
                'description'       => 'A short reminder to help user identify the targeted person and its specifics.'
            ],

            'bank_account_iban' => [
                'type'              => 'string',
                'usage'             => 'uri/urn.iban',
                'description'       => "Number of the bank account of the Identity, if any.",
                'visible'           => [ ['has_parent', '=', false] ]
            ],

            'bank_account_bic' => [
                'type'              => 'string',
                'description'       => "Identifier of the Bank related to the Identity's bank account, when set.",
                'visible'           => [ ['has_parent', '=', false] ]
            ],

            'bank_country' => [
                'type'              => 'string',
                'usage'             => 'country/iso-3166:2',
                'description'       => 'The country where the organization holds the bank account, specified using the ISO 3166-2 code.',
                'readonly'          => true
            ],

            'bank_name' => [
                'type'              => 'string',
                'description'       => 'The name of the bank where the organization holds its account.'
            ],

            /*
                Fields specific to organizations
            */
            'legal_name' => [
                'type'              => 'string',
                'description'       => 'Full name of the Identity.',
                'visible'           => [ ['type', '<>', 'IN'] ],
                'dependents'        => ['name']
            ],

            'short_name' => [
                'type'              => 'string',
                'description'       => 'Usual name to be used as a memo for identifying the organization (acronym or short name).',
                'visible'           => [ ['type', '<>', 'IN'] ],
                'dependents'        => ['name']
            ],

            'has_vat' => [
                'type'              => 'boolean',
                'description'       => 'Does the organization have a VAT number?',
                'visible'           => [ ['type', '<>', 'IN'], ['has_parent', '=', false] ],
                'default'           => false
            ],

            'vat_number' => [
                'type'              => 'string',
                'description'       => 'Value Added Tax identification number, if any.',
                'visible'           => [ ['has_vat', '=', true], ['type', '<>', 'IN'], ['has_parent', '=', false] ]
            ],

            'registration_number' => [
                'type'              => 'string',
                'description'       => 'Organization registration number (company number).',
                'visible'           => [ ['type', '<>', 'IN'] ]
            ],

            /*
                Fields specific to citizen: children organizations and parent company, if any
            */
            'citizen_identification' => [
                'type'              => 'string',
                'description'       => 'Citizen registration number, if any.',
                'visible'           => [ ['type', '=', 'IN'] ]
            ],

            'nationality' => [
                'type'              => 'string',
                'usage'             => 'country/iso-3166:2',
                'description'       => 'The country the person is citizen of.',
                'default'           => 'BE'
            ],

            /*
                Relational fields specific to organizations: children organizations and parent company, if any
            */
            'children_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'identity\Identity',
                'foreign_field'     => 'parent_id',
                'domain'            => [ ['id', '<>', 'object.id'], ['type', '<>', 'IN'] ],
                'description'       => 'Children departments of the organization, if any.',
                'visible'           => [ ['type', '<>', 'IN'] ]
            ],

            'has_parent' => [
                'type'              => 'boolean',
                'description'       => 'Does the identity have a parent organization?',
                'visible'           => [ ['type', '<>', 'IN'] ],
                'default'           => false
            ],

            'parent_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'identity\Identity',
                'domain'            => [ ['id', '<>', 'object.id'], ['type', '<>', 'IN'] ],
                'description'       => 'Parent company of which the organization is a branch (department), if any.',
                'visible'           => [ ['has_parent', '=', true] ]
            ],

            'contacts_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'identity\Contact',
                'foreign_field'     => 'owner_identity_id',
                'domain'            => ['partner_identity_id', '<>', 'object.id'],
                'description'       => 'List of contacts related to the organization, if any.',
                'help'              => 'A contact is an arbitrary relation between two identities. Any Identity can have several contacts.'
            ],

            'bank_accounts_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'finance\bank\BankAccount',
                'foreign_field'     => 'owner_identity_id',
                'description'       => 'List of the bank account of the organisation'
            ],


            /*
                Contact details.
                For individuals, these are the contact details of the person herself.
            */
            'firstname' => [
                'type'              => 'string',
                'description'       => "Full name of the contact (must be a person, not a role).",
                'visible'           => ['type', '=', 'IN'],
                'dependents'        => ['name']
            ],

            'lastname' => [
                'type'              => 'string',
                'description'       => 'Reference contact surname.',
                'visible'           => ['type', '=', 'IN'],
                'dependents'        => ['name']
            ],

            'gender' => [
                'type'              => 'string',
                'selection'         => ['M' => 'Male', 'F' => 'Female', 'X' => 'Non-binary'],
                'description'       => 'Reference contact gender.',
                'visible'           => ['type', '=', 'IN']
            ],

            'title' => [
                'type'              => 'string',
                'selection'         => ['Dr' => 'Doctor', 'Ms' => 'Miss', 'Mrs' => 'Misses', 'Mr' => 'Mister', 'Pr' => 'Professor'],
                'description'       => 'Reference contact title.',
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
                'description'       => "Preferred language of the identity.",
                'default'           => 1
            ],

            /*
                Description of the Identity address.
                For organizations this is the official (legal) address (typically headquarters, but not necessarily)
            */
            'address_street' => [
                'type'              => 'string',
                'description'       => 'Street and number.'
            ],

            'address_dispatch' => [
                'type'              => 'string',
                'description'       => 'Optional info for mail dispatch (apartment, box, floor, ...).'
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
                'description'       => 'Main address from related Identity.'
            ],

            'address_hash' => [
                'type'              => 'string',
                'usage'             => 'text/plain:32',
                'description'       => 'Hash of the normalized version of the address.',
                'help'              => 'This field is used to attempt retrieving matches for a given address.'
            ],

            /*
                Additional official contact details.
                For individuals these are personal contact details, whereas for companies these are official (registered) details.
            */
            'email' => [
                'type'              => 'string',
                'usage'             => 'email',
                'description'       => "Identity main email address."
            ],

            'email_alt' => [
                'type'              => 'string',
                'usage'             => 'email',
                'description'       => "Identity secondary email address."
            ],

            'phone' => [
                'type'              => 'string',
                'usage'             => 'phone',
                'description'       => "Identity secondary phone number (mobile or landline)."
            ],

            'phone_alt' => [
                'type'              => 'string',
                'usage'             => 'phone',
                'description'       => "Identity main phone number (mobile or landline)."
            ],

            'mobile' => [
                'type'              => 'string',
                'usage'             => 'phone',
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
                'usage'             => 'uri/url',
                'description'       => 'Organization main official website URL, if any.',
                'visible'           => ['type', '<>', 'IN']
            ],

            'profile_image_document_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'documents\Document',
                'description'       => 'Logo or picture of the identity.',
                'help'              => 'Company logo for organizations or profile image for natural person.',
                'domain'            => ['extension', 'in', ['avif', 'jpg', 'png', 'svg', 'webp']]
            ],

            // an identity can have additional addresses
            'addresses_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'identity\Address',
                'foreign_field'     => 'identity_id',
                'description'       => 'List of addresses related to the identity.',
            ],

            /*
                For organizations, there might be a reference person: a person who is entitled to legally represent the organization (typically the director, the manager, the CEO, ...).
                These contact details are commonly requested by service providers for validating the identity of an organization.
            */

            'reference_identity_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'identity\Identity',
                'description'       => 'Contact (natural person) that can legally represent the identity.',
                'help'              => 'This field can be the symmetrical value of owner_identity_id.',
                'visible'           => [ ['type', '<>', 'IN'], ['type', '<>', 'SE'] ]
            ],

            'broadcasts_ids' => [
                'type'              => 'many2many',
                'foreign_object'    => 'communication\broadcast\BroadcastMessage',
                'foreign_field'     => 'identities_ids',
                'rel_table'         => 'identity_identity_rel_broadcast',
                'rel_foreign_key'   => 'broadcast_id',
                'rel_local_key'     => 'identity_id',
                'description'       => 'Broadcasts to send to the identity email address.'
            ],

            'is_active' => [
                'type'              => 'boolean',
                'description'       => "Is the identity active?",
                'help'              => "When an identity is not marked as active, it is no longer displayed amongst the selection choices. However, it is still visible in the list of identities, and its related informations and documents remain available.",
                'default'           => true
            ]

        ];
    }
}
