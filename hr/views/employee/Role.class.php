<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace hr\employee;

class Role extends \equal\orm\Model {

    public static function getName() {
        return 'Role';
    }

    public static function getDescription() {
        return "A role relates to a Job Title and describes a set of specific tasks that are assigned to an employee.";
    }

    public static function getColumns() {

        return [

            'description' => [
                'type'              => 'string',
                'description'       => 'Details about the role.'
            ]

        ];
    }

}