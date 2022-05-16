<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace lodging\sale\season;


class Season extends \sale\season\Season {

    public static function getColumns() {

        return [

            'center_category_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'lodging\identity\CenterCategory',
                'description'       => "Center category targeted by season.",
                'required'          => true
            ]
            
        ];
    }
}