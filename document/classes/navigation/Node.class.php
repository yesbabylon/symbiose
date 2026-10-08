<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2026
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

namespace document\navigation;

use document\document\Document;
use equal\orm\Model;

class Node extends Model {

    public static function getColumns() {
        return [

            'name' => [
                'type'              => 'string',
                'required'          => true,
                'description'       => 'Name displayed for the node.'
            ],

            'code' => [
                'type'              => 'string',
                'usage'             => 'text/plain:40',
                'description'       => 'Optional code used to identify a folder programmatically.',
                'visible'           => ['node_type', '=', 'folder']
            ],

            'description' => [
                'type'              => 'string',
                'description'       => 'Short description of the folder purpose.',
                'visible'           => ['node_type', '=', 'folder']
            ],

            'node_type' => [
                'type'              => 'string',
                'selection'         => [
                    'folder',
                    'document'
                ],
                'description'       => 'Type of content represented by the node.',
                'default'           => 'folder'
            ],

            'nodes_count' => [
                'type'              => 'computed',
                'result_type'       => 'integer',
                'description'       => 'Number of document nodes contained in this node hierarchy.',
                'function'          => 'calcNodesCount',
                'readonly'          => true
            ],

            'parent_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'document\navigation\Node',
                'description'       => 'Folder containing the node.',
                'domain'            => [
                    ['id', '<>', 'object.id'],
                    ['node_type', '=', 'folder']
                ],
                'ondelete'          => 'null'
            ],

            'node_visibility' => [
                'type'              => 'string',
                'selection'         => [
                    'organization',
                    'external',
                    'public'
                ],
                'default'           => 'organization',
                'description'       => 'Defines who can see the node.',
                'help'              => 'For document nodes, this field is synchronized with the related document visibility.',
                'onupdate'          => 'onupdateNodeVisibility'
            ],

            'document_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'document\document\Document',
                'description'       => 'Document represented by the node.',
                'visible'           => ['node_type', '=', 'document'],
                'ondelete'          => 'cascade'
            ],

            'document_link' => [
                'type'              => 'computed',
                'result_type'       => 'string',
                'usage'             => 'uri/url.relative',
                'description'       => 'URL used to open the represented document.',
                'relation'          => ['document_id' => 'link'],
                'readonly'          => true,
                'visible'           => ['node_type', '=', 'document']
            ],

            'nodes_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'document\navigation\Node',
                'foreign_field'     => 'parent_id',
                'description'       => 'Nodes directly contained in this folder.',
                'order'             => 'node_type,name'
            ],

            'is_system' => [
                'type'              => 'boolean',
                'description'       => 'Marks a folder managed by an application workflow.',
                'visible'           => ['node_type', '=', 'folder'],
                'default'           => false
            ]

        ];
    }

    public static function getActions(): array {
        return array_merge(parent::getActions(), [
            'sync_document_visibility' => [
                'description'   => 'Synchronize related documents with their node visibility.',
                'policies'      => [],
                'function'      => 'doSyncDocumentVisibility'
            ]
        ]);
    }

    protected static function onupdateNodeVisibility($self) {
        $self->do('sync_document_visibility');
    }

    protected static function doSyncDocumentVisibility($self) {
        $self->read(['node_type', 'node_visibility', 'document_id']);

        foreach($self as $node) {
            if($node['node_type'] !== 'document' || !$node['document_id']) {
                continue;
            }

            $document = Document::id($node['document_id'])
                ->read(['document_visibility'])
                ->first();

            if(
                $document
                && $document['document_visibility'] !== $node['node_visibility']
            ) {
                Document::id($node['document_id'])->update([
                    'document_visibility' => $node['node_visibility']
                ]);
            }
        }
    }

    protected static function calcNodesCount($self) {
        $result = [];

        $self->read(['node_type', 'nodes_ids']);

        foreach($self as $id => $node) {
            if($node['node_type'] === 'document') {
                $result[$id] = 1;
                continue;
            }

            $count = 0;
            $visited = [$id => true];
            $stack = $node['nodes_ids'] ?? [];

            while(!empty($stack)) {
                $node_id = array_pop($stack);
                if(!$node_id || isset($visited[$node_id])) {
                    continue;
                }

                $visited[$node_id] = true;
                $child = self::id($node_id)
                    ->read(['node_type', 'nodes_ids'])
                    ->first();

                if(!$child) {
                    continue;
                }

                if($child['node_type'] === 'document') {
                    ++$count;
                    continue;
                }

                foreach($child['nodes_ids'] ?? [] as $child_id) {
                    if($child_id && !isset($visited[$child_id])) {
                        $stack[] = $child_id;
                    }
                }
            }

            $result[$id] = $count;
        }

        return $result;
    }

    public static function onchange($event, $values) {
        $result = [];

        if(isset($event['node_type']) && $event['node_type'] === 'folder') {
            $result['document_id'] = null;
        }

        if(isset($event['document_id']) && $event['document_id']) {
            $result['node_type'] = 'document';
        }

        return $result;
    }

}
