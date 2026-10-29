<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2026
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

namespace infra\server;

use equal\orm\Model;

class Check extends Model {

    public static function getColumns(): array {
        return [

            'code' => [
                'type'              => 'string',
                'description'       => 'Code of the check.',
                'unique'            => true,
                'required'          => true
            ],

            'name' => [
                'type'              => 'string',
                'description'       => 'Name of the check.',
                'function'          => 'calcName',
                'store'             => true
            ],

            'description' => [
                'type'              => 'string',
                'description'       => "Short description of check."
            ],

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

            'status_path' => [
                'type'              => 'string',
                'description'       => 'The path of the status_data used for the check.',
                'required'          => true,
                'dependents'        => ['name']
            ],

            'operator' => [
                'type'              => 'string',
                'description'       => "Operator used for the check.",
                'selection'         => ['eq', 'ne', 'gt', 'gte', 'lt', 'lte', 'contains', 'does_not_contain', 'in', 'not_in'],
                'default'           => 'eq',
                'required'          => true,
                'dependents'        => ['name']
            ],

            'check_value' => [
                'type'              => 'string',
                'description'       => "Value used for the check.",
                'required'          => true,
                'dependents'        => ['name']
            ],

            'value' => [
                'type'              => 'string',
                'description'       => "Current value, set by the last check.",
                'required'          => true,
                'dependents'        => ['name']
            ],

            'is_success' => [
                'type'              => 'boolean',
                'description'       => "Current states of success, set by the last check."
            ],

            'failure_count' => [
                'type'              => 'integer',
                'description'       => "The quantity of failures of the check.",
                'default'           => 0
            ],

            'alert_triggers_ids' => [
                'type'              => 'many2many',
                'foreign_object'    => 'infra\server\AlertTrigger',
                'foreign_field'     => 'checks_ids',
                'rel_table'         => 'infra_check_rel_alert_trigger',
                'rel_foreign_key'   => 'alert_trigger_id',
                'rel_local_key'     => 'check_id',
                'description'       => "Trigger alerts that uses the check."
            ]

        ];
    }

    public static function calcName($self): array {
        $result = [];
        $self->read(['status_path', 'operator', 'check_value']);
        foreach($self as $id => $trigger) {
            $result[$id] = "{$trigger['status_path']} {$trigger['operator']} {$trigger['check_value']}";
        }

        return $result;
    }
}
