<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2026
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

namespace exchange\import;

use document\document\Document;
use document\document\DocumentType;
use equal\orm\Model;

class DataImport extends Model {

    public static function getName() {
        return "Data import";
    }

    public static function getDescription() {
        return "Base for data import from an uploaded document.";
    }

    public static function getColumns() {
        return [

            'name' => [
                'type'              => 'string',
                'description'       => 'Display name of data import.',
            ],

            'data' => [
                'type'              => 'binary',
                'description'       => 'Raw binary data of the uploaded document.',
                'help'              => 'This field is meant to be used for the subsequent document creation, and is emptied once the document creation is confirmed.'
            ],

            'document_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'document\document\Document',
                'description'       => 'Targeted document of the import.',
                'readonly'          => true
            ],

            'import_type' => [
                'type'              => 'string',
                'selection'         => [
                    'banks_import',
                    'bank_statement',
                    'contacts_import',
                    'customers_import',
                    'suppliers_import',
                ],
                'description'       => 'Targeted type of the import.',
                'required'          => true
            ],

            'logs' => [
                'type'              => 'string',
                'usage'             => 'text/json',
                'description'       => 'Human readable descriptor of the processing result.'
            ],

            'status' => [
                'type'              => 'string',
                'selection'         => [
                    'pending',
                    'ready',
                    'failing',
                    'imported'
                ],
                'default'           => 'pending',
                'description'       => 'Current processing status (to avoid concurrent executions).',
                'help'              => "Remains `pending` while the uploaded data hasn't passed validation."
            ],

        ];
    }

    public static function getActions(): array {
        return array_merge(parent::getActions(), [
            'process' => [
                'description' => 'Check and import a newly instantiated data import.',
                'policies'    => [],
                'function'    => 'doProcess'
            ],
            'remove' => [
                'description' => 'Remove a terminal data import.',
                'help'        => 'Only failing or imported data imports can be removed.',
                'policies'    => ['can_remove'],
                'function'    => 'doRemove'
            ]
        ]);
    }

    public static function getPolicies(): array {
        return array_merge(parent::getPolicies(), [
            'can_check' => [
                'description' => 'Check whether the data import is pending.',
                'function'    => 'policyCanCheck'
            ],
            'can_import' => [
                'description' => 'Check whether the data import is ready.',
                'function'    => 'policyCanImport'
            ],
            'can_remove' => [
                'description' => 'Check whether the data import can be removed.',
                'function'    => 'policyCanRemove'
            ]
        ]);
    }

    public static function getWorkflow() {
        return [
            'pending' => [
                'description' => 'The uploaded data is waiting to be checked.',
                'transitions' => [
                    'mark_failing' => [
                        'status'        => 'failing',
                        'description'   => 'Mark the data import as failing.'
                    ],
                    'mark_ready' => [
                        'status'        => 'ready',
                        'description'   => 'Mark the data import as ready.'
                    ]
                ]
            ],
            'ready' => [
                'description' => 'The uploaded data is ready to be imported.',
                'transitions' => [
                    'mark_failing' => [
                        'status'        => 'failing',
                        'description'   => 'Mark the data import as failed.'
                    ],
                    'mark_imported' => [
                        'status'        => 'imported',
                        'description'   => 'Mark the data import as imported.'
                    ]
                ]
            ],
            'failing' => [
                'description' => 'The data import failed and can only be removed.'
            ],
            'imported' => [
                'description' => 'The uploaded data was imported.'
            ]
        ];
    }

    protected static function policyCanCheck($self): array {
        $result = [];

        $self->read(['status']);

        foreach($self as $id => $dataImport) {
            if($dataImport['status'] !== 'pending') {
                $result[$id] = [
                    'data_import_not_pending' => 'Only pending data imports can be checked.'
                ];
            }
        }

        return $result;
    }

    protected static function policyCanImport($self): array {
        $result = [];

        $self->read(['status']);

        foreach($self as $id => $dataImport) {
            if($dataImport['status'] !== 'ready') {
                $result[$id] = [
                    'data_import_not_ready' => 'Only ready data imports can be imported.'
                ];
            }
        }

        return $result;
    }

    protected static function policyCanRemove($self): array {
        $result = [];

        $self->read(['status']);

        foreach($self as $id => $dataImport) {
            if(!in_array($dataImport['status'], ['failing', 'imported'], true)) {
                $result[$id] = [
                    'data_import_not_terminal' => 'Only failing or imported data imports can be removed.'
                ];
            }
        }

        return $result;
    }

    protected static function doRemove($self) {
        $self->delete(true);
    }

    protected static function onafterinstantiate($self) {
        if($self->getClass() !== self::class) {
            $self->do('process');
        }
    }

    protected static function doProcess($self) {
        try {
            $self
                ->do('check')
                ->do('import');
        }
        catch(\Throwable $e) {
            // unexpected error
        }
    }

    /**
     * Handle data update (i.e. file upload).
     * This method is used to create the document based on received data, and start the processing.
     */
    protected static function onbeforeinstantiate($self) {
        $self->read(['name', 'data', 'import_type']);

        foreach($self as $id => $dataImport) {
            if(!$dataImport['data']) {
                continue;
            }

            $documentType = DocumentType::search(['code', '=', $dataImport['import_type']])->first();

            $document = Document::create([
                    'name'              => $dataImport['name'],
                    'data'              => $dataImport['data'],
                    'document_type_id'  => $documentType['id'],
                    'is_origin'         => true
                ])
                ->first();

            self::id($id)->write([
                    // attach document
                    'document_id'   => $document['id'],
                    // remove current object data (pointless after successful import)
                    'data'          => null
                ]);
        }
    }

    public static function onchange($event, $values) {
        $result = [];
        if(isset($event['data']['name'])) {
            $result['name'] = $event['data']['name'];
        }

        return $result;
    }
}
