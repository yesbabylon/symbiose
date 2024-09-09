<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace sale\order;

class Contract extends \sale\contract\Contract {

    public static function getColumns() {

        return [
            'name' => [
                'type'              => 'computed',
                'function'          => 'calcName',
                'result_type'       => 'string',
                'store'             => true,
                'description'       => 'The display name of the contract.'
            ],

            'order_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\order\Order',
                'description'       => 'Order the contract relates to.',
                'required'          => true
            ],

            'contract_lines_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'sale\order\ContractLine',
                'foreign_field'     => 'contract_id',
                'description'       => 'Contract lines that belong to the contract.',
                'ondetach'          => 'delete'
            ]

        ];
    }

    public static function calcName($self) {
        $result = [];
        $self->read(['order_id', 'customer_id' => ['name'], 'order_id' => ['id', 'name']]);
        foreach($self as $id => $contract) {
            $ids = self::search(['order_id', '=', $contract['order_id']['id']]);
            $result[$id] = sprintf("%s - %s - %d", $contract['customer_id']['name'], $contract['order_id']['name'], count($ids));
        }
        return $result;
    }



    public static function canupdate($self,  $values) {
        $self->read(['status']);
        foreach($self as $contract) {
            if(count($values) > 1 || !isset($contract['status'])) {
                return ['status' => ['not_allowed' => 'Contract cannot be manually updated.']];
            }

        }

        return parent::canupdate($self, $values);
    }
}