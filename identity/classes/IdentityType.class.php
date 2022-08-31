<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace identity;
use equal\orm\Model;

class IdentityType extends Model {

    public static function getColumns() {
        return [
            'name' => [
                'type'              => 'alias',
                'alias'             => 'description'
            ],

            'code' => [
                'type'              => 'string',
                'description'       => "Mnemonic of the identity type.",
                'required'          => true
            ],

            'description' => [
                'type'              => 'string',
                'description'       => "Short description of the identity type.",
                "multilang"         => true
            ]

        ];
    }

}