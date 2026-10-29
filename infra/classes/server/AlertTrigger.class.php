<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2024
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

namespace infra\server;

use equal\orm\Model;

class AlertTrigger extends Model {

    const MAP_STATUS_KEYS_TYPES = [
        'common' => [
            'state.up'                  => 'boolean',
            'instant.total_proc'        => 'integer',
            'instant.ram_use'           => 'percentage',
            'instant.cpu_use'           => 'percentage',
            'instant.dsk_use'           => 'percentage',
        ],

        'b2' => [
            'instant.mysql_mem'         => 'percentage',
            'instant.apache_mem'        => 'percentage',
            'instant.nginx_mem'         => 'percentage',
            'instant.apache_proc'       => 'integer',
            'instant.nginx_proc'        => 'integer',
            'instant.mysql_proc'        => 'integer',
        ],

        'b2_instance' => [
            'state.maintenance'         => 'boolean',
        ],

        'k2' => [
            'instant.backup_tokens_qty' => 'integer',
            'instant.backups_disk'      => 'percentage'
        ]
    ];

    public static function getColumns(): array {
        return [

            'object_class' => [
                'type'              => 'string',
                'usage'             => 'text/plain:128',
                'description'       => "Class of entity this entry is related to.",
                'help'              => "Is a template if left empty.",
                'readonly'          => true
            ],

            'object_id' => [
                'type'              => 'integer',
                'description'       => "Identifier of the targeted object (of given class).",
                'help'              => "Is a template if left empty.",
                'readonly'          => true
            ],

            'repetition' => [
                'type'              => 'integer',
                'description'       => "Number of repetitions needed for the alert to be triggered.",
                'help'              => "If repetition is 2, then the trigger must match twice in a row.",
                'default'           => 1
            ],

            'checks_ids' => [
                'type'              => 'many2many',
                'foreign_object'    => 'infra\server\Check',
                'foreign_field'     => 'alert_triggers_ids',
                'rel_table'         => 'infra_check_rel_alert_trigger',
                'rel_foreign_key'   => 'check_id',
                'rel_local_key'     => 'alert_trigger_id',
                'description'       => "Checks used to trigger the alert."
            ]

        ];
    }

    public static function getConstraints(): array {
        return [
            'repetition' => [
                'repetition_min_is_one' => [
                    'message'       => 'Min repetition is 1',
                    'function'      => function($repetition, $values) {
                        return $repetition > 0;
                    }
                ]
            ]
        ];
    }
}
