<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2024
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

namespace identity;
use finance\accounting\BankAccount;

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
                'default'           => 'C',
                'readonly'          => true
            ],

            'bank_account_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'finance\accounting\BankAccount',
                'foreign_field'     => 'organisation_id',
                'description'       => 'List of the bank account of the organisation',
                'ondetach'          => 'delete',
            ],

            'bank_account_iban' => [
                'type'              => 'string',
                'usage'             => 'uri/urn:iban',
                'description'       => "Number of the bank account of the Identity, if any.",
                'onupdate'          => 'onupdateBankAccount'
            ],

            'bank_account_bic' => [
                'type'              => 'string',
                'description'       => "Identifier of the Bank related to the Organisation's bank account, when set.",
                'onupdate'          => 'onupdateBankAccount'
            ],

        ];
    }

    public static function onupdateBankAccount($self) {
        $self->read(['id','bank_account_iban','bank_account_bic', 'bank_account_ids']);
        foreach($self as $id => $organisation) {
            $bankAccount_ids = BankAccount::search(['id', 'in', $organisation['bank_account_ids']])->ids();
            if(!$bankAccount_ids) {
                BankAccount::create([
                    'organisation_id'   => $organisation['id'],
                    'bank_account_iban' => $organisation['bank_account_iban'],
                    'bank_account_bic'  => $organisation['bank_account_bic']
                ]);
            } else {
                $first_bankAcccount = min($bankAccount_ids);
                BankAccount::id($first_bankAcccount)->update([
                    'bank_account_iban' => $organisation['bank_account_iban'],
                    'bank_account_bic'  => $organisation['bank_account_bic']
                ]);
            }
        }
    }

    public static function onupdateIdentityId($self) {
        $self->read(['identity_id']);
        foreach($self as $id => $organisation) {
            Identity::id($organisation['identity_id'])->update(['is_organisation' => true, 'organisation_id' => $id]);
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
