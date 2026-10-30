<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2026
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

namespace hr\role;

use equal\orm\Model;

class RoleGrant extends Model {

    public static function getName() {
        return 'Role permission';
    }

    public static function getDescription() {
        return 'A role permission grants access to a workflow transition for a model.';
    }

    public static function getColumns(): array {
        return [

            'role_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'hr\role\Role',
                'description'       => 'Role to which the permission applies.',
                'ondelete'          => 'cascade',
                'required'          => true
            ],

            'class_name' => [
                'type'              => 'string',
                'description'       => 'Fully qualified name of the targeted model.',
                'required'          => true
            ],

            'transition' => [
                'type'              => 'string',
                'description'       => 'Workflow transition to which the permission applies.'
            ]

        ];
    }

    public static function onchange($event, $values, $view): array {
        $result = [];

        if(isset($event['class_name']) && class_exists($event['class_name'])) {
            $workflow = $event['class_name']::getWorkflow();
            $transitions = [];
            if(is_array($workflow)) {
                foreach($workflow as $definition) {
                    foreach($definition['transitions'] ?? [] as $transition => $descriptor) {
                        $transitions[$transition] = true;
                    }
                }
            }
            $result['transition']['selection'] = array_keys($transitions);
        }

        return $result;
    }
}
