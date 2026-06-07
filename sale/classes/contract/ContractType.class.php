<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace sale\contract;
use equal\orm\Model;

class ContractType extends Model {

    public static function getColumns() {

        return [

            'name' => [
                'type'              => 'string',
                'required'          => true
            ],

            'code' => [
                'type'              => 'string',
                'usage'             => 'text/plain:50',
                'description'       => 'Mnemo code for identifying the contract type'
            ],

            'description' => [
                'type'              => 'string'
            ]

        ];
    }

}
