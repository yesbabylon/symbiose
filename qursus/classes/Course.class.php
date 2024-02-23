<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace qursus;

use equal\orm\Model;

class Course extends Model {

    public static function getColumns() {
        return [
            'name' => [
                'type'              => 'string',
                'description'       => 'Unique slug of the program.'
            ],

            'title' => [
                'type'              => 'string',
                'multilang'         => true
            ],

            'subtitle' => [
                'type'              => 'string',
                'multilang'         => true
            ],

            'description' => [
                'type'              => 'string',
                'usage'             => 'text/plain',
                'multilang'         => true
            ],

            'modules' => [
                'type'              => 'alias',
                'alias'             => 'modules_ids'
            ],

            'modules_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'qursus\Module',
                'foreign_field'     => 'pack_id',
                'ondetach'          => 'delete'
            ],

            'quizzes_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'qursus\Quiz',
                'foreign_field'     => 'pack_id',
                'ondetach'          => 'delete'
            ],

            'bundles_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'qursus\Bundle',
                'foreign_field'     => 'pack_id',
                'ondetach'          => 'delete'
            ],

            'langs_ids' => [
                'type'              => 'many2many',
                'foreign_object'    => 'qursus\Lang',
                'foreign_field'     => 'packs_ids',
                'rel_table'         => 'qursus_rel_lang_pack',
                'rel_foreign_key'   => 'lang_id',
                'rel_local_key'     => 'pack_id',
                'description'       => "List of languages in which the program is available"
            ]

        ];
    }

}