<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace sale\autosale;
use equal\orm\Model;

class Condition extends Model {

    public static function getColumns() {

        return [
            'name' => [
                'type'              => 'string',
                'required'          => true
            ],

            'operand' => [
                'type'              => 'string',
                'required'          => true
            ],

            'operator' => [
                'type'              => 'string',
                'required'          => true
            ],

            'value' => [
                'type'              => 'string',
                'required'          => true
            ],

            'autosale_line_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\autosale\AutosaleLine',
                'description'       => 'The autosale line the condition belongs to.',
                'required'          => true
            ]

        ];
    }

}