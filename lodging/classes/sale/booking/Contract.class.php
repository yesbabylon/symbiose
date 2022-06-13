<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace lodging\sale\booking;

class Contract extends \sale\booking\Contract {

    public static function getColumns() {

        return [
            'name' => [
                'type'              => 'computed',
                'function'          => 'calcName',
                'result_type'       => 'string',
                'store'             => true,
                'description'       => 'The display name of the contract.'
            ],

            'booking_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'lodging\sale\booking\Booking',
                'description'       => 'Booking the contract relates to.',
                'required'          => true
            ],

            'contract_line_groups_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'lodging\sale\booking\ContractLineGroup',
                'foreign_field'     => 'contract_id',
                'description'       => 'Contract lines that belong to the contract.',
                'ondetach'          => 'delete'
            ],

            'contract_lines_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'lodging\sale\booking\ContractLine',
                'foreign_field'     => 'contract_id',
                'description'       => 'Contract lines that belong to the contract.',
                'ondetach'          => 'delete'
            ]

        ];
    }

    public static function calcName($om, $oids, $lang) {
        $result = [];

        $res = $om->read(get_called_class(), $oids, ['booking_id', 'customer_id.name', 'booking_id.name']);
        foreach($res as $oid => $odata) {
            $ids = $om->search(get_called_class(), ['booking_id', '=', $odata['booking_id']]);
            $result[$oid] = sprintf("%s - %s - %d", $odata['customer_id.name'], $odata['booking_id.name'], count($ids));
        }
        return $result;
    }

}