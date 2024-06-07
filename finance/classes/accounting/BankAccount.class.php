<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2024
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

namespace finance\accounting;

use equal\orm\Model;
use identity\Organisation;

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
                'readonly'          => true,
                'unique'            => true
            ],

            'organisation_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'identity\Organisation',
                'description'       => 'The organization that owns the bank account.',
                'dependents'        => ['name'],
                'ondelete'          => 'cascade',
                'visible'           => ['organisation_id', '<>', null],
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
                'usage'             => 'uri/urn:iban',
                'description'       => 'The IBAN number of the organization’s bank account.',
                'dependents'        => ['name','bank_country'],
                'required'          => true,
                'onupdate'          => 'onupdateOrganisationBA'
            ],

            'bank_account_bic' => [
                'type'              => 'string',
                'description'       => 'The BIC code of the bank related to the organization’s bank account.',
                'onupdate'          => 'onupdateOrganisationBA'
            ]

        ];
    }


    public static function onupdateOrganisationBA($self) {
        $self->read(['id','organisation_id','bank_account_iban','bank_account_bic']);
        foreach($self as $id => $bankAccount) {
            $organisation = Organisation::id($bankAccount['organisation_id'])->read(['id', 'bank_account_ids'])->first(true);
            if ($organisation){
                $first_bank_acccount = min($organisation['bank_account_ids']);
                if($id == $first_bank_acccount) {
                    Organisation::id($bankAccount['organisation_id'])
                       ->update([
                           'bank_account_iban' => $bankAccount['bank_account_iban'],
                           'bank_account_bic'  => $bankAccount['bank_account_bic']
                       ]);
               }
            }
        }
    }

    public static function onchange($event, $values) {
        $result = [];

        if(isset($event['bank_account_iban'])){
            $result['bank_country'] = self::createBankCountry(['bank_account_iban' => $event['bank_account_iban']]);
        }

        if(isset($event['organisation_id'])|| isset($event['bank_account_iban'])) {

            $result['name'] = self::createName([
                'organisation_id'        => $event['organisation_id'] ?? $values['organisation_id'],
                'bank_account_iban'      => $event['bank_account_iban'] ?? $values['bank_account_iban'],
            ]);
        }
        return $result;
    }

    public static function calcBankCountry($self) {
        $result = [];
        $self->read(['bank_account_iban']);
        foreach($self as $id => $bankAccount) {
            $result[$id]  = self::createBankCountry($bankAccount);
        }
        return $result;
    }

    private static function createBankCountry($bankAccount) {
        $country = '';
        if(isset($bankAccount['bank_account_iban']) && strlen($bankAccount['bank_account_iban']) > 0){
            $country = substr($bankAccount['bank_account_iban'], 0, 2);
        }
        return $country;
    }


    public static function calcName($self) {
        $result = [];
        $self->read(['organisation_id', 'bank_account_iban']);
        foreach($self as $id => $bankAccount) {
            $result[$id]  = self::createName($bankAccount);
        }
        return $result;
    }


    private static function createName($bankAccount) {
        $name = '';
        $organisation = Organisation::id($bankAccount['organisation_id'])->read(['id','name'])->first(true);
        if(isset($organisation) && isset($bankAccount['bank_account_iban']) && strlen($bankAccount['bank_account_iban']) > 0){
            $name = '['.$organisation['name']. ' - '. $bankAccount['bank_account_iban'] .']';
        }
        return $name;
    }

    public static function candelete($self) {
        $self->read(['organisation_id']);
        foreach($self as $bankAccount) {
            $organisation = Organisation::id($bankAccount['organisation_id'])->read(['id', 'bank_account_ids'])->first(true);
            if (count($organisation['bank_account_ids']) == 1 ) {
                return ['id' => ['non_removable' => 'The bank account cannot be removed. The organization must have at least one bank account.']];
            }
        }

        return parent::candelete($self);
    }

}