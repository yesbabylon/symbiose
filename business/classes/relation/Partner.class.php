<?php
namespace business\relation;
use equal\orm\Model;

class Partner extends Model {

    public static function getName() {
        return 'Partner';
    }
    
    public static function getColumns() {
        return [

            'name' => [
                'type'              => 'computed',
                'function'          => 'business\relation\Partner::getDisplayName',
                'result_type'       => 'string',
                'store'             => true,
                'description'       => 'The display name of the partner (related organisation name).'
            ],

            'organisation_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'identity\Organisation',
                'description'       => 'The targeted organisation (the partner).' 
            ],

            'relation' => [
                'type'              => 'string',
                'selection'         => [ 'invoice', 'delivery', 'other' ],
                'description'       => 'The kind of partnership that exists between the organisations.' 
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