<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace identity;
use equal\orm\Model;

class Partner extends Model {

    public static function getName() {
        return 'Partner';
    }
    
    public static function getColumns() {
        return [

            'name' => [
                'type'              => 'computed',
                'function'          => 'identity\Partner::getDisplayName',
                'result_type'       => 'string',
                'store'             => true,
                'description'       => 'The display name of the partner (related organisation name).'
            ],

            'owner_identity_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'identity\Identity',
                'domain'            => ['type', '<>', 'I'],                
                'description'       => 'The organisation which the targeted identity is a partner of.'
            ],

            'partner_identity_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'identity\Identity',
                'description'       => 'The targeted identity (the partner).' 
            ],

            'partner_position' => [
                'type'              => 'string',
                'description'       => 'Position of the reference contact (natural person) within the organisation (legal person), e.g. \'director\', \'CEO\', \'Regional manager\'.',
                'visible'           => [ ['type', '<>', 'I'] ]
            ],

            'relationship' => [
                'type'              => 'string',
                'selection'         => [ 'contact', 'employee', 'customer', 'provider', 'payer', 'other' ],
                'description'       => 'The kind of partnership that exists between the identities.' 
            ],

        ];
    }

    public static function getDisplayName($om, $oids, $lang) {
        $result = [];
        $employees = $om->read(__CLASS__, $oids, ['organisation_id.name']);
        foreach($employees as $oid => $odata) {
            $result[$oid] = $odata['organisation_id.name'];
        }
        return $result;              
    }    
}