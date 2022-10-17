<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace yesbabylon\service;

use equal\cron\Scheduler;
use equal\orm\Model;

class Service extends \sale\contract\Contract {

    public static function getName() {
        return "Service";
    }

    public static function getDescription() {
        return "";
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

            'service_lines_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'yesbabylon\service\ServiceLine',
                'foreign_field'     => 'service_id',
                'description'       => 'Service lines that belong to the service.',
                'ondetach'          => 'delete',
                'onupdate'          => 'createTask'
            ],

            'is_recurring' => [
                'type'              => 'string',
                'selection'         => [
                    'week',
                    'month',
                    'quarter',
                    'year',
                    'none'
                ],
                'description'       => 'Status of the service.',
                'default'           => 'none'
            ],

            'last_invoice' => [
                'type'              => 'date',
                'description'       => 'Latest date at which the service was invoiced.'
            ],

            'next_invoice' => [
                'type'              => 'computed',
                'result_type'       => 'date',
                'description'       => "Next expected invoice date.",
                'function'          => 'calcNextInvoice',
                'store'             => true
            ],

            'start_service' => [
                'type'              => 'date',
                'description'       => 'Date at which the service starts.'
            ],

            'end_service' => [
                'type'              => 'date',
                'description'       => 'Date at which the service starts.'
            ],

            'invoices_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'yesbabylon\accounting\Invoice',
                'foreign_field'     => 'service_id',
                'description'       => 'Invoices that relate to the service.',
                'ondetach'          => 'delete'
            ],

            'customer_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\customer\Customer',
                'domain'            => ['relationship', '=', 'customer'],
                'description'       => 'The customer the contract relates to.',
            ],

        ];
    }

    public static function calcName($om, $oids, $lang) {
        $result = [];
        $res = $om->read('yesbabylon\\service\\Service', $oids, ['id', 'customer_id']);
        foreach($res as $oid => $odata) {
            ob_start();
            var_dump($odata);
            $buff = ob_get_clean();
            trigger_error("QN_DEBUG_ORM::{$buff}", QN_REPORT_ERROR);

            $result[$oid] = "{$odata['customer_id.name']} - {$odata['id']}";
        }
        return $result;
    }


    public static function calcNextInvoice($om, $oids, $lang) {
        $result = [];

        $lines = $om->read(get_called_class(), $oids, ['last_invoice', 'is_recurring']);

        foreach($lines as $oid => $odata) {

            if(!isset($odata['last_invoice'])){
                // start of the contract ????
                $result[$oid] = time();
            }
            elseif($odata['is_recurring'] != 'none'){
                // 15 jours arbitraires, difficile d'application pendant les semaines, faire un switch en fonction me semble plausible
                $result[$oid] = strtotime(date('c',$odata['last_invoice']).'+1'.$odata['is_recurring']);
            }else{
                 $result[$oid] = null;
            }
        }
        return $result;
    }
}