<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2026
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

namespace symbiose\import;

use document\document\Document;
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

            'import_type' => [
                'type'              => 'string',
                'selection'         => [
                    'contacts_import'
                ],
                'description'       => 'Targeted type of the import.',
                'required'          => true
            ],

            'status' => [
                'type'              => 'string',
                'selection'         => [
                    'doc_missing',
                    'doc_uploaded',
                    'import_check_failed',
                    'import_checked',
                    'import_failed',
                    'imported'
                ],
                'default'           => 'doc_missing'
            ],

            'data' => [
                'type'              => 'binary',
                'description'       => 'Raw binary data of the uploaded document',
                'help'              => 'This field is meant to be used for the subsequent document creation, and is emptied once the document creation is confirmed.',
                'onupdate'          => 'onupdateData'
            ],

            'document_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'document\document\Document',
                'description'       => 'Targeted document of the import.',
                'onupdate'          => 'onupdateDocumentId',
                'readonly'          => true
            ],

            'logs' => [
                'type'              => 'string',
                'usage'             => 'text/json',
                'description'       => 'Human readable descriptor of the processing result.'
            ]

        ];
    }

    public static function getActions(): array {
        return [
            'create_document' => [
                'description'   => 'Create the document holding the uploaded import data.',
                'policies'      => [],
                'function'      => 'doCreateDocument'
            ],
            'synchronize_document_status' => [
                'description'   => 'Mark the import as uploaded once a document is linked.',
                'policies'      => [],
                'function'      => 'doSynchronizeDocumentStatus'
            ]
        ];
    }

    public static function getWorkflow() {
        return [
            'doc_missing' => [
                'description' => 'The document is missing, it must to be uploaded.',
                'transitions' => [
                    'mark_uploaded' => [
                        'status'        => 'doc_uploaded',
                        'description'   => 'Mark the import as document uploaded.'
                    ]
                ]
            ],
            'doc_uploaded' => [
                'description' => 'The document was uploaded and is waiting to be checked for validity.',
                'transitions' => [
                    'mark_check_failed' => [
                        'status'        => 'check_failed',
                        'description'   => 'Mark the import document as failing the validations checks.'
                    ],
                    'mark_checked' => [
                        'status'        => 'checked',
                        'description'   => 'Mark the import document as passing the validation checks.'
                    ]
                ]
            ],
            'check_failed' => [
                'description' => "The document hasn't passed the validation checks.",
                'transitions' => [
                    'mark_checked' => [
                        'status'        => 'checked',
                        'description'   => 'Mark the import document as passing the validation checks.'
                    ]
                ]
            ],
            'checked' => [
                'description' => "The document has passed the validation checks and is ready to be imported.",
                'transitions' => [
                    'mark_import_failed' => [
                        'status'        => 'import_failed',
                        'description'   => 'Mark the data import as failed.'
                    ],
                    'mark_imported' => [
                        'status'        => 'imported',
                        'description'   => 'Mark the document as imported.'
                    ]
                ]
            ],
            'import_failed' => [
                'description' => "The document couldn't be imported.",
                'transitions' => [
                    'mark_imported' => [
                        'status'        => 'imported',
                        'description'   => 'Mark the document as imported.'
                    ]
                ]
            ],
            'imported' => [
                'description' => 'The uploaded document was parsed and imported.'
            ]
        ];
    }

    /**
     * Handle data update (i.e. file upload).
     * This method is used to create the document based on received data, and start the processing.
     */
    protected static function onupdateData($self) {
        $self->do('create_document');
    }

    protected static function doCreateDocument($self) {
        $self->read(['name', 'data']);

        foreach($self as $id => $dataImport) {
            if(!$dataImport['data']) {
                continue;
            }

            $document = Document::create([
                'name'  => $dataImport['name'],
                'data'  => $dataImport['data']
            ])
                ->read(['id'])
                ->first(true);

            // remove current object data (pointless after successful import)
            self::id($id)
                ->write([
                    'document_id'   => $document['id'],
                    'data'          => null
                ])
                ->do('synchronize_document_status');
        }
    }

    protected static function onupdateDocumentId($self) {
        $self->do('synchronize_document_status');
    }

    protected static function doSynchronizeDocumentStatus($self) {
        $self->read(['status', 'document_id']);
        foreach($self as $id => $dataImport) {
            if($dataImport['document_id'] && $dataImport['status'] === 'doc_missing') {
                self::id($id)->transition('mark_uploaded');
            }
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
