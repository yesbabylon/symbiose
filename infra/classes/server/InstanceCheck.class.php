<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2026
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

namespace infra\server;

use equal\orm\Model;

class InstanceCheck extends Model {

    public static function getColumns() {
        return [

            'name' => [
                'type'              => 'string',
                'usage'             => 'text/plain:128',
                'description'       => 'Unique technical name of the instance check.',
                'required'          => true
            ],

            'description' => [
                'type'              => 'string',
                'usage'             => 'text/plain',
                'description'       => 'Functional description of what is checked.'
            ],

            'instance_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'infra\server\Instance',
                'description'       => 'Instance concerned by the check.',
                'required'          => true,
                'ondelete'          => 'cascade'
            ],

            'value' => [
                'type'              => 'boolean',
                'description'       => 'Current boolean value of the instance check.',
                'default'           => false
            ]

        ];
    }

    public function getUnique() {
        return [
            ['instance_id', 'name']
        ];
    }
}
