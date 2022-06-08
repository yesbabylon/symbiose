<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace lodging\identity;

class Identity extends \identity\Identity {

    public static function getColumns() {
        return [
            // Any Identity can have several contacts
            'contacts_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'lodging\sale\booking\Contact',
                'foreign_field'     => 'owner_identity_id',
                'domain'            => ['partner_identity_id', '<>', 'object.id'],
                'description'       => 'List of contacts related to the organisation (not necessarily employees), if any.'
            ],

            'lang_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'core\Lang',
                'description'       => "Preferred language of the identity.",
                'default'           => 2,
                'onupdate'          => 'identity\Identity::onupdateLangId'
            ]
        ];
    }

}