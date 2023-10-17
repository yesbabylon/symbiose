<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace qursus;

use equal\orm\Model;

class Lang extends Model {

    public static function getColumns() {
        return [
            'name' => [            
                'type'              => 'string',
                'description'       => "Full name of language, in english."
            ],

            'code' => [
                'type'              => 'string',
                'description'       => "ISO 639-1 language code."
            ],

            'packs_ids' => [ 
                'type'              => 'many2many', 
                'foreign_object'    => 'qursus\Pack', 
                'foreign_field'     => 'langs_ids', 
                'rel_table'         => 'qursus_rel_lang_pack', 
                'rel_foreign_key'   => 'pack_id',
                'rel_local_key'     => 'lang_id'
            ]

        ];
    }

}