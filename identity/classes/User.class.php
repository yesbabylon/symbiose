<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace identity;

class User extends \core\User {

    public static function getName() {
        return 'User';
    }
    
    public static function getColumns() {
        return [

            'name' => [
                'type'              => 'computed',
                'function'          => 'identity\User::getDisplayName',
                'result_type'       => 'string',
                'store'             => true,
                'description'       => 'The display name of the user.'
            ],

            'identity_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'identity\Identity',
                'domain'            => ['type', '=', 'I'],                
                'description'       => 'The contact related to the user.',
                'onchange'          => 'identity\User::onchangeIdentity'
            ]

        ];
    }

    public static function getDisplayName($om, $oids, $lang) {
        $result = [];
        $users = $om->read(__CLASS__, $oids, ['login', 'identity_id.name']);
        foreach($users as $oid => $odata) {
            if(isset($odata['identity_id.name']) && strlen($odata['identity_id.name']) ) {
                $result[$oid] = $odata['identity_id.name'];
            }
            else {
                $result[$oid] = $odata['login'];
            }            
        }
        return $result;              
    }


    public static function onchangeIdentity($om, $oids, $lang) {
        // force re-compute the name
        $om->write(__CLASS__, $oids, ['name' =>  null], $lang);
    }    
}