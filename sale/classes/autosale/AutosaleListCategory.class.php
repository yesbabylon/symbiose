<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace sale\autosale;
use equal\orm\Model;

class AutosaleListCategory extends Model {

    public static function getColumns() {

        return [

            'name' => [
                'type'              => 'string',
                'description'       => "Name of the automatic sale category."
            ],

            'description' => [
                'type'              => 'string',
                'description'       => "Reason of the categorization of children lists.",
                'multilang'         => true
            ],

            'autosale_lists_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'sale\autosale\AutosaleList',
                'foreign_field'     => 'autosale_list_category_id',
                'description'       => 'The autosale lists that are assigned to the category.'
            ]

        ];
    }

}