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
                'usage'             => 'text/plain:small',
                'description'       => 'Short description or comments about the contract (e.g. the object of the agreement).'
            ],

            'is_active' => [
                'type'              => 'boolean',
                'description'       => 'Mark the contract as being active or not.',
                'default'           => true
            ],

            'date_from' => [
                'type'              => 'date',
                'description'       => 'Date at which the contract has been officially released.'
            ],

            'date_to' => [
                'type'              => 'date',
                'description'       => 'Date at which the contract ends or has to be renewed.'
            ],

            'proposal_valid_until' => [
                'type'              => 'date',
                'description'       => 'Date after which the contract lapses if it has not been approved.',
                'visible'           => [ 'status', 'in', ['pending', 'sent'] ]
            ],

            'sent_at' => [
                'type'              => 'datetime',
                'description'       => 'Date and time at which the contract was sent to the customer.'
            ],

            'accepted_at' => [
                'type'              => 'datetime',
                'description'       => 'Date and time at which the contract was accepted by the customer.'
            ],

            'rejected_at' => [
                'type'              => 'datetime',
                'description'       => 'Date and time at which the contract was rejected by the customer.'
            ],

            'rejection_reason' => [
                'type'              => 'string',
                'usage'             => 'text/plain',
                'description'       => 'Reason provided when the contract is rejected.',
                'visible'           => [ 'status', '=', 'rejected' ]
            ],

            'customer_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\customer\Customer',
                'description'       => 'The customer the contract relates to.',
                'dependents'        => ['name']
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
            ],

            'status' => [
                'type'              => 'string',
                'selection'         => [
                    'pending',
                    'ready',                // proposal ready to be sent
                    'sent',                 // sent to customer for signature
                    'signed',               // signed by customer (valid)
                    'rejected',
                    'expired',
                    'cancelled'             // outdated or rejected
                ],
                'description'       => 'Status of the contract.',
                'default'           => 'pending'
            ]
        ];
    }

    public static function calcName($self) {
        $result = [];
        $self->read(['customer_id' => ['name']]);
        foreach($self as $id => $contract) {
            if(!$contract['customer_id']) {
                continue;
            }
            $result[$id] = sprintf("{$contract['customer_id']['name']} - %05d", $contract['id']);
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
