<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2024
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

namespace inventory;

use equal\orm\Model;

class SoftwareModel extends Model {

    public static function getColumns() {
        return [

            'name' => [
                'type'              => 'string',
                'description'       => "Name of the software model.",
                'required'          => true
            ],

            'description' => [
                'type'              => 'string',
                'usage'             => 'text/plain',
                'description'       => 'Information about a software model.'
            ],

            'edition' => [
                'type'              => 'string',
                'description'       => "Type of edition (CE/EE/Pro/...)."
            ],

            'version' => [
                'type'              => 'string',
                'description'       => "Installed version of the software model."
            ]

        ];
    }
}
