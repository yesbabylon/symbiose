<?php
/*
    This file is part of the eQual framework <http://www.github.com/cedricfrancoys/equal>
    Some Rights Reserved, Cedric Francoys, 2010-2021
    Licensed under GNU GPL 3 license <http://www.gnu.org/licenses/>
*/
namespace infra;

use equal\orm\Model;

class Instance extends Model {

    public static function getColumns() {
        return [
            'version' => [
                'type'              => 'string',
                'description'       => 'Version of the instance.',
                'selection'         => ['production', 'preview', 'staging', 'development']
            ],
            'description' => [
                'type'              => 'string',
                'description'       => 'Informations about an instance.',
                'multilang'         => true
            ],
            'branch' => [
                'type'              => 'string',
                'description'       => 'Branch on which the instance resides.'
            ],
            'access_ids' => [                
                'type'              => 'one2many',
                'foreign_object'    => 'infra\Access', 
                'foreign_field'     => 'instance_id', 
                'description'       => 'Access informations to the server.'
            ],
            'server_id' => [
                'type'              => 'many2one', 
                'foreign_object'    => 'infra\Server', 
                'description'       => 'Server to which the instance belongs.'
            ],
        ];
    }

}