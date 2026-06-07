<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2024
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace sale\contract;

use sale\customer\Customer;

class ServiceAccount extends \sale\contract\Contract {

    public function getTable() {
        return 'sale_contract_serviceaccount';
    }

    public static function getFlags(): int {
        return EQ_FLAG_OWN_TABLE;
    }

    public static function getDescription() {
        return "Service Accounts relate to Customers and are equivalent to Contracts. These entities are Read-Only and synced from AutoTask.";
    }

    public static function getColumns() {
        return [

            'reporting_from' => [
                'type'              => 'date',
                'description'       => 'First date to use when no previous report exists.'
            ],

            'service_account_entries_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'sale\contract\ServiceAccountEntry',
                'foreign_field'     => 'service_account_id',
                'description'       => 'List of all lines referring to the service account.',
            ],

            'reports_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'sale\contract\Report',
                'foreign_field'     => 'service_account_id',
                'description'       => 'List of reports of the service account.',
                'ondetach'          => 'delete'
            ],

            'balance_current' => [
                'type'              => 'computed',
                'result_type'       => 'float',
                'store'             => true,
                'description'       => 'The amount of remaining points in the account according to all existing lines.',
                'function'          => 'calcBalanceCurrent'
            ],

            'has_balance_changed' => [
                'type'              => 'boolean',
                'description'       => 'Mark the balance as changed.',
                'default'           => false
            ],

            'last_entry_id' => [
                'type'              => 'computed',
                'result_type'       => 'many2one',
                'foreign_object'    => 'sale\contract\ServiceAccountEntry',
                'function'          => 'calcLastEntryId',
                'description'       => 'The most recent line relating to the the account.'
            ],

            'is_invoiceable' => [
                'type'              => 'boolean',
                'description'       => 'The contract as implies CutOff (will be listed in Cut-Off Reports).',
                'default'           => true
            ],

            'm_reporting' => [
                'type'              => 'string',
                'description'       => 'Mode for reporting to the customer about the contract.',
                'help'              => 'Indicates how the Reports must be communicated to the customer: no sending, sending, sending + archive.',
                'default'           => 'None',
                'selection'         => [
                    'None',
                    'Send',
                    'Archive'
                ]
            ],

            'has_monthly_target' => [
                'type'              => 'boolean',
                'description'       => "Flag for Service account with monthly target.",
                'default'           => false
            ],

            'monthly_target' => [
                'type'              => 'float',
                'usage'             => 'numeric/real:5.2',
                'description'       => "Estimated amount of points for internal followup purpose.",
                'visible'           => ['has_monthly_target', '=', true]
            ],

            'renew_auto' => [
                'type'              => 'boolean',
                'description'       => "Automatic Renewal of ServicePackage.",
                'default'           => false
            ],

            'renew_amount' => [
                'type'              => 'float',
                'usage'             => 'amount/money:2',
                'description'       => "Renewal ServicePack amount in €.",
                'visible'           => ['renew_auto', '=', true]
            ],

            'renew_floor' => [
                'type'              => 'float',
                'description'       => "Floor in # for triggering renewal.",
                'default'           => 0,
                'visible'           => ['renew_auto', '=', true]
            ],

            'has_renew_alert_sent' => [
                'type'              => 'boolean',
                'description'       => "Balance is below floor and ticket has been sent to AT.",
                'default'           => false
            ]

        ];
    }


    public static function calcBalanceCurrent($self) {
        $result = [];
        foreach($self as $id => $serviceAccount) {
            // retrieve the amount of balance_new from the latest released report
            $report = Report::search([
                        ['service_account_id', '=', $id],
                        ['status', '<>', 'pending']
                    ],
                    [
                        'sort'  => ['id' => 'desc'],
                        'limit' => 1
                    ]
                )
                ->read(['balance_new'])
                ->first();
            // use report balance_new and, if missing, fallback to account initial balance
            if($report) {
                $balance = $report['balance_new'];
            }
            else {
                $balance = 0.0;
            }
            $result[$id] = $balance;
        }
        return $result;
    }

    public static function calcLastEntryId($self) {
        $result = [];
        foreach($self as $id => $serviceAccount) {
            $line = ServiceAccountEntry::search(['service_account_id', '=', $id], ['sort'  => ['date' => 'desc'], 'limit' => 1])
                ->read(['id'])
                ->first();
            if($line) {
                $result[$id] = $line['id'];
            }
        }
        return $result;
    }



}
