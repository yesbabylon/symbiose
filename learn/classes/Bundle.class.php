<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace learn;

use equal\orm\Model;

class Bundle extends Model {

    public static function getColumns(): array
    {
        return [
            'name' => [
                'type'              => 'string',
                'multilang'         => true
            ],

            'description' => [
                'type'              => 'string',
                'usage'             => 'text/plain',
                'multilang'         => true
            ],

            'attachments_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'learn\BundleAttachment',
                'foreign_field'     => 'bundle_id',
                'ondetach'          => 'delete'
            ],

            'course_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'learn\Course',
                'description'       => 'Course the attachment relates to.',
                'ondelete'          => 'cascade'         // delete module when parent course is deleted
            ]
        ];
    }

}