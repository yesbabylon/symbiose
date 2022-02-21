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

            'email' => [
                'type'              => 'computed',
                'function'          => 'identity\Partner::getEmail',
                'result_type'       => 'string',
                'usage'             => 'email',
                'description'       => 'Email of the contact (from Identity).'
            ],

            'phone' => [
                'type'              => 'computed',
                'function'          => 'identity\Partner::getPhone',
                'result_type'       => 'string',
                'usage'             => 'phone',
                'description'       => 'Phone number of the contact (from Identity).'
            ],

            'title' => [
                'type'              => 'computed',
                'function'          => 'identity\Partner::getTitle',
                'result_type'       => 'string',
                'description'       => 'Title of the contact (from Identity).'
            ],

            'lang_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'core\Lang',
                'description'       => "Preferred language of the partner (relates to identity).",
                'default'           => 1
            ]

        ];
    }

    public function getUnique() {
        return [
            ['owner_identity_id', 'partner_identity_id', 'relationship']
        ];
    }

    public static function onchangeIdentity($om, $oids, $lang) {
        $res = $om->read(get_called_class(), $oids, [ 'partner_identity_id.lang_id' ], $lang);        
        if($res > 0 && count($res) ) {
            foreach($res as $oid => $odata) {
                $om->write(get_called_class(), $oids, [ 'lang_id' => $odata['partner_identity_id.lang_id'] ], $lang);        
            }
        }
        $om->write(get_called_class(), $oids, [ 'name' => null, 'title' => null, 'phone' => null, 'email' => null ], $lang);
        // force immediate re-computing of the name
        $om->read(get_called_class(), $oids, [ 'name' ], $lang);        
    }

    public static function getDisplayName($om, $oids, $lang) {
        $result = [];
        $partners = $om->read(get_called_class(), $oids, ['partner_identity_id.name'], $lang);
        foreach($partners as $oid => $partner) {
            $result[$oid] = '';
            if(isset($partner['partner_identity_id.name'])) {
                $result[$oid] = $partner['partner_identity_id.name'];
            }
        }
        return $result;
    }

    public static function getEmail($om, $oids, $lang) {
        $result = [];
        $partners = $om->read(get_called_class(), $oids, ['partner_identity_id.email'], $lang);
        foreach($partners as $oid => $partner) {
            $result[$oid] = '';
            if(isset($partner['partner_identity_id.email'])) {
                $result[$oid] = $partner['partner_identity_id.email'];
            }
        }
        return $result;
    }

    public static function getPhone($om, $oids, $lang) {
        $result = [];
        $partners = $om->read(get_called_class(), $oids, ['partner_identity_id.phone'], $lang);
        foreach($partners as $oid => $partner) {
            $result[$oid] = '';
            if(isset($partner['partner_identity_id.phone'])) {
                $result[$oid] = $partner['partner_identity_id.phone'];
            }
        }
        return $result;
    }

    public static function getTitle($om, $oids, $lang) {
        $result = [];
        $partners = $om->read(get_called_class(), $oids, ['partner_identity_id.title'], $lang);
        foreach($partners as $oid => $partner) {
            $result[$oid] = '';
            if(isset($partner['partner_identity_id.title'])) {
                $result[$oid] = $partner['partner_identity_id.title'];
            }
        }
        return $result;
    }

}