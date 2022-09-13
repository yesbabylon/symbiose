<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
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
                'domain'            => ['relationship', '=', 'customer'],
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

    public static function calcName($om, $oids, $lang) {
        $result = [];
        $res = $om->read(get_called_class(), $oids, ['id', 'customer_id.name']);
        foreach($res as $oid => $odata) {
            $result[$oid] = "{$odata['customer_id.name']} - {$odata['id']}";
        }
        return $result;
    }

    /**
     * Compute the VAT excl. total price of the contract, with discounts applied.
     *
     */
    public static function calcTotal($om, $oids, $lang) {
        $result = [];
        $contracts = $om->read(__CLASS__, $oids, ['contract_lines_ids.total']);

        foreach($contracts as $oid => $contract) {
            $result[$oid] = array_reduce($contract['contract_lines_ids.total'], function ($c, $a) {
                return $c + $a['total'];
            }, 0.0);
        }
        return $result;
    }

    /**
     * Compute the final VAT incl. price of the contract.
     *
     */
    public static function calcPrice($om, $oids, $lang) {
        $result = [];
        $contracts = $om->read(__CLASS__, $oids, ['contract_lines_ids.price']);

        foreach($contracts as $oid => $contract) {
            $result[$oid] = array_reduce($contract['contract_lines_ids.price'], function ($c, $a) {
                return $c + $a['price'];
            }, 0.0);
        }

        return $result;
    }

}