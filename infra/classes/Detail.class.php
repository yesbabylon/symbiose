<?php
/*
    This file is part of the eQual framework <http://www.github.com/cedricfrancoys/equal>
    Some Rights Reserved, Cedric Francoys, 2010-2021
    Licensed under GNU GPL 3 license <http://www.gnu.org/licenses/>
*/
namespace infra;

use equal\orm\Model;

class Detail extends Model {

    public static function getColumns() {
        return [
            'label' => [
                'type'              => 'string',
                'unique'            => true
            ],
            'description' => [
                'type'              => 'string',
                'description'       => 'Short presentation of the detail element.',
                'multilang'         => true
            ],
            'value' => [                
                'type'              => 'string',
                'description'       => 'Server attached to the product.',
                'multilang'         => true
            // est-ce correct ? Et je sais que potentiellement il y aura encore une table de transition.
            ],
            'service_id' => [
                'type'              => 'many2one', 
                'foreign_object'    => 'infra\Service', 
                'description'       => 'Details attached to a service.'
            ]
        ];
    }

}