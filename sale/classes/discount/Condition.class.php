<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace sale\discount;
use equal\orm\Model;

class Condition extends Model {

    public static function getColumns() {

        return [
            'name' => [
                'type'              => 'string',
                'result_type'       => 'computed',
                'function'          => 'sale\discount\Condition::getDisplayName',
                'store'             => true,
                'description'       => 'Resulting display name of the condition.'
            ],

            'operand' => [
                'type'              => 'string',
                'selection'         => [
                                            'season', 
                                            'nb_pers', 
                                            'duration', 
                                            'count_booking_24'
                                       ],
                'required'          => true
            ],

            'operator' => [
                'type'              => 'string',
                'selection'         => ['=', '>', '>=', '<', '<='],
                'required'          => true
            ],

            'value' => [
                'type'              => 'string',
                'required'          => true
            ],

            'discount_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\discount\Discount',
                'description'       => 'The discount list the discount belongs to.',
                'required'          => true
            ],


        ];
    }

    public static function getDisplayName($om, $oids, $lang) {
        $result = [];
        $res = $om->read(__CLASS__, $oids, ['operand', 'operator', 'value']);
        foreach($res as $oid => $odata) {
            $result[$oid] = "{$odata['operand']} {$odata['operator']} {$odata['value']}";
        }
        return $result;              
    }


}