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
                'multilang'         => true,
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
                'description'       => 'Folder node code assigned to documents of this type.',
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
                    'organization',
                    'external',
                    'public'
                ],
                'default'           => 'agency',
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
