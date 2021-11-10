<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace finance\accounting;
use equal\orm\Model;

class Invoice extends Model {
    
    public static function getName() {
        return "Invoice";
    }

    public static function getDescription() {
        return "An invoice is a legal document issued by a seller to a buyer that relates to a sale, and is part of the accounting system.";
    }

    public static function getColumns() {

        return [
            'name' => [
                'type'              => 'alias',
                'alias'             => "number"
            ],

            /* owner organisation */
            'organisation_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'identity\Identity',
                'description'       => "The organisation the invoice belongs to.",
                'required'          => true
            ],

            'status' => [
                'type'              => 'string',
                'selection'         => ['proforma', 'invoice'],
            ],

            'number' => [
                'type'              => 'computed',
                'function'          => 'finance\accounting\Invoice::getNumber',
                'result_type'       => 'string',
                'store'             => true,
                'description'       => "Number of the invoice, according to organisation logic (@see config/invoicing)."
            ],

            'is_paid' => [
                'type'              => 'boolean',
                'default'           => false,
                'description'       => "Flag to mark the invoice as fully paid.",
            ],

            'date' => [
                'type'              => 'datetime',
                'description'       => 'Creation date of the invoice.'
            ],

            'partner_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'identity\Partner',
                'description'       => "Organisation which has to pay for the goods and services related to the sale."
            ],

            /* the organisation the invoice relates to (multi-company support) */
            'organisation_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'identity\Identity',
                'description'       => "The organisation that emitted the invoice.",
                'default'           => 1
            ]

        ];
    }

    public static function getNumber($om, $oids, $lang) {
        $result = [];

        $invoices = $om->read(__CLASS__, $oids, ['status', 'organisation_id'], $lang);

        foreach($invoices as $oid => $invoice) {

            // no code is generated for proforma
            if($invoice['status'] == 'invoice') {
                $settings_ids = $om->search('core\Setting', [
                    ['name', '=', 'invoice.sequence.'.$invoice['organisation_id']],
                    ['package', '=', 'sale'],
                    ['section', '=', 'invoice']
                ]);
    
                if($settings_ids < 0 || !count($settings_ids)) {
                    // unexpected error : misconfiguration (setting is missing)
                    $result[$oid] = 0;
                    continue;
                }
    
                $settings = $om->read('core\SettingValue', $settings_ids, ['value']);
                if($settings < 0 || count($settings) != 1) {
                    // unexpected error : misconfiguration (no value for setting)
                    $result[$oid] = 0;
                    continue;
                }
    
                $setting = array_pop($settings);
                $sequence = (int) $setting['value'];
                $om->write('core\SettingValue', $settings_ids, ['value' => $sequence + 1]);
    
                $result[$oid] = sprintf("%4d-%02d-%034", date('Y'), $invoice['organisation_id'], $sequence);
            }            

        }
        return $result;

    }       

}