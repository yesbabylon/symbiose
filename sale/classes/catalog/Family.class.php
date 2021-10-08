<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace sale\catalog;
use equal\orm\Model;

class Family extends Model {

	public static function getName() {
        return "Product Family";
    }

    public static function getColumns() {
        /**
         * A Product Family is a group of goods produced under the same brand.
         * Families support hierarchy.
         */

        return [
            'name' => [
                'type'              => 'string',
                'description'       => "Name of the product family. A family is a group of goods produced under the same brand.",
                'required'          => true,
                'multilang'         => true
            ],

            'children_ids' => [ 
                'type'              => 'one2many', 
                'foreign_object'    => 'sale\catalog\Family', 
                'foreign_field'     => 'parent_id'
            ],

            'parent_id' => [
                'type'              => 'many2one',
                'description'       => "Product Family which current family belongs to, if any.",
                'foreign_object'    => 'sale\catalog\Family'
            ],

            'path' => [
                'type'              => 'computed',
                'function'          => 'sale\catalog\Family::getPath',
                'result_type'       => 'string',
                'store'             => true,
                'description'       => 'Full path of the family with ancestors.'
            ],

            'product_models_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'sale\catalog\ProductModel',
                'foreign_field'     => 'family_id',
                'description'       => "Product models which current product belongs to the family."
            ]

        ];
    }

    public static function getPath($om, $oids, $lang) {
        $result = [];
        $res = $om->read(__CLASS__, $oids, ['name', 'parent_id']);
        foreach($res as $oid => $odata) {
            if($odata['parent_id']) {
                $paths = self::getPath($om, (array) $odata['parent_id'], $lang);
                $result[$oid] = $paths[$odata['parent_id']].'/'.$odata['name'];
            }
            else {
                $result[$oid] = $odata['name'];
            }
        }
        return $result;
    }    
}