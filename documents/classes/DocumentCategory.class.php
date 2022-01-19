<?php
namespace documents;

use equal\orm\Model;

class DocumentCategory extends Model {

    public static function getColumns() {
        return array(
                        'name' => [
                            'type'              => 'string',
                            'description'       => "",
                            'required'          => true,
                            'multilang'         => true
                            ],
                        'children_ids' => [ 
                            'type'              => 'one2many', 
                            'foreign_object'    => 'documents\DocumentCategory', 
                            'foreign_field'     => 'parent_id'
                        ],
                        'parent_id' => [
                            'type'              => 'many2one',
                            'description'       => "Product Family which current family belongs to, if any.",
                            'foreign_object'    => 'documents\DocumentCategory'
                        ],
                        'path' => [
                            'type'              => 'computed',
                            'function'          => 'documents\DocumentCategory::getPath',
                            'result_type'       => 'string',
                            'store'             =>  false,
                            'description'       => 'Full path of the Document'
                        ],
                        'documents_ids' => [
                            'type'              => 'many2one',
                            'foreign_object'    => 'documents\Document',
                            'description'       => "Product models which current product belongs to the family."
                        ]
                    );
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