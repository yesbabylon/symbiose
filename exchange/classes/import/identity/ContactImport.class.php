<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2026
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

namespace exchange\import\identity;

use exchange\import\DataImport;

class ContactImport extends DataImport {

    public static function getName() {
        return "Contact import";
    }

    public static function getDescription() {
        return "A contact import allows to import contacts from an uploaded document.";
    }

    public static function getColumns() {
        return [

            'import_type' => [
                'type'              => 'string',
                'description'       => 'Forced type of import.',
                'default'           => 'contacts_import'
            ]

        ];
    }

    public static function getActions(): array {
        return array_merge(parent::getActions(), [
            'check' => [
                'description' => 'Check the contact import data.',
                'policies'    => ['can_check'],
                'function'    => 'doCheck'
            ],
            'import' => [
                'description' => 'Import the checked contact data.',
                'policies'    => ['can_import'],
                'function'    => 'doImport'
            ]
        ]);
    }

    protected static function doCheck($self) {
        foreach($self as $id => $contactImport) {
            try {
                $result = \eQual::run('do', 'exchange_import_identity_ContactImport_check', ['id' => $id]);
                self::id($id)->update([
                    'logs' => json_encode($result['logs'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
                ]);
                if($result['errors'] > 0) {
                    throw new \Exception('errors_at_check', EQ_ERROR_UNKNOWN);
                }
                self::id($id)->transition('mark_ready');
            }
            catch(\Throwable $e) {
                self::id($id)->transition('mark_failing');
                throw $e;
            }
        }
    }

    protected static function doImport($self) {
        foreach($self as $id => $contactImport) {
            try {
                $result = \eQual::run('do', 'exchange_import_identity_ContactImport_import', ['id' => $id]);
                $import = self::id($id)
                    ->read(['logs'])
                    ->first();
                $logs = json_decode($import['logs'], true) ?? [];
                self::id($id)->update([
                    'logs' => json_encode(array_merge($logs, $result['logs']), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
                ]);
                if($result['errors'] > 0) {
                    throw new \Exception('errors_at_import', EQ_ERROR_UNKNOWN);
                }
                self::id($id)->transition('mark_imported');
            }
            catch(\Throwable $e) {
                self::id($id)->transition('mark_failing');
                throw $e;
            }
        }
    }

}
