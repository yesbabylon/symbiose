<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace talentlead\identity;

use equal\orm\Model;

class Prospect extends Model {

    public static function getColumns() {
        return [

            'campaign_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'talentlead\Campaign',
                'description'       => "Campaign associated to the Prospect."
            ],

            'talent_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'talentlead\Talent',
                'description'       => "Talent associated to the prospect."
            ]

        ];
    }

}