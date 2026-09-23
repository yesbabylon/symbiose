<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2024
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

namespace identity;
use finance\bank\BankAccount;

class Organization extends IdentityFacet {

    public static function getName() {
        return 'Organization';
    }

    public static function getModelTable(): string {
        return self::getSlug();
    }

    public static function getDescription() {
        return 'Organizations are the legal entities to which the ERP is dedicated. By convention, the main Organization uses ID 1.';
    }

    public static function getColumns() {
        return [

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
                'foreign_field'     => 'organization_id',
                'description'       => 'List of bank accounts of the organization.',
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
                'description'       => 'BIC of the main organization bank account.',
                'onupdate'          => 'onupdateBankAccountBic'
            ],

        ];
    }

    public function getUniques(): array {
        return [['identity_id']];
    }

    public static function onupdateBankAccountIban($self) {
        $self->read(['bank_account_ids', 'bank_account_iban', 'bank_account_bic']);
        foreach($self as $id => $organization) {
            if(!isset($organization['bank_account_ids']) || empty($organization['bank_account_ids'])) {
                BankAccount::create([
                    'organization_id'   => $organization['id'],
                    'bank_account_iban' => $organization['bank_account_iban'],
                    'bank_account_bic'  => $organization['bank_account_bic']
                ]);
            }
            else {
                $bank_account_id = reset($organization['bank_account_ids']);
                BankAccount::id($bank_account_id)->update([
                    'bank_account_iban' => $organization['bank_account_iban']
                ]);
            }
        }
    }

    public static function onupdateBankAccountBic($self) {
        $self->read(['bank_account_ids', 'bank_account_bic']);
        foreach($self as $id => $organization) {
            // #memo - we don't create an account here since IBAN might not have been provided
            if(isset($organization['bank_account_ids']) && !empty($organization['bank_account_ids'])) {
                $bank_account_id = reset($organization['bank_account_ids']);
                BankAccount::id($bank_account_id)->update([
                    'bank_account_bic'  => $organization['bank_account_bic']
                ]);
            }
        }
    }

}
