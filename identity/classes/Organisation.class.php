<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2024
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

namespace identity;
use finance\bank\BankAccount;

class Organisation extends Identity {

    public static function getName() {
        return 'Organisation';
    }

    public function getTable() {
        return 'identity_organisation';
    }

    public static function getDescription() {
        return 'Organizations are the legal entities to which the ERP is dedicated. By convention, the main Organization uses ID 1.';
    }

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

            'identity_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'identity\Identity',
                'description'       => 'Identity the organisation relates to.',
                'onupdate'          => 'onupdateIdentityId'
            ],

            'type_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'identity\IdentityType',
                'description'       => 'Type of identity.',
                'domain'            => ['id', '<>', 1],
                'default'           => 3
            ],

            'type' => [
                'type'              => 'string',
                'default'           => 'CO',
                'readonly'          => true
            ],

            'bank_account_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'finance\bank\BankAccount',
                'foreign_field'     => 'organisation_id',
                'description'       => 'List of the bank account of the organisation',
                'ondetach'          => 'delete',
                'order'             => 'id',
                'sort'              => 'asc'
            ],

            'bank_account_iban' => [
                'type'              => 'string',
                'usage'             => 'uri/urn.iban',
                'description'       => "Number of the bank account of the Identity, if any.",
                'onupdate'          => 'onupdateBankAccountIban'
            ],

            'bank_account_bic' => [
                'type'              => 'string',
                'description'       => "Identifier of the Bank related to the Organisation's bank account, when set.",
                'onupdate'          => 'onupdateBankAccountBic'
            ],

        ];
    }

    public static function calcName($self) {
        $result = [];
        $self->read(['identity_id' => ['type', 'firstname', 'lastname', 'legal_name', 'short_name']]);
        foreach($self as $id => $organisation) {
            $identity = $organisation['identity_id'];
            $parts = [];
            if($identity['type'] == 'IN') {
                if(isset($identity['firstname']) && strlen($identity['firstname'])) {
                    $parts[] = ucfirst($identity['firstname']);
                }
                if(isset($identity['lastname']) && strlen($identity['lastname']) ) {
                    $parts[] = mb_strtoupper($identity['lastname']);
                }
            }
            if(empty($parts) ) {
                if(isset($identity['legal_name']) && strlen($identity['legal_name'])) {
                    $parts[] = $identity['legal_name'];
                }
                elseif(isset($identity['short_name']) && strlen($identity['short_name'])) {
                    $parts[] = $identity['short_name'];
                }
            }
            $result[$id] = implode(' ', $parts);
        }
        return $result;
    }

    public static function onupdateBankAccountIban($self) {
        $self->read(['bank_account_ids', 'bank_account_iban', 'bank_account_bic']);
        foreach($self as $id => $organisation) {
            if(!isset($organisation['bank_account_ids']) || empty($organisation['bank_account_ids'])) {
                BankAccount::create([
                    'organisation_id'   => $organisation['id'],
                    'bank_account_iban' => $organisation['bank_account_iban'],
                    'bank_account_bic'  => $organisation['bank_account_bic']
                ]);
            }
            else {
                $bank_account_id = reset($organisation['bank_account_ids']);
                BankAccount::id($bank_account_id)->update([
                    'bank_account_iban' => $organisation['bank_account_iban']
                ]);
            }
        }
    }

    public static function onupdateBankAccountBic($self) {
        $self->read(['bank_account_ids', 'bank_account_bic']);
        foreach($self as $id => $organisation) {
            // #memo - we don't create an account here since IBAN might not have been provided
            if(isset($organisation['bank_account_ids']) && !empty($organisation['bank_account_ids'])) {
                $bank_account_id = reset($organisation['bank_account_ids']);
                BankAccount::id($bank_account_id)->update([
                    'bank_account_bic'  => $organisation['bank_account_bic']
                ]);
            }
        }
    }

    public static function onupdateIdentityId($self) {
        $self->read(['identity_id']);
        foreach($self as $id => $organisation) {
            Identity::id($organisation['identity_id'])->update(['organisation_id' => $id]);
        }
    }

    /**
     * Upon update, synchronize common fields with related Identity
     */
    public static function onafterupdate($self, $values, $orm) {
        $identity_fields = $orm->getModel(Identity::getType())->getSchema();
        $self->read(['identity_id']);
        $identity_values = array_intersect_key($values, $identity_fields);
        foreach($self as $id => $organisation) {
            Identity::id($organisation['identity_id'])->update($identity_values);
        }
    }
}
