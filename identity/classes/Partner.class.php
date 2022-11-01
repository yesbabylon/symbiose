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
                'function'          => 'calcName',
                'result_type'       => 'string',
                'store'             => true,
                'description'       => 'The display name of the partner (related organisation name).'
            ],

            'owner_identity_id' => [
                'type'              => 'many2one',
                'foreign_object'    => Identity::getType(),
                'description'       => 'The identity organisation which the targeted identity is a partner of.',
                'default'           => 1
            ],

            'partner_identity_id' => [
                'type'              => 'many2one',
                'foreign_object'    => Identity::getType(),
                'description'       => 'The targeted identity (the partner).',
                'onupdate'          => 'onupdatePartnerIdentityId',
                'required'          => true
            ],

            'relationship' => [
                'type'              => 'string',
                'selection'         => [
                    'contact',
                    'employee',
                    'customer',
                    'provider',
                    'payer',
                    'other'
                ],
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

            // #memo - email remains related to identity
            'email' => [
                'type'              => 'computed',
                'function'          => 'calcEmail',
                'result_type'       => 'string',
                'usage'             => 'email',
                'description'       => 'Email of the contact (from Identity).'
            ],

            // #memo - phone remains related to identity
            'phone' => [
                'type'              => 'computed',
                'function'          => 'calcPhone',
                'result_type'       => 'string',
                'usage'             => 'phone',
                'description'       => 'Phone number of the contact (from Identity).'
            ],

            // #memo - mobile remains related to identity
            'mobile' => [
                'type'              => 'computed',
                'function'          => 'calcMobile',
                'result_type'       => 'string',
                'usage'             => 'phone',
                'description'       => 'Mobile phone number of the contact (from Identity).'
            ],

            'title' => [
                'type'              => 'computed',
                'function'          => 'calcTitle',
                'result_type'       => 'string',
                'description'       => 'Title of the contact (from Identity).'
                // #memo - title origin remains the related identity
            ],

            'lang_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'core\Lang',
                'description'       => "Preferred language of the partner (relates to identity).",
                'default'           => 1
            ],

            'is_active' => [
                'type'              => 'boolean',
                'description'       => 'Mark the partner as active.',
                'default'           => true
            ]
        ];
    }

    public function getUnique() {
        return [
            ['owner_identity_id', 'partner_identity_id', 'relationship']
        ];
    }

    public static function onupdatePartnerIdentityId($om, $oids, $values, $lang) {
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

    public static function calcName($om, $oids, $lang) {
        $result = [];
        $partners = $om->read(self::getType(), $oids, ['partner_identity_id.name'], $lang);
        foreach($partners as $oid => $partner) {
            if(isset($partner['partner_identity_id.name'])) {
                $result[$oid] = $partner['partner_identity_id.name'];
            }
        }
        return $result;
    }

    public static function calcEmail($om, $oids, $lang) {
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

    public static function calcPhone($om, $oids, $lang) {
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

    public static function calcMobile($om, $oids, $lang) {
        $result = [];
        $partners = $om->read(get_called_class(), $oids, ['partner_identity_id.mobile'], $lang);
        foreach($partners as $oid => $partner) {
            $result[$oid] = '';
            if(isset($partner['partner_identity_id.mobile'])) {
                $result[$oid] = $partner['partner_identity_id.mobile'];
            }
        }
        return $result;
    }

    public static function calcTitle($om, $oids, $lang) {
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

    /**
     * Signature for single object change from views.
     *
     * @param  Object   $om        Object Manager instance.
     * @param  Array    $event     Associative array holding changed fields as keys, and their related new values.
     * @param  Array    $values    Copy of the current (partial) state of the object (fields depend on the view).
     * @param  String   $lang      Language (char 2) in which multilang field are to be processed.
     * @return Array    Associative array mapping fields with their resulting values.
     */
    public static function onchange($om, $event, $values, $lang='en') {
        $result = [];

        if(isset($event['partner_identity_id'])) {
            $identities = $om->read('identity\Identity', $event['partner_identity_id'], ['name']);
            if($identities > 0) {
                $identity = reset($identities);
                $result['name'] = $identity['name'];
            }
        }

        return $result;
    }
}