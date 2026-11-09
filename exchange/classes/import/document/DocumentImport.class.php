<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2026
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

namespace exchange\import\document;

use document\document\Document;
use document\document\DocumentType;
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

    protected static function onafterinstantiate($self) {
        $self->read(['name', 'data', 'document_type_id', 'document_subtype_id', 'organization_id']);

        foreach($self as $id => $dataImport) {
            if(!$dataImport['data']) {
                continue;
            }

            $document = Document::create([
                    'organization_id'       => $dataImport['organization_id'],
                    'name'                  => $dataImport['name'],
                    'data'                  => $dataImport['data'],
                    'document_type_id'      => $dataImport['document_type_id'] ?? null,
                    'document_subtype_id'   => $dataImport['document_subtype_id'] ?? null,
                    'is_origin'             => true,
                    'source'                => 'upload'
                ])
                ->first();

            self::id($id)->write([
                    // attach document
                    'document_id'   => $document['id'],
                    // remove current object data (pointless after successful import)
                    'data'          => null
                ]);
        }

        $self
            ->transition('mark_ready')
            ->transition('mark_imported');

    }

}
