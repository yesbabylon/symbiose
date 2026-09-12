<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2024
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

namespace infra\server;

use equal\orm\Model;

class Status extends Model {

    public static function getFlags(): int {
        return EQ_FLAG_ABSTRACT;
    }

    public static function getColumns(): array {
        return [

            'server_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'infra\server\Server',
                'ondelete'          => 'cascade',
                'description'       => "Server concerned by the status.",
                'help'              => "A status can either concern a server or an instance."
            ],

            'instance_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'infra\server\Instance',
                'ondelete'          => 'cascade',
                'description'       => "Instance concerned by the status.",
                'help'              => "A status can either concern an instance or a server."
            ],
            /*
            // #todo - define possible values with related metric units
                units
                -----
                percent
                amount/unit
                amount/G
                kbs
                bool
                string
             */
            'status_data' => [
                'type'              => 'string',
                'usage'             => 'text/json',
                'description'       => "JSON representation of server/instance statuses and statistics."
            ],

            // instant values

            'dsk_use' => [
                'type'              => 'float',
                'usage'             => 'amount/percent',
                'description'       => "Instant percentage of storage drive being used."
            ],

            'cpu_use' => [
                'type'              => 'float',
                'usage'             => 'amount/percent',
                'description'       => "Instant percentage of CPU being used."
            ],

            'ram_use' => [
                'type'              => 'float',
                'usage'             => 'amount/percent',
                'description'       => "Instant percentage of Memory being used."
            ],

            'total_proc' => [
                'type'              => 'integer',
                'description'       => "Amount of currently runnin processes."
            ]

        ];
    }

    public static function cancreate($self, $values) {
        if($error = self::validateTarget($values)) {
            return $error;
        }

        return parent::cancreate($self, $values);
    }

    public static function canupdate($self, $values) {
        $self->read(['server_id', 'instance_id']);
        foreach($self as $status) {
            $targets = array_merge(
                [
                    'server_id'   => $status['server_id'],
                    'instance_id' => $status['instance_id']
                ],
                $values
            );
            if($error = self::validateTarget($targets)) {
                return $error;
            }
        }

        return parent::canupdate($self, $values);
    }

    private static function validateTarget(array $values): array {
        $has_server = !empty($values['server_id']);
        $has_instance = !empty($values['instance_id']);

        if($has_server === $has_instance) {
            return ['server_id' => ['invalid_target' => 'Exactly one server or instance is required.']];
        }
        if(is_a(static::class, ServerStatus::class, true) && !$has_server) {
            return ['server_id' => ['incompatible_target' => 'A server status requires a server.']];
        }
        if(is_a(static::class, InstanceStatus::class, true) && !$has_instance) {
            return ['instance_id' => ['incompatible_target' => 'An instance status requires an instance.']];
        }

        return [];
    }
}
