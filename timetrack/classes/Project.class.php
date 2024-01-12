<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

namespace timetrack;

use equal\orm\Model;

class Project extends Model {

    public static function getColumns(): array {
        return [

            'name' => [
                'type'           => 'string',
                'description'    => 'Name of the project.',
                'required'       => true,
                'unique'         => true
            ],

            'description' => [
                'type'           => 'string',
                'description'    => 'Description of the project.'
            ],

            'customer_id' => [
                'type'           => 'many2one',
                'foreign_object' => 'sale\customer\Customer',
                'description'    => 'Which customer is the project for.'
            ],

            'instance_id' => [
                'type'           => 'many2one',
                'foreign_object' => 'inventory\server\Instance',
                'description'    => 'The instance hosting the project.'
            ]

        ];
    }
}
