<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2024
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

namespace sale\contract;

use equal\orm\Model;

class Contract extends Model {

    public static function getName() {
        return "Contract";
    }

    public static function getDescription() {
        return "Contracts are formal agreement regarding the delivery of products or services concluded between two parties.";
    }

    public static function getColumns() {
        return [

            'name' => [
                'type'              => 'computed',
                'function'          => 'calcName',
                'result_type'       => 'string',
                'store'             => true,
                'description'       => 'The display name of the contract.'
            ],

            'description' => [
                'type'              => 'string',
                'description'       => 'Short description about the reason of the contract (i.e. the object of the agreement).'
            ],

            'status' => [
                'type'              => 'string',
                'selection'         => [
                    'pending',
                    'sent',                 // sent to customer for signature
                    'signed',               // signed by customer (valid)
                    'cancelled'             // outdated or rejected
                ],
                'description'       => 'Status of the contract.',
                'default'           => 'pending'
            ],

            'is_active' => [
                'type'              => 'boolean',
                'description'       => 'Mark the contract as being active or not.',
                'default'           => true
            ],

            'date' => [
                'type'              => 'date',
                'description'       => 'Date at which the contract has been officially released.'
            ],

            'valid_until' => [
                'type'              => 'date',
                'description'       => 'Date after which the contract lapses if it has not been approved.',
                'visible'           => [ 'status', 'in', ['pending', 'sent'] ]
            ],

            'customer_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\customer\Customer',
                'description'       => 'The customer the contract relates to.',
            ],

            'contract_lines_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'sale\contract\ContractLine',
                'foreign_field'     => 'contract_id',
                'description'       => 'Contract lines that belong to the contract.',
                'ondetach'          => 'delete'
            ],

            'total' => [
                'type'              => 'computed',
                'result_type'       => 'float',
                'function'          => 'calcTotal',
                'usage'             => 'amount/money:4',
                'description'       => 'Total tax-excluded price of the contract (computed).',
                'store'             => true
            ],

            'price' => [
                'type'              => 'computed',
                'result_type'       => 'float',
                'function'          => 'calcPrice',
                'usage'             => 'amount/money:2',
                'store'             => true,
                'description'       => "Final tax-included contract amount (computed)."
            ]

        ];
    }

    public static function calcName($self) {
        $result = [];
        $self->read(['customer_id' => ['name']]);
        foreach($self as $id => $contract) {
            $result[$id] = "{$contract['customer_id']['name']} - {$contract['id']}";
        }

        return $result;
    }

    public static function calcTotal($self): array {
        $result = [];
        $self->read(['contract_lines_ids' => ['total']]);
        foreach($self as $id => $contract) {
            $result[$id] = array_reduce($contract['contract_lines_ids']->get(true), function ($c, $a) {
                return $c + $a['total'];
            }, 0.0);
        }

        return $result;
    }

    public static function calcPrice($self): array {
        $result = [];
        $self->read(['contract_lines_ids' => ['price']]);
        foreach($self as $id => $contract) {
            $result[$id] = array_reduce($contract['contract_lines_ids']->get(true), function ($c, $a) {
                return $c + $a['price'];
            }, 0.0);
        }

        return $result;
    }
}
