<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace identity;
use equal\orm\Model;

class Organisation extends Model {

    public static function getName() {
        return "Organisation";
    }

    public static function getDescription() {
        return "Holds the core information describing the organisation, either natural or legal person.";
    }

    public static function getColumns() {
        return [
            'name' => [
                'type'          => 'string',
                'description'   => 'Display name. A short name to be used as a memo for identifying the organisation type (e.g. an acronym).',
                'required'      => true
            ],
            'type' => [
                'type'          => 'string',
                'selection'     => [
                                    'I'  => 'individual (natural person)',
                                    'SE' => 'self-employed',
                                    'C'  => 'company',
                                    'NP' => 'non-profit',
                                    'PA' => 'public-administration'
                ],
                'description'   => 'Type of organisation.',
                'required'      => true
            ],
            'legal_name' => [
                'type'          => 'string',
                'description'   => 'Full name of the organisation (legal business name).',
                'required'      => true
            ],
            'description' => [
                'type'          => 'string',
                'description'   => 'A short reminder to help user identify the organisation (e.g. "Human Resources Consultancy Firm").'
            ],
            'phone' => [
                'type'          => 'string',
                'usage'         => 'phone',
                'description'   => 'Official contact phone number.' 
            ],
            'email' => [
                'type'          => 'string',
                'usage'         => 'email',
                'description'   => 'Official contact email address.' 
            ],
            'has_VAT' => [
                'type'          => 'string',
                'default'       => true,
                'description'   => 'Does the this organisation have a VAT number?.' 
            ],
            'VAT_number' => [
                'type'          => 'string',
                'description'   => 'Value Added Tax identification number, if any.',
                'visible'           => [ ['has_VAT', '=', true], ['type', '<>', 'I'] ]
            ],
            'registration_number' => [
                'type'          => 'string',
                'description'   => 'Organisation registration number (company number ), if any.'
            ],
            'citizen_identification' => [
                'type'          => 'string',
                'description'   => 'Citizen registration number, if any.',
                'visible'       => [ ['type', '=', 'I'] ]                
            ],
            'website' => [
                'type'          => 'string',
                'usage'         => 'uri:url',
                'description'   => 'Organisation main official website URL, if any.'
            ],  

            /*
                Description of the main address of the organisation (the headquarters, most of the time)
            */
            'address_street' => [
                'type'          => 'string',
                'description'   => 'Street and number of the headquarters address.'
            ],
            'address_dispatch' => [
                'type'          => 'string',
                'description'   => 'Optional info for mail dispatch (appartment, box, floor, ...).'
            ],
            'address_city' => [
                'type'          => 'string',
                'description'   => 'City in which headquarters are located.'
            ],
            'address_zip' => [
                'type'          => 'string',
                'description'   => 'Postal code of the headquarters address.'                
            ],
            'address_state' => [
                'type'          => 'string',
                'description'   => 'State the headquarters address.'
            ],
            'address_country' => [
                'type'          => 'string',
                'usage'         => 'country/iso-3166:2',
                'description'   => 'Country in which headquarters are located.' 
            ],
            /*
                The reference person is stored as part of the organisation. 
                That person must be entitled to legally represent the organisation (might be the director, the manager, the CEO, ...).
                These contact details are commonly requested by service providers for validating the identity of an organisation.
            */
            'contact_position' => [
                'type'          => 'string',
                'description'   => 'Position of the reference contact (natural person) for the organisation (legal person).'
            ],            
            'contact_firstname' => [
                'type'          => 'string',
                'description'   => 'Reference contact forename.'
            ],
            'contact_lastname' => [
                'type'          => 'string',
                'description'   => 'Reference contact surname.'
            ],
            'contact_gender' => [
                'type'          => 'string',
                'selection'     => ['M' => 'Male', 'F' => 'Female'],
                'description'   => 'Reference contact gender.'
            ],
            'contact_title' => [
                'type'          => 'string',
                'selection'     => ['Dr' => 'Doctor', 'Ms' => 'Miss', 'Mrs' => 'Misses', 'Mr' => 'Mister', 'Pr' => 'Professor'],
                'description'   => 'Title to be used for the reference contact.'
            ],
            'contact_date_of_birth' => [
                'type'          => 'date',
                'description'   => 'Date of birth of the reference contact.'
            ],
            'contact_lang' => [
                'type'          => 'string',
                'usage'         => 'language/iso-639:2',
                'description'   => 'Prefered spoken language of the contact.'
            ],

            /*
                Relational fields for
                Children entities and parent company, if any
            */
            'children_id' => [
                'type'              => 'one2many',
                'foreign_object'    => 'identity\Organisation',
                'foreign_field'     => 'parent_id',
                'description'       => 'Children organisations owned by the company, if any.' 
            ],
            'parent_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'identity\Organisation',
                'description'       => 'Parent company of which the organisation is a branch, if any.' 
            ],
            'contacts_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'identity\Contact',
                'foreign_field'     => 'organisation_id',
                'description'       => 'List of contacts related to the organisation (not necessarily employees), if any.' 
            ],
            'employees_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'identity\Employee',
                'foreign_field'     => 'organisation_id',
                'description'       => 'List of employees of the organisation, if any.' 
            ],
            'customers_ids' => [ 
                'type'              => 'many2many', 
                'foreign_object'    => 'sale\product\Organisation', 
                'foreign_field'     => 'providers_ids', 
                'rel_table'         => 'sale_product_rel_organisation_organisation', 
                'rel_foreign_key'   => 'provider_id',
                'rel_local_key'     => 'customer_id'
            ],
            'providers_ids' => [
                'type'              => 'many2many', 
                'foreign_object'    => 'sale\product\Organisation', 
                'foreign_field'     => 'customers_ids', 
                'rel_table'         => 'sale_product_rel_organisation_organisation', 
                'rel_foreign_key'   => 'customer_id',
                'rel_local_key'     => 'provider_id'
            ]
        ];
	}
}