<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2024
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

namespace timetrack;

use equal\orm\Model;

class Project extends Model {

    public static function getName(): string {
        return 'Project';
    }

    public static function getDescription(): string {
        return 'A project is linked to a customer and time entries.'
            .' It organizes time entries and allows to configure sale models to auto apply sale related fields of a time entry.';
    }

    public static function getColumns(): array {
        return [

            'name' => [
                'type'            => 'string',
                'description'     => 'Name of the project.',
                'required'        => true,
                'unique'          => true
            ],

            'description' => [
                'type'            => 'string',
                'description'     => 'Description of the project.'
            ],

            'customer_id' => [
                'type'            => 'many2one',
                'foreign_object'  => 'sale\customer\Customer',
                'description'     => 'Which customer is the project for.'
            ],

            'instance_id' => [
                'type'            => 'many2one',
                'foreign_object'  => 'inventory\server\Instance',
                'description'     => 'The instance hosting the project.'
            ],

            'time_entry_sale_models_ids' => [
                'type'            => 'many2many',
                'foreign_object'  => 'timetrack\TimeEntrySaleModel',
                'foreign_field'   => 'projects_ids',
                'rel_table'       => 'timetrack_project_rel_time_entry_sale_model',
                'rel_foreign_key' => 'time_entry_sale_model_id',
                'rel_local_key'   => 'project_id'
            ]

        ];
    }
}
