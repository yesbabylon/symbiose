<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace lodging\documents;


class Export extends \documents\Document {

    public function getTable() {
        return 'lodging_documents_export';
    }

    public static function getColumns() {
        return [
            'name' => [
                'type'              => 'computed',
                'result_type'       => 'string',
                'function'          => 'calcName',
                'store'             => true
            ],

            'center_office_id' => [
                'type'              => 'many2one',
                'foreign_object'    => \lodging\identity\CenterOffice::getType(),
                'description'       => 'Office the invoice relates to (for center management).',
            ],

            'export_type' => [
                'type'              => 'string',
                'selection'         => [
                    'invoices',
                    'payments'
                ],
                'required'          => true,
                'readonly'          => true
            ],

            'is_exported' => [
                'type'              => 'boolean',
                'default'           => false,
                'description'       => 'Mark the archive as already exported.'
            ]

        ];
    }

    public static function calcName($om, $ids, $lang) {
        $result = [];
        $exports = $om->read(self::getType(), $ids, ['created', 'export_type', 'center_office_id.name'], $lang);
        if($exports) {
            foreach($exports as $oid => $export) {
                $result[$oid] = date('Ymd', $export['created']).' - '.$export['export_type'].' - '.$export['center_office_id.name'];
            }
        }
        return $result;
    }

}