<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2026
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace identity;

use finance\bank\BankAccount;

/**
 * Canonical representation of a natural or legal person.
 *
 * Role objects keep local projections of common fields, but Identity remains the
 * source of truth and owns every explicit role backlink.
 */
class Identity extends IdentityAbstract {

    public static function getName() {
        return 'Identity';
    }

    public static function getDescription() {
        return 'Canonical identity of a natural or legal person, independently of its business roles.';
    }

    public static function getColumns() {
        return [
            'name' => [
                'type'        => 'computed',
                'result_type' => 'string',
                'function'    => 'calcName',
                'store'       => true,
                'instant'     => true,
                'description' => 'The display name of the identity.'
            ],

            'hash_sha256' => [
                'type'        => 'string',
                'usage'       => 'text/plain:64',
                'description' => 'SHA256 hash of the identity.',
                'readonly'    => true
            ],

            'identity_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'identity\Identity',
                'description'       => 'Identity the object relates to.',
                'help'              => 'Identity objects do not have a identity_id backlink.
                    Therefore it is forced to be left to null using `required`.',
                'readonly'          => true
            ],

            'identity_slug' => [
                'type'        => 'string',
                'description' => 'Slug for helping identify duplicates.',
                'help'        => '{type-legal_name-zip-country}'
            ],

            'slug_hash' => [
                'type'        => 'string',
                'usage'       => 'text/plain:32',
                'description' => 'Hash of the identity slug.'
            ],

            'type_id' => [
                'type'           => 'many2one',
                'foreign_object' => 'identity\IdentityType',
                'default'        => 1,
                'dependents'     => ['type', 'name', 'identity_slug', 'slug_hash'],
                'description'    => 'Type of identity.'
            ],

            'children_ids' => [
                'type'           => 'one2many',
                'foreign_object' => 'identity\Identity',
                'foreign_field'  => 'parent_id',
                'domain'         => [['id', '<>', 'object.id'], ['type', '<>', 'IN']],
                'description'    => 'Children departments of the organization, if any.',
                'visible'       => [['type', '<>', 'IN']]
            ],

            'has_parent' => [
                'type'        => 'boolean',
                'description' => 'Does the identity have a parent organization?',
                'visible'    => [['type', '<>', 'IN']],
                'default'     => false
            ],

            'parent_id' => [
                'type'           => 'many2one',
                'foreign_object' => 'identity\Identity',
                'domain'         => [['id', '<>', 'object.id'], ['type', '<>', 'IN']],
                'description'    => 'Parent company of which the organization is a branch, if any.',
                'visible'       => [['has_parent', '=', true]]
            ],

            'address_hash' => [
                'type'        => 'string',
                'usage'       => 'text/plain:32',
                'description' => 'Hash of the normalized main address.'
            ],

            'addresses_ids' => [
                'type'           => 'one2many',
                'foreign_object' => 'identity\Address',
                'foreign_field'  => 'identity_id',
                'description'    => 'Additional addresses related to the identity.'
            ],

            'reference_identity_id' => [
                'type'           => 'many2one',
                'foreign_object' => 'identity\Identity',
                'description'    => 'Natural person entitled to represent the identity.',
                'visible'       => [['type', '<>', 'IN'], ['type', '<>', 'SE']]
            ],

            // Compatibility link: User is not an Identity role and receives a limited field projection.
            'user_id' => [
                'type'           => 'many2one',
                'foreign_object' => 'identity\User',
                'description'    => 'User associated with this identity, if any.',
                'visible'       => [['type', '=', 'IN']]
            ],

            'organization_id' => [
                'type'           => 'many2one',
                'foreign_object' => 'identity\Organization',
                'description'    => 'Organization role associated with this identity, if any.'
            ],

            'contact_id' => [
                'type'           => 'many2one',
                'foreign_object' => 'identity\Contact',
                'description'    => 'Contact role associated with this identity, if any.'
            ],

            'customer_contact_id' => [
                'type'           => 'many2one',
                'foreign_object' => 'sale\customer\Contact',
                'description'    => 'Legacy customer contact associated with this identity, if any.'
            ],

            'employee_id' => [
                'type'           => 'many2one',
                'foreign_object' => 'hr\employee\Employee',
                'description'    => 'Employee role associated with this identity, if any.'
            ],

            'customer_id' => [
                'type'           => 'many2one',
                'foreign_object' => 'sale\customer\Customer',
                'description'    => 'Customer role associated with this identity, if any.'
            ],

            'supplier_id' => [
                'type'           => 'many2one',
                'foreign_object' => 'purchase\supplier\Supplier',
                'description'    => 'Supplier role associated with this identity, if any.'
            ]
        ];
    }

    public static function getActions() {
        return [
            'refresh_bank_accounts' => [
                'description'   => 'Force sync between Identity main bank account and additional ones.',
                'function'      => 'doRefreshBankAccounts'
            ],
            'refresh_addresses' => [
                'description'   => 'Force sync between Identity main bank account and additional ones.',
                'function'      => 'doRefreshAddresses'
            ]
        ];
    }

    // #memo - this is also done in onafterupdate handler
    protected static function doRefreshBankAccounts($self) {
        $self->read(['bank_account_iban', 'bank_account_bic', 'bank_name', 'bank_country', 'supplier_id']);

        foreach($self as $id => $identity) {
            if(!$identity['bank_account_iban'] || strlen($identity['bank_account_iban']) <= 0) {
                continue;
            }
            $mainBankAccount = BankAccount::search([
                    ['owner_identity_id', '=', $id],
                    ['bank_account_iban', '=', $identity['bank_account_iban']]
                ])
                ->read(['is_primary', 'supplier_id'])
                ->first();

            if(!$mainBankAccount) {
                BankAccount::create([
                    'owner_identity_id' => $id,
                    'is_primary'        => true,
                    'bank_account_iban' => $identity['bank_account_iban'],
                    'bank_account_bic'  => $identity['bank_account_bic'],
                    'bank_name'         => $identity['bank_name'],
                    'bank_country'      => $identity['bank_country'],
                    'supplier_id'       => $identity['supplier_id']
                ]);
            }
            else {
                if(!$mainBankAccount['is_primary']) {
                    BankAccount::search([['owner_identity_id', '=', $id]])
                        ->update(['is_primary' => false]);

                    BankAccount::id($mainBankAccount['id'])
                        ->update([
                            'is_primary'    => true,
                            'supplier_id'   => $identity['supplier_id']
                        ]);
                }
                elseif($identity['supplier_id'] && $mainBankAccount['supplier_id'] !== $identity['supplier_id']) {
                    BankAccount::id($mainBankAccount['id'])
                        ->update(['supplier_id' => $identity['supplier_id']]);
                }
            }
        }
    }

    // #memo - this is also done in onafterupdate handler
    protected static function doRefreshAddresses($self) {
        // sync primary address
        $self->read(['identity_id', 'address_street', 'address_dispatch', 'address_zip', 'address_city', 'address_state', 'address_country']);

        foreach($self as $id => $identity) {
            if(!$identity['address_street'] || strlen($identity['address_street']) <= 0) {
                continue;
            }
            $identity_id = $identity['identity_id'] ?? $id;
            $mainAddress = Address::search([['owner_identity_id', '=', $identity_id], ['is_primary', '=', true]])->first();
            if(!$mainAddress) {
                $mainAddress = Address::create([
                    'owner_identity_id' => $identity_id,
                    'is_primary'        => true,
                    'address_street'    => $identity['address_street'],
                    'address_dispatch'  => $identity['address_dispatch'],
                    'address_zip'       => $identity['address_zip'],
                    'address_city'      => $identity['address_city'],
                    'address_state'     => $identity['address_state'],
                    'address_country'   => $identity['address_country']
                ]);
            }
        }
    }

    /**
     * For organizations the name is the legal name; for individuals it is firstname + lastname.
     */
    protected static function calcName($self) {
        $result = [];
        $self->read(['type', 'firstname', 'lastname', 'legal_name', 'short_name']);
        foreach($self as $id => $identity) {
            $parts = [];
            if($identity['type'] == 'IN') {
                if(!empty($identity['firstname'])) {
                    $parts[] = ucfirst($identity['firstname']);
                }
                if(!empty($identity['lastname'])) {
                    $parts[] = mb_strtoupper($identity['lastname']);
                }
            }
            if(!$parts) {
                if(!empty($identity['legal_name'])) {
                    $parts[] = $identity['legal_name'];
                }
                elseif(!empty($identity['short_name'])) {
                    $parts[] = $identity['short_name'];
                }
            }
            $result[$id] = implode(' ', $parts);
        }
        return $result;
    }


    protected static function onafterupdate($self, $values, $orm) {
        $updated_values = self::computeCommonIdentityValues($values);

        $fields = array_keys($updated_values);
        $sync_user = array_intersect(['firstname', 'lastname', 'lang_id', 'user_id'], array_keys($values));

        $self->read(array_merge(['user_id'], array_keys(self::MAP_FIELDS_FACETS)));
        try {
            $events = $orm->disableEvents();
            foreach($self as $id => $identity) {
                foreach(self::MAP_FIELDS_FACETS as $facet_field => $descriptor) {
                    if(!($identity[$facet_field] ?? null)) {
                        continue;
                    }
                    $identityFacet = $descriptor['class']::id($identity[$facet_field])->read($fields)->first();
                    $map = [];
                    foreach($updated_values as $field => $value) {
                        if($value !== ($identityFacet[$field] ?? null)) {
                            $map[$field] = $value;
                        }
                    }
                    // propagate update to facet class
                    $descriptor['class']::id($identity[$facet_field])->update($map);
                    // update backlink if required
                    if(isset($values[$facet_field])) {
                        $descriptor['class']::id($identity[$facet_field])->update(['identity_id' => $id]);
                        if(is_subclass_of($descriptor['class'], IdentityFacet::class)) {
                            $descriptor['class']::id($identity[$facet_field])->do('sync_from_identity');
                        }
                    }
                }
                if($sync_user && ($identity['user_id'] ?? null)) {
                    if(array_key_exists('user_id', $values)) {
                        User::id($identity['user_id'])->update(['identity_id' => $id]);
                    }
                    User::id($identity['user_id'])->do('sync_from_identity');
                }
            }
        }
        finally {
            $orm->enableEvents($events);
        }
    }

    public static function candelete($self, $values) {
        $backlinks = array_merge(['user_id'], array_keys(self::MAP_FIELDS_FACETS));
        $self->read($backlinks);
        foreach($self as $identity) {
            foreach($backlinks as $backlink) {
                if(!empty($identity[$backlink])) {
                    return ['id' => ['has_roles' => 'An Identity with an active role cannot be deleted.']];
                }
            }
        }
        return parent::candelete($self, $values);
    }

    /**
     * Signature for single object change from views.
     *
     * @param  Array    $event     Associative array holding changed fields as keys, and their related new values.
     * @param  Array    $values    Copy of the current (partial) state of the object (fields depend on the view).
     * @return Array    Associative array mapping fields with their resulting values.
     */
    public static function onchange($self, $event, $values, $lang) {
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

        if(isset($event['address_zip']) && isset($values['address_country'])) {
            $list = self::computeCitiesByZip($event['address_zip'], $values['address_country'], $lang);
            if($list) {
                $result['address_city'] = [
                    'value' => '',
                    'selection' => $list
                ];
            }
        }

        if(isset($event['citizen_identification'])) {
            // remove spacing chars
            $result['citizen_identification'] = preg_replace('/[^0-9]/i', '', $event['citizen_identification']);
        }

        if(isset($event['vat_number'])) {
            // remove spacing chars
            $result['vat_number'] = preg_replace('/[^A-Z0-9]/i', '', $event['vat_number']);
        }

        if(isset($event['has_vat']) && $event['has_vat']) {
            if(isset($values['address_country'], $values['registration_number']) && $values['address_country'] === 'BE') {
                $result['vat_number'] = 'BE' . $values['registration_number'];
            }
        }

        if(isset($event['registration_number'])) {
            // remove spacing chars
            $result['registration_number'] = preg_replace('/[^0-9]/i', '', $event['registration_number']);
        }

        if(isset($event['bank_account_iban'])) {
            // remove spacing chars
            $result['bank_account_iban'] = preg_replace('/[^A-Z0-9]/i', '', $event['bank_account_iban']);
            $bank_info = self::computeBankFromIban($result['bank_account_iban']);
            if($bank_info) {
                $result['bank_account_bic'] = $bank_info['bic'];
            }
        }

        if(isset($event['phone'])) {
            $result['phone'] = preg_replace('/[^\d+]/', '', $event['phone']);
        }

        if(isset($event['phone_alt'])) {
            $result['phone_alt'] = preg_replace('/[^\d+]/', '', $event['phone_alt']);
        }

        if(isset($event['mobile'])) {
            $result['mobile'] = preg_replace('/[^\d+]/', '', $event['mobile']);
        }

        if(isset($event['email'])) {
            $result['email'] = trim($event['email']);
        }

        return $result;
    }

    private static function getCitiesByZip($zip, $country, $lang) {
        $result = null;
        $file = EQ_BASEDIR."/packages/identity/i18n/{$lang}/zipcodes/{$country}.json";
        if(file_exists($file)) {
            $map_zip = json_decode(file_get_contents($file), true);
            if(isset($map_zip[$zip])) {
                $result = $map_zip[$zip];
            }
        }
        if(!$result) {
            $file = EQ_BASEDIR."/packages/identity/i18n/en/zipcodes/{$country}.json";
            if(file_exists($file)) {
                $map_zip = json_decode(file_get_contents($file), true);
                if(isset($map_zip[$zip])) {
                    $result = $map_zip[$zip];
                }
            }
        }
        return $result;
    }

    /**
     * Check wether an object can be updated, and perform some additional operations if necessary.
     * This method can be overridden to define a more precise set of tests.
     *
     * @param  object   $om         ObjectManager instance.
     * @param  array    $ids       List of objects identifiers.
     * @param  array    $values     Associative array holding the new values to be assigned.
     * @param  string   $lang       Language in which multilang fields are being updated.
     * @return array    Returns an associative array mapping fields with their error messages. En empty array means that object has been successfully processed and can be updated.
     */
    public static function canupdate($om, $ids, $values, $lang='en') {
        if(isset($values['type_id'])) {
            $identities = $om->read(Identity::getType(), $ids, [ 'type', 'firstname', 'lastname', 'legal_name', 'registration_number' ], $lang);
            foreach($identities as $id => $identity) {
                // if type == 'CO' then registration_number is mandatory
                $type = $values['type'] ?? $identity['type'];
                if($type === 'CO') {
                    $registration_number = $values['registration_number'] ?? $identity['registration_number'];
                    if(!$registration_number || strlen($registration_number) <= 0) {
                        return ['registration_number' => ['missing_registration_number' => 'Registration number is mandatory for companies.']];
                    }
                }
                elseif($type === 'IN') {
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
                        return ['firstname' => ['missing' => "Firstname cannot be empty for natural person (identity $id)."]];
                    }
                    if(!strlen($lastname) ) {
                        return ['lastname' => ['missing' => "Lastname cannot be empty for natural person (identity $id)."]];
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
        return parent::canupdate($om, $ids, $values, $lang);
    }
}
