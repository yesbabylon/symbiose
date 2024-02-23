<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace qursus;

use equal\orm\Model;

class Quiz extends Model {

    public static function getColumns() {
        return [
            'identifier' => [
                'type'              => 'integer',
                'description'       => 'Unique Id of the quiz within the pack.'
            ],

            'name' => [
                'type'              => 'string',
                'multilang'         => true,
                'default'           => 'Quiz'
            ],

            'quiz_code' => [
                'type'              => 'integer',
                'multilang'         => true
            ],

            'course_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'qursus\Course',
                'description'       => 'Course the attachment relates to.',
                'ondelete'          => 'cascade'         // delete module when parent pack is deleted
            ]
        ];
    }

}