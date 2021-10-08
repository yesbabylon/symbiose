<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace sale\autosale;
use equal\orm\Model;

class AutosaleList extends Model {

    public static function getColumns() {

        return [

            'name' => [
                'type'              => 'string',
                'description'       => "Context the discount is meant to be used."
            ],

            'description' => [
                'type'              => 'string',
                'description'       => "Reason for which the discount is meant to be used."
            ],

            'date_from' => [
                'type'              => 'date',
                'description'       => "Date (included) at which the season starts.",
                'required'          => true
            ],

            'date_to' => [
                'type'              => 'date',
                'description'       => "Date (excluded) at which the season ends.",
                'required'          => true                
            ],
            
            'autosale_lines_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'sale\autosale\AutosaleLine',
                'foreign_field'     => 'autosale_list_id',
                'description'       => 'The lines that apply to the list.'
            ],

            'autosale_list_category_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\autosale\AutosaleListCategory',
                'description'       => 'The autosale category the list belongs to.',
                'required'          => true
            ]
            
        ];
    }

}