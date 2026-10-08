<?php
/*
    Developed by Yesbabylon - https://yesbabylon.com
    (c) 2025-2026 Yesbabylon SA
    Licensed under the GNU AGPL v3 License - https://www.gnu.org/licenses/agpl-3.0.html
*/
namespace document\document;

use equal\data\DataGenerator;
use equal\orm\Model;

class DocumentSubtype extends Model {

    public static function getColumns() {
        return [

            'name' => [
                'type'              => 'string',
                'description'       => 'Name of the document Subtype.',
                'required'          => true
            ],

            'uuid' => [
                'type'              => 'string',
                'usage'             => 'text/plain:36',
                'unique'            => true,
                'description'       => 'Unique supplier identifier provided by GLOBAL instance.'
            ],

            'code' => [
                'type'              => 'string',
                'usage'             => 'text/plain:40',
                'description'       => 'Unique code identifier of the document Subtype.',
                'required'          => true
            ],

            'folder_code' => [
                'type'              => 'string',
                'description'       => 'Code of the Folder node a document by this type must be assigned to.'
            ],

            'description' => [
                'type'              => 'string',
                'usage'             => 'text/plain.short',
                'description'       => 'Description of the purpose and usage of the tag.'
            ],

            'document_type_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'document\document\DocumentType',
                'description'       => 'Parent documents type.',
                'dependents'        => ['document_type_uuid']
            ],

            'document_type_uuid' => [
                'type'              => 'computed',
                'result_type'       => 'string',
                'usage'             => 'text/plain:36',
                'store'             => true,
                'instant'           => true,
                'relation'          => ['document_type_id' => 'uuid'],
                'description'       => 'Unique document type identifier provided by GLOBAL instance.'
            ],

            'recording_rules_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'document\recording\RecordingRule',
                'foreign_field'     => 'document_subtype_id',
                'description'       => 'Rules matching the document subtype.'
            ],

            'labeling_rules_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'document\labeling\LabelingRule',
                'foreign_field'     => 'document_subtype_id',
                'description'       => 'Rules matching the document subtype.'
            ],

            'documents_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'document\document\Document',
                'foreign_field'     => 'document_subtype_id',
                'description'       => 'Documents matching the document subtype.'
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
            ]

        ];
    }

    public function getUnique() {
        return [
            ['document_type_id', 'code']
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
        $self->read(['document_type_id', 'document_type_uuid']);
        foreach($self as $id => $document_subtype) {
            if(!empty($document_subtype['document_type_uuid'])) {
                $document = DocumentType::search(['uuid', '=', $document_subtype['document_type_uuid']])
                    ->first();

                if($document && $document_subtype['document_type_id'] !== $document['id']) {
                    self::id($id)->update(['document_type_id' => $document['id']]);
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
