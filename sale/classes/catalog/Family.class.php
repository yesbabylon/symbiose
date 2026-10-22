<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2024
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

namespace sale\catalog;

use equal\orm\Model;

class Family extends Model {

	public static function getName() {
        return "Product Family";
    }

    public static function getDescription() {
        return 'A Product Family is a group of goods produced under the same brand. Families support hierarchy.';
    }

    public static function getColumns() {
        return [

            'name' => [
                'type'              => 'string',
                'description'       => "Name of the product family (goods under the same brand).",
                'required'          => true,
                'multilang'         => true,
                'unique'            => true,
                'onupdate'          => 'onupdateHierarchy',
                'dependents'        => ['path']
            ],

            'children_ids' => [ 
                'type'              => 'one2many', 
                'foreign_object'    => 'sale\catalog\Family', 
                'foreign_field'     => 'parent_id',
                'description'       => 'Product families that belongs to current family, if any.'
            ],

            'parent_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\catalog\Family',
                'description'       => "Product Family which current family belongs to, if any.",
                'onupdate'          => 'onupdateHierarchy',
                'dependents'        => ['path'],
                'domain'            => ['id', '<>', 'object.id']
            ],

            'path' => [
                'type'              => 'computed',
                'result_type'       => 'string',
                'description'       => 'Full path of the family with ancestors.',
                'store'             => true,
                'function'          => 'calcPath'
            ],

            'product_models_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'sale\catalog\ProductModel',
                'foreign_field'     => 'family_id',
                'description'       => "Product models which current product belongs to the family."
            ]

        ];
    }

    public static function getActions(): array {
        return [
            'refresh_descendant_paths' => [
                'description' => 'Invalidate the stored path of all descendant families.',
                'policies'    => [],
                'function'    => 'doRefreshDescendantPaths'
            ]
        ];
    }

    protected static function onupdateHierarchy($self): void {
        $self->do('refresh_descendant_paths');
    }

    protected static function doRefreshDescendantPaths($self): void {
        $root_ids = $self->ids();
        $pending_ids = $root_ids;
        $visited_ids = array_fill_keys($root_ids, true);
        $descendant_ids = [];

        while(!empty($pending_ids)) {
            $children_ids = self::search(['parent_id', 'in', $pending_ids])->ids();
            $pending_ids = [];

            foreach($children_ids as $child_id) {
                if(isset($visited_ids[$child_id])) {
                    continue;
                }

                $visited_ids[$child_id] = true;
                $descendant_ids[] = $child_id;
                $pending_ids[] = $child_id;
            }
        }

        if(!empty($descendant_ids)) {
            self::ids($descendant_ids)->update(['path' => null]);
        }
    }

    protected static function calcPath($self): array {
        $result = [];
        $self->read(['name', 'parent_id']);
        foreach($self as $id => $family) {
            $result[$id] = self::addParentPath($family['name'], $family['parent_id']);
        }

        return $result;
    }

    public static function addParentPath($path, $parent_id = null) {
        if(is_null($parent_id)) {
            return $path;
        }

        $parent_family = self::id($parent_id)
            ->read(['name', 'parent_id'])
            ->first();

        return self::addParentPath(
            $parent_family['name'].'/'.$path,
            $parent_family['parent_id']
        );
    }

    public static function canupdate($self, $values) {
        if(isset($values['parent_id'])) {
            $value_parent_ids = self::getParentIds($values['parent_id']);

            $self->read(['parent_id']);
            foreach($self as $id => $family) {
                if($values['parent_id'] === $id || in_array($id, $value_parent_ids)) {
                    return ['parent_id' => ['invalid' => 'A family cannot be parent of itself.']];
                }
            }
        }

        if(isset($values['name']) && preg_match('/[#$%^&*()+=\-\[\]\';,.\/{}|":<>?~\\\\]/', $values['name'])) {
            return ['name' => ['invalid' => 'Special characters not allowed in name.']];
        }

        return parent::canupdate($self, $values);
    }

    public static function getParentIds($id, $ids = []) {
        $family = self::id($id)
            ->read(['parent_id'])
            ->first();

        if(!is_null($family['parent_id'])) {
            return self::getParentIds(
                $family['parent_id'],
                array_merge([$family['parent_id']], $ids)
            );
        }

        return $ids;
    }
}
