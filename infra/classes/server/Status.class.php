<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2026
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

namespace infra\server;

use equal\orm\Model;

class Status extends Model {

    public static function getColumns(): array {
        return [

            'object_class' => [
                'type'              => 'string',
                'usage'             => 'text/plain:128',
                'description'       => "Class of entity this entry is related to.",
                'required'          => true,
                'readonly'          => true
            ],

            'object_id' => [
                'type'              => 'integer',
                'description'       => "Identifier of the targeted object (of given class).",
                'required'          => true,
                'readonly'          => true
            ],

            'status_data' => [
                'type'              => 'string',
                'usage'             => 'text/json',
                'description'       => "JSON representation of server/instance statuses and statistics.",
                'readonly'          => true
            ],

            'is_collection_ok' => [
                'type'              => 'boolean',
                'description'       => "Was the status collection process a success ?",
                'default'           => true,
                'readonly'          => true
            ]

        ];
    }

    public static function getConstraints() {
        return [
            'object_class' =>  [
                'invalid_object_class' => [
                    'message'   => 'Object class must be infra\server\Server or infra\server\Instance.',
                    'function'  => function ($object_class, $values) {
                        return in_array($object_class, [Server::class, Instance::class]);
                    }
                ]
            ]
        ];
    }
}
