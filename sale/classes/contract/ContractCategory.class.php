<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace sale\contract;
use equal\orm\Model;

class ContractCategory extends Model {

    public static function getColumns() {

        return [

            'name' => [
                'type'              => 'string',
                'required'          => true
            ],

            'description' => [
                'type'              => 'string'
            ],

            'is_active' => [
                'type'              => 'boolean',
                'default'           => true
            ]

        ];
    }

}
