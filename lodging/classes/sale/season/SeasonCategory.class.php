<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace lodging\sale\season;


class SeasonCategory extends \sale\season\SeasonCategory {
    public static function getColumns() {
        /**
         */

        return [
            'centers_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'lodging\identity\Center',
                'foreign_field'     => 'season_category_id',
                'description'       => 'List of centers assigned to this category of seasons.'
            ]
            
        ];
    }
}