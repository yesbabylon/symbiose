<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace identity;

class User extends \core\User {

    public static function getName() {
        return 'User';
    }

    public static function getColumns() {
        return [

            'identity_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'identity\Identity',
                'domain'            => ['type', '=', 'IN'],
                'description'       => 'The identity the user relates to.',
                'help'              => 'The identity whose firstname, lastname and language are synchronized with this user.',
                'dependents'        => ['name']
            ],

            'setting_values_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'core\setting\SettingValue',
                'foreign_field'     => 'user_id',
                'description'       => 'List of settings that relate to the user.'
            ],

            'organization_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'identity\Organization',
                'description'       => 'The organization the user belongs to.',
                'default'           => 1
            ]

        ];
    }

    public static function getActions() {
        return array_merge(parent::getActions(), [
            'sync_from_identity' => [
                'description'   => 'Force sync values from related identity.',
                'function'      => 'doSyncFromIdentity'
            ]
        ]);
    }

    protected static function onafterupdate($self, $values) {
        $identity_values = [];
        foreach(['firstname', 'lastname'] as $field) {
            if(array_key_exists($field, $values)) {
                $identity_values[$field] = $values[$field];
            }
        }
        if(array_key_exists('language', $values)) {
            if(!$values['language']) {
                $identity_values['lang_id'] = null;
            }
            else {
                $lang = \core\Lang::search([['code', '=', $values['language']]])->first();
                if($lang) {
                    $identity_values['lang_id'] = $lang['id'];
                }
            }
        }

        $self->read(['identity_id' => ['id', 'user_id']]);
        foreach($self as $id => $user) {
            if(!isset($user['identity_id']['id'])) {
                continue;
            }
            if($identity_values) {
                Identity::id($user['identity_id']['id'])->update($identity_values);
            }
            if(($user['identity_id']['user_id'] ?? null) !== $id) {
                Identity::id($user['identity_id']['id'])->update(['user_id' => $id]);
            }
        }
    }

    protected static function onafterinstantiate($self) {
        $self->read(['identity_id']);
        foreach($self as $id => $user) {
            if($user['identity_id']) {
                Identity::id($user['identity_id'])->update(['user_id' => $id]);
            }
        }
    }

    protected static function doSyncFromIdentity($self, $orm) {
        $self->read(['identity_id']);
        foreach($self as $id => $user) {
            if(!$user['identity_id']) {
                continue;
            }

            $identity = Identity::id($user['identity_id'])
                ->read(['firstname', 'lastname', 'lang_id' => ['code']])
                ->first(true);

            if(!$identity) {
                continue;
            }

            try {
                $orm_events = $orm->disableEvents();
                static::id($id)->update([
                    'firstname' => $identity['firstname'] ?? null,
                    'lastname'  => $identity['lastname'] ?? null,
                    'language'  => $identity['lang_id']['code'] ?? null
                ]);
            }
            finally {
                $orm->enableEvents($orm_events);
            }
        }
    }

}
