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

            'lang_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'core\Lang',
                'description'       => "Preferred language of the identity.",
                'default'           => 2,
                'onupdate'          => 'identity\Identity::onupdateLangId'
            ],

            // field for retrieving all partners related to the identity
            'partners_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'lodging\sale\customer\Customer',
                'foreign_field'     => 'partner_identity_id',
                'description'       => 'Partnerships that relate to the identity.',
                'domain'            => ['owner_identity_id', '<>', 'object.id']
            ]

        ];
    }

}