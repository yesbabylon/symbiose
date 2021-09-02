<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace sale\season;
use equal\orm\Model;

class SeasonType extends Model {

    public static function getColumns() {

        return [

            'name' => [
                'type'              => 'string',
                'description'       => "Short code of the type."
            ],

            'description' => [
                'type'              => 'string',
                'description'       => "Explanation on when to use the type and its specifics.",
                'default'           => '',
                'multilang'         => true
            ]            
            
        ];
    }

}