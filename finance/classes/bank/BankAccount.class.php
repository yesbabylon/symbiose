<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2024
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace finance\bank;

use equal\orm\Model;
use identity\Organization;

class BankAccount extends Model {

    public static function getColumns() {

        return [
            'name' => [
                'type'              => 'computed',
                'function'          => 'calcName',
                'result_type'       => 'string',
                'description'       => 'The display name of the organization and IBAN.',
                'store'             => true,
                'instant'           => true,
                'readonly'          => true
            ],

            'organization_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'identity\Organization',
                'description'       => 'The organization that owns the bank account.',
                'dependents'        => ['name'],
                'ondelete'          => 'cascade',
                'visible'           => ['organization_id', '<>', null],
                'required'          => true
            ],

            'bank_country' => [
                'type'              => 'computed',
                'function'          => 'calcBankCountry',
                'result_type'       => 'string',
                'usage'             => 'country/iso-3166:2',
                'description'       => 'The country where the organization holds the bank account, specified using the ISO 3166-2 code.',
                'store'             => true,
                'instant'           => true,
                'readonly'          => true
            ],

            'bank_name' => [
                'type'              => 'string',
                'description'       => 'The name of the bank where the organization holds its account.'
            ],

            'bank_account_iban' => [
                'type'              => 'string',
                'usage'             => 'uri/urn.iban',
                'description'       => 'The IBAN number of the organization’s bank account.',
                'dependents'        => ['name','bank_country'],
                'required'          => true,
                'onupdate'          => 'onupdateBankAccountIban'
            ],

            'bank_account_bic' => [
                'type'              => 'string',
                'description'       => 'The BIC code of the bank related to the organization’s bank account.',
                'onupdate'          => 'onupdateBankAccountBic'
            ]

        ];
    }


    public static function onupdateBankAccountIban($self) {
        $self->read(['organization_id', 'bank_account_iban']);
        foreach($self as $id => $bankAccount) {
            $organization = Organization::id($bankAccount['organization_id'])->read(['id', 'bank_account_ids'])->first();
            if($organization) {
                // by convention, if current bank account is the first of the organization, sync back with iban from organization
                $first_bank_account_id = min($organization['bank_account_ids']);
                if($id == $first_bank_account_id) {
                    Organization::id($bankAccount['organization_id'])
                       ->update([
                           'bank_account_iban' => $bankAccount['bank_account_iban']
                       ]);
               }
            }
        }
    }

    public static function onupdateBankAccountBic($self) {
        $self->read(['organization_id', 'bank_account_bic']);
        foreach($self as $id => $bankAccount) {
            $organization = Organization::id($bankAccount['organization_id'])->read(['id', 'bank_account_ids'])->first();
            if($organization) {
                // by convention, if current bank account is the first of the organization, sync back with iban from organization
                $first_bank_account_id = min($organization['bank_account_ids']);
                if($id == $first_bank_account_id) {
                    Organization::id($bankAccount['organization_id'])
                       ->update([
                           'bank_account_bic' => $bankAccount['bank_account_bic']
                       ]);
               }
            }
        }
    }

    public static function onchange($event, $values) {
        $result = [];

        if(isset($event['bank_account_iban'])) {
            $result['bank_country'] = self::computeCountryFromIban($event['bank_account_iban']);
        }

        if(isset($event['organization_id']) || isset($event['bank_account_iban'])) {
            $result['name'] = self::computeName($event['organization_id'] ?? $values['organization_id'], $event['bank_account_iban'] ?? $values['bank_account_iban']);
        }

        return $result;
    }

    public static function calcBankCountry($self) {
        $result = [];
        $self->read(['bank_account_iban']);
        foreach($self as $id => $bankAccount) {
            $result[$id]  = self::computeCountryFromIban($bankAccount['bank_account_iban']);
        }
        return $result;
    }

    public static function calcName($self) {
        $result = [];
        $self->read(['organization_id', 'bank_account_iban']);
        foreach($self as $id => $bankAccount) {
            $result[$id] = self::computeName($bankAccount['organization_id'], $bankAccount['bank_account_iban']);
        }
        return $result;
    }

    public static function candelete($self) {
        $self->read(['organization_id']);
        foreach($self as $bankAccount) {
            $organization = Organization::id($bankAccount['organization_id'])->read(['bank_account_ids'])->first();
            if(count($organization['bank_account_ids']) <= 1 ) {
                return ['id' => ['non_removable' => 'The bank account cannot be removed. Organizations must have at least one bank account.']];
            }
        }

        return parent::candelete($self);
    }

    private static function computeCountryFromIban($iban) {
        $country = '';
        if($iban && strlen($iban) > 0) {
            $country = substr($iban, 0, 2);
        }
        return $country;
    }

    private static function computeName($organization_id, $iban) {
        $name = '';
        $organization = Organization::id($organization_id)->read(['name'])->first();
        if($organization && $iban && strlen($iban) > 0){
            $name = $organization['name'] . ' - ' . $iban;
        }
        return $name;
    }

}
