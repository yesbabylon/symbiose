<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace documents;

use equal\orm\Model;

class DocumentCategory extends Model {

    public static function getColumns() {
        return [
            'name' => [
                'type'              => 'string',
                'description'       => '',
                'required'          => true,
                'multilang'         => true,
                'onupdate'          => 'onupdatePath'
            ],

            'children_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'documents\DocumentCategory',
                'foreign_field'     => 'parent_id'
            ],

            'parent_id' => [
                'type'              => 'many2one',
                'description'       => 'Product Family which current family belongs to, if any.',
                'foreign_object'    => 'documents\DocumentCategory',
                'onupdate'          => 'onupdatePath'
            ],

            'path' => [
                'type'              => 'computed',
                'function'          => 'calcPath',
                'result_type'       => 'string',
                'description'       => 'Full path of the Document',
                'store'             => true,
                'multilang'         => true,
                'readonly'          => true
            ],

            'documents_ids' => [
                'type'              => 'one2many',
                'foreign_field'     => 'category_id',
                'foreign_object'    => 'documents\Document',
                'description'       => 'Product models which current product belongs to the family.'
            ]
        ];
    }


    public static function calcPath($om, $oids, $lang) {
        $result = [];
        $res = $om->read(__CLASS__, $oids, ['name', 'parent_id'], $lang);
        foreach($res as $oid => $odata) {
            if($odata['parent_id']) {
                $paths = self::calcPath($om, (array) $odata['parent_id'], $lang);
                $result[$oid] = $paths[$odata['parent_id']].'/'.$odata['name'];
            }
            else {
                $result[$oid] = $odata['name'];
            }
        }
        return $result;
    }

    public static function onupdatePath($om, $oids, $values, $lang){
        $om->write(__CLASS__, $oids, ['path' => null], $lang);
        $res = $om->read(__CLASS__, $oids, ['children_ids']);

        if($res > 0 && count($res)) {
            foreach($res as $oid => $odata) {
                $om->write('documents\DocumentCategory', $odata['children_ids'], ['path' => null], $lang);
            }
        }
    }
}