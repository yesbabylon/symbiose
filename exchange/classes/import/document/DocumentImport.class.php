<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2026
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

namespace exchange\import\document;

use document\document\Document;
use exchange\import\DataImport;

class DocumentImport extends DataImport {

    public static function getName() {
        return 'Document import';
    }

    public static function getDescription() {
        return 'A document import creates a document from an uploaded file and its metadata.';
    }

    public static function getColumns() {
        return [
            'organization_id' => [
                'type'           => 'many2one',
                'foreign_object' => 'identity\Organization',
                'description'    => 'Organization for which the document is imported.',
                'required'       => true,
                'default'        => 1
            ],

            'document_type_id' => [
                'type'           => 'many2one',
                'foreign_object' => 'document\document\DocumentType',
                'description'    => 'Type assigned to the imported document.',
                'required'       => true
            ],

            'document_subtype_id' => [
                'type'           => 'many2one',
                'foreign_object' => 'document\document\DocumentSubtype',
                'description'    => 'Optional subtype assigned to the imported document.',
                'domain'         => [['document_type_id', '=', 'object.document_type_id']]
            ],

            'document_visibility' => [
                'type'        => 'string',
                'selection'   => [
                    'organization',
                    'external',
                    'public'
                ],
                'default'     => 'organization',
                'description' => 'Defines who can access the imported document.',
                'required'    => true
            ],

            'import_type' => [
                'type'        => 'string',
                'selection'   => ['document'],
                'default'     => 'document',
                'description' => 'Forced type of import.',
                'required'    => true
            ]
        ];
    }

    public static function getActions(): array {
        return array_merge(parent::getActions(), [
            'check' => [
                'description' => 'Check the document import data.',
                'policies'    => ['can_check', 'is_document_importable'],
                'function'    => 'doCheck'
            ],
            'import' => [
                'description' => 'Create a document from the checked import data.',
                'policies'    => ['can_import', 'is_document_importable'],
                'function'    => 'doImport'
            ]
        ]);
    }

    public static function getPolicies(): array {
        return array_merge(parent::getPolicies(), [
            'is_document_importable' => [
                'description' => 'Check whether the document import has valid data and metadata.',
                'function'    => 'policyIsDocumentImportable'
            ]
        ]);
    }

    protected static function policyIsDocumentImportable($self): array {
        $result = [];

        $self->read([
            'data',
            'organization_id',
            'document_type_id',
            'document_subtype_id' => ['document_type_id']
        ]);

        foreach($self as $id => $documentImport) {
            $errors = [];

            if(empty($documentImport['data'])) {
                $errors['missing_data'] = 'The document data is required.';
            }
            if(empty($documentImport['organization_id'])) {
                $errors['missing_organization'] = 'The organization is required.';
            }
            if(empty($documentImport['document_type_id'])) {
                $errors['missing_document_type'] = 'The document type is required.';
            }
            if(
                !empty($documentImport['document_subtype_id'])
                && $documentImport['document_subtype_id']['document_type_id'] !== $documentImport['document_type_id']
            ) {
                $errors['invalid_document_subtype'] = 'The document subtype must belong to the selected document type.';
            }

            if(!empty($errors)) {
                $result[$id] = $errors;
            }
        }

        return $result;
    }

    /**
     * Document creation is deferred to the import action so all selected metadata can be applied.
     */
    protected static function onbeforeinstantiate($self) {
    }

    protected static function doCheck($self) {
        $self->transition('mark_ready');
    }

    protected static function doImport($self) {
        $self->read([
            'name',
            'document_id',
            'document_type_id',
            'document_subtype_id',
            'document_visibility'
        ]);

        foreach($self as $id => $documentImport) {
            Document::id($documentImport['document_id'])
            ->update([
                    'name'                => $documentImport['name'],
                    'document_type_id'    => $documentImport['document_type_id'],
                    'document_subtype_id' => $documentImport['document_subtype_id'],
                    'document_visibility' => $documentImport['document_visibility'],
                ])
                ->first();
        }

        $self->transition('mark_imported');
    }
}
