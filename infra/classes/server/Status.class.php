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

            'status_data_excerpt' => [
                'type'              => 'computed',
                'result_type'       => 'string',
                'description'       => "Excerpt of the Message (for display).",
                'store'             => true,
                'function'          => 'calcStatusDataExcerpt',
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

    protected static function calcStatusDataExcerpt($self) {
        $result = [];
        $self->read(['status_data']);
        foreach($self as $id => $status) {
            $status_data = json_decode($status['status_data'], true);

            $data = [];
            if(isset($status_data['state']['up'])) {
                $data['up'] = $status_data['state']['up'];
            }
            if(isset($status_data['instant']['ram_use'])) {
                $data['ram_use'] = $status_data['instant']['ram_use'];
            }
            if(isset($status_data['instant']['cpu_use'])) {
                $data['cpu_use'] = $status_data['instant']['cpu_use'];
            }
            if(isset($status_data['instant']['dsk_use'])) {
                $data['dsk_use'] = $status_data['instant']['dsk_use'];
            }
            if(isset($status_data['instant']['total_proc'])) {
                $data['total_proc'] = $status_data['instant']['total_proc'];
            }

            $excerpt = strip_tags(json_encode($data));
            if(strlen($excerpt) > 100) {
                $excerpt = substr(strip_tags(json_encode($data)), 0, 100) . '...';
            }

            $result[$id] = $excerpt;
        }

        return $result;
    }
}
