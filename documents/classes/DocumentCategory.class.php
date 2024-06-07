<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2024
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

namespace documents;

use equal\orm\Model;

class DocumentCategory extends Model {

    public static function getColumns() {
        return [

            'name' => [
                'type'              => 'string',
                'description'       => 'Name of the document category.',
                'required'          => true,
                'multilang'         => true,
                'unique'            => true,
                'dependents'        => ['path']
            ],

            'children_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'documents\DocumentCategory',
                'foreign_field'     => 'parent_id'
            ],

            'parent_id' => [
                'type'              => 'many2one',
                'description'       => 'Document category which current category belongs to, if any.',
                'foreign_object'    => 'documents\DocumentCategory',
                'dependents'        => ['path'],
                'domain'            => ['id', '<>', 'object.id']
            ],

            'path' => [
                'type'              => 'computed',
                'result_type'       => 'string',
                'description'       => 'Full path of the category.',
                'store'             => true,
                'function'          => 'calcPath',
                'dependents'        => ['children_ids' => ['path']]
            ],

            'documents_ids' => [
                'type'              => 'one2many',
                'foreign_field'     => 'category_id',
                'foreign_object'    => 'documents\Document',
                'description'       => 'Documents assigned to this category.'
            ]

        ];
    }

    public static function calcPath($self): array {
        $result = [];
        $self->read(['name', 'parent_id']);
        foreach($self as $id => $category) {
            $result[$id] = self::addParentPath($category['name'], $category['parent_id']);
        }

        return $result;
    }

    public static function addParentPath($path, $parent_id = null) {
        if(is_null($parent_id)) {
            return $path;
        }

        $parent_category = self::id($parent_id)
            ->read(['name', 'parent_id'])
            ->first();

        return self::addParentPath(
            $parent_category['name'].'/'.$path,
            $parent_category['parent_id']
        );
    }

    public static function canupdate($self, $values) {
        if(isset($values['parent_id'])) {
            $value_parent_ids = self::getParentIds($values['parent_id']);

            $self->read(['parent_id']);
            foreach($self as $id => $category) {
                if($values['parent_id'] === $id || in_array($id, $value_parent_ids)) {
                    return ['parent_id' => ['invalid' => 'A category cannot be parent of itself.']];
                }
            }
        }

        if(isset($values['name']) && preg_match('/[#$%^&*()+=\-\[\]\';,.\/{}|":<>?~\\\\]/', $values['name'])) {
            return ['name' => ['invalid' => 'Special characters not allowed in name.']];
        }

        return parent::canupdate($self, $values);
    }

    public static function getParentIds($id, $ids = []) {
        $category = self::id($id)
            ->read(['parent_id'])
            ->first();

        if(!is_null($category['parent_id'])) {
            return self::getParentIds(
                $category['parent_id'],
                array_merge([$category['parent_id']], $ids)
            );
        }

        return $ids;
    }
}
