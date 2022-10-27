<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace talentlead\campaign;

use equal\orm\Model;

class CriteriaChoice extends Model {

    public static function getColumns() {
        return [

            'name' => [
                'type'              => 'string',
                'description'       => "Name of the Criteria Choice."
            ],

            'value' => [
                'type'              => 'string',
                'description'       => 'Value of the Customer Choice.'
            ],

            'criteria_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'talentlead\Criteria',
                'description'       => "Criteria to which the choices are related."
            ],

        ];
    }

}