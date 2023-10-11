<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace sale\pay;
use equal\orm\Model;

class Guarantee extends Model {

    public static function getColumns() {

        return [

            'name' => [
                'type'              => 'computed',
                'result_type'       => 'string',
                'function'          => 'calcName',
                'store'             => true
            ],

            'mode' => [
                'type'              => 'string',
                'description'       => 'The kind of guarantee (payment mode).',
                'selection'         => [
                    'card'
                ],
                'default'           => 'card'
            ],

            'holder' => [
                'type'              => 'string',
                'description'       => "Name or reference given to the guarantee."
            ],

            'card_number' => [
                'type'              => 'string',
                'description'       => 'Number of the card for \'card\' mode.',
                'visible'           => ['mode', '=', 'card']
            ],

            'card_expire' => [
                'type'              => 'string',
                'description'       => 'Expiration month (mm/YY) for \'card\' mode.',
                'visible'           => ['mode', '=', 'card']
            ],

            'card_type' => [
                'type'              => 'string',
                'description'       => "OTA payment card code for \'card\' mode.",
                'selection'         => [
                    'AX' => 'American Express',
                    'BC' => 'Bank Card',
                    'BL' => 'Carte Bleue',
                    'CB' => 'Carte Blanche',
                    'DN' => 'Diners Club',
                    'DS' => 'Discover Card',
                    'EC' => 'Eurocard',
                    'JC' => 'Japanese Credit Bureau Credit Card',
                    'LC' => 'Local Card',
                    'MA' => 'Maestro',
                    'MC' => 'Master Card',
                    'SO' => 'Solo',
                    'CU' => 'Union Pay',
                    'TP' => 'Universal Air Travel Card',
                    'VE' => 'Visa Electron',
                    'VI' => 'Visa'
                ],
                'visible'           => ['mode', '=', 'card']
            ]

        ];
    }


    public static function calcName($orm, $ids, $lang) {
        $result = [];
        $guarantees = $orm->read(self::getType(), $ids, ['mode', 'holder', 'card_type', 'card_expire'], $lang);

        if($guarantees > 0) {
            foreach($guarantees as $id => $guarantee) {
                $result[$id] = "{$guarantee['holder']} ({$guarantee['mode']})";
            }
        }
        return $result;
    }


}