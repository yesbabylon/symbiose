<?php
/*
    Developed by Yesbabylon - https://yesbabylon.com
    (c) 2025-2026 Yesbabylon SA
    Licensed under the GNU AGPL v3 License - https://www.gnu.org/licenses/agpl-3.0.html
*/

namespace document\document;

use equal\data\DataGenerator;
use equal\orm\Model;

class DocumentType extends Model {

    public static function getColumns() {
        return [

            'name' => [
                'type'              => 'string',
                'description'       => 'Name of the document Type.',
                'required'          => true
            ],

            'uuid' => [
                'type'              => 'string',
                'usage'             => 'text/plain:36',
                'unique'            => true,
                'description'       => 'Unique supplier identifier provided by GLOBAL instance.'
            ],

            'object_class' => [
                'type'              => 'string',
                'description'       => 'Class of the objects targeted by the Document Type.'
            ],

            'code' => [
                'type'              => 'string',
                'usage'             => 'text/plain:40',
                'description'       => 'Unique code identifier of the document Type.',
                'required'          => true,
                'unique'            => true
            ],

            'folder_code' => [
                'type'              => 'string',
                'description'       => 'Code of the Folder node a document by this type must be assigned to.',
                'required'          => true
            ],

            'description' => [
                'type'              => 'string',
                'usage'             => 'text/plain.short',
                'description'       => 'Description of the purpose and usage of the tag.'
            ],

            'json_schema' => [
                'type'              => 'string',
                'description'       => 'URN identifier of the schema following json-schema.org specs.'
            ],

            'document_visibility' => [
                'type'              => 'string',
                'selection'         => [
                    'condo',        // visible to all owners of a same condo + syndic
                    'ownership',    // visible to all owners of a same ownership + syndic
                    'owner',        // visible only to a single owner or supplier
                    'suppliership', // visible to a specific supplier of a condo + syndic
                    'agency'        // visible only to syndic (employees)
                ],
                'default'           => 'agency',
                'onupdate'          => 'onupdateDocumentVisibility',
                'description'       => 'Defines who can access the document.',
                'help'              => 'This field is synchronized with the node and updates automatically when the parent node visibility changes.'
            ],

            'has_subtype' => [
                'type'              => 'boolean',
                'description'       => 'The document type has 2 ore more subtypes.',
                'default'           => false
            ],

            'documents_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'document\document\Document',
                'foreign_field'     => 'document_type_id',
                'description'       => 'Documents matching the document type.'
            ],

            'document_subtypes_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'document\document\DocumentSubtype',
                'foreign_field'     => 'document_type_id',
                'description'       => 'Sub-types relating to the Document Type.'
            ],

            'document_assignment_rules_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'document\processing\DocumentAssignmentRule',
                'foreign_field'     => 'document_type_id',
                'description'       => "Document assignment rules that are related to this document type."
            ],

            'validation_rules_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'document\validation\ValidationRule',
                'foreign_field'     => 'document_type_id',
                'description'       => 'Validation rules relating to the document type.'
            ],

            'recording_rules_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'document\recording\RecordingRule',
                'foreign_field'     => 'document_type_id',
                'description'       => 'Recording rules relating to the document type.'
            ],

            'labeling_rules_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'document\recording\RecordingRule',
                'foreign_field'     => 'document_type_id',
                'description'       => 'Labeling rules relating to the document type.'
            ]


        ];
    }

    /**
     * This is a "private class": upon creation, assign a unique UUID if on GLOBAL instance
     */
    protected static function oncreate($self, $orm) {
        $self->read(['state']);
        foreach($self as $id => $object) {
            if(defined('FMT_INSTANCE_TYPE') && constant('FMT_INSTANCE_TYPE') === 'global') {
                do {
                    $uuid = DataGenerator::uuid();
                    $existing = $orm->search(static::class, ['uuid', '=', $uuid]);
                } while( $existing > 0 && count($existing) > 0 );

                self::id($id)->update([
                    'state' => $object['state'],
                    'uuid'  => $uuid
                ]);
            }
        }
    }

    protected static function doSyncUuidLinks($self) {
        $self->read(['uuid']);
        foreach($self as $id => $document_type) {
            if(!empty($document_type['uuid'])) {
                $subtypes = DocumentSubtype::search(['document_type_uuid', '=', $document_type['uuid']])
                    ->read(['document_type_id'])
                    ->get();

                $subtypes_to_sync_ids = [];
                foreach($subtypes as $st_id => $subtype) {
                    if($subtype['document_type_id'] !== $id) {
                        $subtypes_to_sync_ids[] = $st_id;
                    }
                }

                if(!empty($subtypes_to_sync_ids)) {
                    DocumentSubtype::ids($subtypes_to_sync_ids)->update(['document_type_id' => $id]);
                }
            }
        }
    }

    public static function getActions() {
        return [
            'sync_uuid_links' => [
                'description'   => 'Synchronize the uuid links.',
                'policies'      => [],
                'function'      => 'doSyncUuidLinks'
            ]
        ];
    }
}
