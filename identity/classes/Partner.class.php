<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace identity;
use equal\orm\Model;

class Partner extends Model {

    public static function getName() {
        return 'Partner';
    }

    public static function getDescription() {
        return "Partner is an entity that describes a relationship (contact, employee, customer, supplier, ...) between two Identities : an Owner identity (often, but not necessarily, the company) and a Partner identity.";
    }

    public static function getColumns() {
        return [

            'name' => [
                'type'              => 'computed',
                'result_type'       => 'string',
                'function'          => 'calcName',
                'store'             => true,
                'instant'           => true,
                'description'       => 'The display name of the partner (related organisation name).'
            ],

            'organisation_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'identity\Organisation',
                'description'       => 'Organisation of the current installation the partner belongs to (defaults to current).',
                'default'           => 1
            ],

            'is_internal' => [
                'type'              => 'boolean',
                'description'       => 'The partnership relates to (one of) the organization(s) from the current installation.',
                'default'           => true
            ],

            'owner_identity_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'identity\Identity',
                'description'       => 'The identity which the targeted identity is partner of.',
                'visible'           => ['is_internal', '=', false],
                'default'           => 1
            ],

            'partner_identity_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'identity\Identity',
                'dependents'        => ['name'],
                'description'       => 'The targeted identity (the partner).'
            ],

            'relationship' => [
                'type'              => 'string',
                'selection'         => [
                    'user',
                    'contact',
                    'employee',
                    'customer',
                    'supplier'
                ],
                'description'       => 'The kind of partnership that exists between the identities.'
            ],

            'type_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'identity\IdentityType',
                'dependents'        => ['type'],
                'default'           => 1,
                'description'       => 'Type of identity.',
                'help'              => 'Default value is Individual.'
            ],

            'type' => [
                'type'              => 'computed',
                'result_type'       => 'string',
                'store'             => true,
                'instant'           => true,
                'readonly'          => true,
                'description'       => 'Code of the type of identity.',
                'function'          => 'calcType'
            ],

            'has_vat' => [
                'type'              => 'boolean',
                'description'       => 'Does the organization have a VAT number?',
                'default'           => false
            ],

            'vat_number' => [
                'type'              => 'string',
                'description'       => 'Value Added Tax identification number, if any.',
                'visible'           => [ ['has_vat', '=', true] ]
            ],

            'legal_name' => [
                'type'              => 'string',
                'description'       => 'Full name of the Identity.',
                'visible'           => [ ['type', '<>', 'I'] ]
            ],

            'firstname' => [
                'type'              => 'string',
                'description'       => "Full name of the contact (must be a person, not a role).",
                'visible'           => ['type', '=', 'I']
            ],

            'lastname' => [
                'type'              => 'string',
                'description'       => 'Reference contact surname.',
                'visible'           => ['type', '=', 'I']
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
                'foreign_object'    => 'core\Lang',
                'description'       => "Preferred language of the identity.",
                'default'           => 1,
                'onupdate'          => 'onupdateLangId'
            ],

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
                'description'       => "Identity secondary phone number (mobile or landline)."
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
            ]

        ];
    }

    public static function calcType($self) {
        $result = [];
        $self->read(['type_id' => ['code']]);
        foreach($self as $id => $identity) {
            $result[$id] = $identity['type_id']['code'];
        }
        return $result;
    }

    public static function calcName($self) {
        $result = [];
        $self->read(['partner_identity_id' => ['name']]);
        foreach($self as $id => $partner) {
            if(isset($partner['partner_identity_id']['name'])) {
                $result[$id] = $partner['partner_identity_id']['name'];
            }
        }
        return $result;
    }

    /**
     * Signature for single object change from views.
     *
     * @param  Array    $event     Associative array holding changed fields as keys, and their related new values.
     * @param  Array    $values    Copy of the current (partial) state of the object (fields depend on the view).
     * @return Array    Associative array mapping fields with their resulting values.
     */
    public static function onchange($self, $event, $values) {
        $result = [];
        if(isset($event['type_id'])) {
            $type = IdentityType::id($event['type_id'])->read(['code'])->first();
            if($type) {
                $result['type'] = $type['code'];
            }
            if($event['type_id'] > 1) {
                $result['firstname'] = '';
                $result['lastname'] = '';
            }
        }
        return $result;
    }

    public static function onafterupdate($self, $values) {
        $self->read([
                'partner_identity_id', 'state',
                'type_id', 'has_vat', 'vat_number', 'legal_name', 'firstname', 'lastname',
                'email', 'phone', 'mobile', 'fax',
                'address_street', 'address_dispatch', 'address_zip', 'address_city', 'address_state', 'address_country'
            ]);

        foreach($self as $id => $partner) {
            if($partner['state'] == 'draft') {
                continue;
            }
            if(is_null($partner['partner_identity_id'])) {
                $identity = Identity::create([
                        'type_id'           => $partner['type_id'],
                        'has_vat'           => $partner['has_vat'],
                        'vat_number'        => $partner['vat_number'],
                        'legal_name'        => $partner['legal_name'],
                        'firstname'         => $partner['firstname'],
                        'lastname'          => $partner['lastname'],
                        'email'             => $partner['email'],
                        'phone'             => $partner['phone'],
                        'mobile'            => $partner['mobile'],
                        'fax'               => $partner['fax'],
                        'address_street'    => $partner['address_street'],
                        'address_dispatch'  => $partner['address_dispatch'],
                        'address_zip'       => $partner['address_zip'],
                        'address_city'      => $partner['address_city'],
                        'address_state'     => $partner['address_state'],
                        'address_country'   => $partner['address_country']
                    ])
                    ->read(['id'])
                    ->first();

                self::id($id)->update(['partner_identity_id' => $identity['id']]);
            }
            else {
                foreach($values as $field => $value) {
                    Identity::id($partner['partner_identity_id'])->update([$field => $value]);
                }
            }
        }
    }

    public static function getConstraints() {
        return Identity::getConstraints();
    }
}
