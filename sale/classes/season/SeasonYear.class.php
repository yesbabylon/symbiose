<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace sale\season;
use equal\orm\Model;

class SeasonYear extends Model {

    public static function getColumns() {

        return [

            'name' => [
                'type'              => 'alias',
                'alias'             => "year"
            ],

            'year' => [
                'type'              => 'integer',
                'usage'             => 'date/year:4',
                'description'       => "Year the season applies to.",
                'required'          => true
            ],

            
        ];
    }

}