<?php
/*
    This file is part of the eQual framework <http://www.github.com/cedricfrancoys/equal>
    Some Rights Reserved, Cedric Francoys, 2010-2021
    Licensed under GNU GPL 3 license <http://www.gnu.org/licenses/>
*/
namespace documents;

class DocumentRoleAssignment extends \core\Assignment {

    public static function getColumns() {
        return [
            'object_class' => [
                'type'              => 'string',
                'description'       => 'Full name of the entity on which the role assignment applies.',
                'required'          => true,
                'default'           => 'documents\Document'
            ],

            'role' => [
                'type' 	            => 'string',
                'usage'             => 'orm/role.documents_Document',
                'description'       => "Role that is assigned to the user.",
                'help'              => "The assigned Role should match one of the roles defined at the entity level and returned by the `getRole()` method."
            ]
        ];
    }

}
