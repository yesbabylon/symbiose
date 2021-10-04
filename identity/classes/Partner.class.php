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
        return "A Partner describes a relationship between two Identities (contact, employee, customer, provider, payer, other).";
    }
    
    public static function getColumns() {
        return [

            'name' => [
                'type'              => 'computed',
                'function'          => 'identity\Partner::getDisplayName',
                'result_type'       => 'string',
                'store'             => true,
                'description'       => 'The display name of the partner (related organisation name).'
            ],

            'owner_identity_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'identity\Identity',
                'description'       => 'The organisation which the targeted identity is a partner of.'
            ],

            'partner_identity_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'identity\Identity',
                'description'       => 'The targeted identity (the partner).',
                'onchange'          => 'identity\Partner::onchangeIdentity'
            ],

            'relationship' => [
                'type'              => 'string',
                'selection'         => [ 'contact', 'employee', 'customer', 'provider', 'payer', 'other' ],
                'description'       => 'The kind of partnership that exists between the identities.' 
            ],

            // if partner is a contact, keep the organisation (s)he is a contact from
            'partner_organisation_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'identity\Identity',
                'description'       => 'Target organisation which the contact is working for.',
                'visible'           => [ ['relationship', '=', 'contact'] ]
            ],

            // if partner is a contact, keep its 'position' within the 
            'partner_position' => [
                'type'              => 'string',
                'description'       => 'Position of the contact (natural person) within the target organisation (legal person), e.g. \'director\', \'CEO\', \'Regional manager\'.',
                'visible'           => [ ['relationship', '=', 'contact'] ]
            ],

            // if partner is a customer, it can have an external reference (e.g. reference assigned by previous software)
            'customer_external_ref' => [
                'type'              => 'string',
                'description'       => 'External reference for customer, if any.',
                'visible'           => ['relationship', '=', 'customer']
            ],
            
        ];
    }

    public function getUnique() {
        return [
            ['owner_identity_id', 'partner_identity_id', 'relationship']
        ];
    }       

    public static function onchangeIdentity($om, $oids, $lang) {
        $om->write(__CLASS__, $oids, [ 'name' => null ], $lang);
    }

    public static function getDisplayName($om, $oids, $lang) {
        $result = [];
        $partners = $om->read(__CLASS__, $oids, ['partner_identity_id.name'], $lang);
        foreach($partners as $oid => $partner) {
            $result[$oid] = '';
            if(isset($partner['partner_identity_id.name'])) {
                $result[$oid] = $partner['partner_identity_id.name'];
            }
        }
        return $result;
    }
}