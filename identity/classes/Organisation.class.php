<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2024
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

namespace identity;

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
            ]

        ];
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
    public static function onafterupdate($self, $values) {
        $identity_fields = Identity::getColumns();
        $self->read(['identity_id']);
        $identity_values = array_intersect_key($values, $identity_fields);
        foreach($self as $id => $organisation) {
            Identity::id($organisation['identity_id'])->update($identity_values);
        }
    }
}
