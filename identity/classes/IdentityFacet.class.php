<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2026
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace identity;

use equal\orm\Model;

/**
 * Common schema for a canonical identity and its role projections.
 */
abstract class IdentityFacet extends IdentityAbstract {


    public static function getColumns() {
        return [
            'identity_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'identity\Identity',
                'description'       => 'Identity the object relates to.',
                'help'              => 'Meant for entities that inherit from `identity\Identity` and must be synced with parent Identity.
                    Classes that inherit from `identity\IdentityAbstract` must implement `onupdateIdentityId()` method.',
                'onupdate'          => 'onupdateIdentityId'
            ],
        ];
    }

    /**
     * Stored projections that must be invalidated after a silent synchronization.
     */
    protected static function getIdentityProjectionFields() {
        return ['name', 'type', 'address'];
    }

    public static function getActions() {
        return [
            'sync_from_identity' => [
                'description' => 'Replace all common role values with the canonical Identity values.',
                'policies'    => [],
                'function'    => 'doSyncFromIdentity'
            ]
        ];
    }


    protected static function doSyncFromIdentity($self, $orm) {
        $self->read(['identity_id']);
        $facet_class = static::class;
        foreach($self as $id => $facet) {
            // trigger_error("APP::checking identity " . $object['identity_id'], EQ_REPORT_ERROR);
            if(!$facet['identity_id']) {
                continue;
            }

            // trigger_error("APP::loading parent", EQ_REPORT_ERROR);
            $parent_identity = Identity::id($facet['identity_id'])
                ->read(self::COMMON_IDENTITY_FIELDS)
                ->first(true);

            if(!$parent_identity) {
                continue;
            }

            $values = [];
            foreach(self::COMMON_IDENTITY_FIELDS as $field) {
                if(array_key_exists($field, $parent_identity)) {
                    $values[$field] = $parent_identity[$field];
                }
            }
            try {
                $orm_events = $orm->disableEvents();
                // trigger_error("APP::updating values to target object", EQ_REPORT_ERROR);
                $facet_class::id($id)->update($values);
            }
            finally {
                $orm->enableEvents($orm_events);
            }

            // trigger_error("APP::updating backlink ref to identity", EQ_REPORT_ERROR);
            // force sync backlink from target Identity
            $facet_class::id($id)->update(['identity_id' => $facet['identity_id']]);
        }
    }

    protected static function onupdateIdentityId($self) {
        $self->read(['identity_id']);
        $facet_field = null;
        foreach(self::MAP_FIELDS_FACETS as $field => $descriptor) {
            if($descriptor['class'] === static::class) {
                $facet_field = $field;
                break;
            }
        }
        foreach($self as $id => $facet) {
            if($facet['identity_id']) {
                Identity::id($facet['identity_id'])->update([$facet_field => $id]);
            }
        }
    }

    protected static function onafterinstantiate($self, $values, $orm) {
        $self->read(array_merge(self::COMMON_IDENTITY_FIELDS, ['identity_id']));

        foreach($self as $id => $facet) {

            if(!$facet['identity_id']) {
                $identity_values = [];
                foreach(self::COMMON_IDENTITY_FIELDS as $field) {
                    $identity_values[$field] = $facet[$field];
                }

                /*
                    attempt to retrieve existing identity
                */

                $identity_id = null;

                // VAT number (strong identifier)
                if(!$identity_id && !empty($identity_values['vat_number'])) {
                    $existing = Identity::search([
                        ['vat_number', '=', $identity_values['vat_number']]
                    ])
                    ->first();

                    if($existing) {
                        $identity_id = $existing['id'];
                    }
                }

                // registration number
                if(!$identity_id && !empty($identity_values['registration_number'])) {
                    $existing = Identity::search([
                        ['registration_number', '=', $identity_values['registration_number']]
                    ])
                    ->first();

                    if($existing) {
                        $identity_id = $existing['id'];
                    }
                }

                // citizen identification
                if(!$identity_id && !empty($identity_values['citizen_identification'])) {
                    $existing = Identity::search([
                        ['citizen_identification', '=', $identity_values['citizen_identification']]
                    ])
                    ->first();

                    if($existing) {
                        $identity_id = $existing['id'];
                    }
                }

                // create if none found
                if(!$identity_id) {
                    // #memo - do not use Identity::create
                    $identity_id = $orm->create(Identity::getType(), $identity_values);
                    Identity::id($identity_id)
                        ->do('refresh_bank_accounts')
                        ->do('refresh_addresses');
                }
                // #memo - classes that inherit from Identity should have a callback onupdateIdentityId (in order to assign back the right field: 'user_id', 'customer_id', 'supplier_id', 'employee_id', ...)
                $orm->update(static::class, $id, ['identity_id' => $identity_id]);
            }
            elseif(isset($values['identity_id'])) {
                $explicit_values = static::computeCommonIdentityValues($values);
                if($explicit_values) {
                    $orm->update(Identity::getType(), $facet['identity_id'], $explicit_values);
                }
            }

        }
        $self->do('sync_from_identity');
    }

    protected static function onafterupdate($self, $values, $orm) {
        $updated_values = static::computeCommonIdentityValues($values);

        if(!$updated_values) {
            return;
        }

        $self->read(array_merge(['identity_id'], array_keys($updated_values)));

        foreach($self as $id => $identityFacet) {
            if(empty($identityFacet['identity_id'])) {
                continue;
            }
            $map = [];
            foreach(array_keys($updated_values) as $field) {
                $map[$field] = $identityFacet[$field] ?? null;
            }
            // The canonical update intentionally triggers redistribution to every role.
            Identity::id($identityFacet['identity_id'])->update($map);
        }
    }

    protected static function ondelete($self) {

    }

    public static function getConstraints() {
        return [
            'legal_name' => [
                'too_short' => [
                    'message'  => 'Legal name must be minimum 2 chars long.',
                    'function' => function($legal_name, $values) {
                        return !(strlen($legal_name) < 2 && isset($values['type_id']) && $values['type_id'] != 1);
                    }
                ],
                'too_long' => [
                    'message'  => 'Legal name must be maximum 80 chars long.',
                    'function' => function($legal_name, $values) {
                        return !(strlen($legal_name) > 80 && isset($values['type_id']) && $values['type_id'] != 1);
                    }
                ],
                'invalid_chars' => [
                    'message'  => 'Legal name must contain only naming glyphs.',
                    'function' => function($legal_name, $values) {
                        if(isset($values['type_id']) && $values['type_id'] == 1) {
                            return true;
                        }
                        return (bool) preg_match('/^[\w\'\-,.&][^_!¡?÷?¿\\+=@#$%ˆ*{}|~<>;:[\]]{1,}$/u', $legal_name);
                    }
                ]
            ],
            'firstname' => [
                'too_short' => [
                    'message'  => 'Firstname must be 2 chars long at minimum.',
                    'function' => function($firstname, $values) {
                        return !(strlen($firstname) < 2 && isset($values['type_id']) && $values['type_id'] == 1);
                    }
                ],
                'invalid_chars' => [
                    'message'  => 'Firstname must contain only naming glyphs.',
                    'function' => function($firstname, $values) {
                        if(isset($values['type_id']) && $values['type_id'] != 1) {
                            return true;
                        }
                        return (bool) preg_match('/^[\w\'\-,.][^0-9_!¡?÷?¿\/\\+=@#$%ˆ&*(){}|~<>;:[\]]{1,}$/u', $firstname);
                    }
                ]
            ],
            'lastname' => [
                'too_short' => [
                    'message'  => 'Lastname must be 2 chars long at minimum.',
                    'function' => function($lastname, $values) {
                        return !(strlen($lastname) < 2 && isset($values['type_id']) && $values['type_id'] == 1);
                    }
                ],
                'invalid_chars' => [
                    'message'  => 'Lastname must contain only naming glyphs.',
                    'function' => function($lastname, $values) {
                        if(isset($values['type_id']) && $values['type_id'] != 1) {
                            return true;
                        }
                        return (bool) preg_match('/^[\w\'\-,.][^0-9_!¡?÷?¿\/\\+=@#$%ˆ&*(){}|~<>;:[\]]{1,}$/u', $lastname);
                    }
                ]
            ]
        ];
    }
}
