<?php
namespace documents;

use equal\orm\Model;

class Document extends Model {

    public static function getColumns() {
        return array(
            'name'		    => array('type' => 'string'),
            'data'			=> array('type' => 'file', 'onchange' => 'documents\Document::onchangeData'),
            'type'	        => array('type' => 'string'),
            'size'		    => array('type' => 'integer'),
            'readable_size' =>[
                'type'              => 'computed',
                'description'       => "Readable size",
                'function'          => 'documents\Document::getReadable_size',
                'result_type'       => 'string',
                'store'             => true,
            ],
            'hash'			=> array('type' => 'string'),
            'link' => [
                'type'              => 'computed',
                'description'       => "URL to visual edior of the module.",
                'function'          => 'documents\Document::getLink',
                'result_type'       => 'string',
                'usage'             => 'uri/url',
                'store'             => true,
            ],
            'categories_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'documents\DocumentCategory',
                'foreign_field'     => 'documents_ids',
                'description'       => "Documents that belong to that category"
            ],
            'tags_ids' => [
                'type'              => 'many2many',
                'foreign_object'    => 'documents\DocumentTag',
                'foreign_field'     => 'documents_ids',
                'rel_table'         =>'documents_rel_document_tag',
                'rel_foreign_key'   => 'tag_id',
                'rel_local_key'     => 'document_id',
                'description'       => 'List of product models assigned to this tag.'
            ],
            'public' => [
                'type'              => 'boolean',
                'description'       => "Accessibility of the document.",
                'default'           => false,
            ],   
            
        );
    }


    function getReadable_size($om, $oids, $lang)
    {
        $res = $om->read(__CLASS__, $oids, ['size']);
        $precision = 1;
        $suffixes = array('B', 'KB', 'MB', 'GB');   
        $result = [];
        foreach($res as $oid=>$odoc) {
            $content = $odoc['size']; 
            $base = log($content, 1024);   
            $result[$oid] = round(pow(1024, $base - floor($base)), $precision) . ' '. $suffixes[floor($base)];
        }
        return $result;
    }

    public static function onchangeData($om, $oids, $lang) {
        $res = $om->read(__CLASS__, $oids, ['data']);

        foreach($res as $oid => $odata) {
            $content = $odata['data'];
            $size = strlen($content);
           
            // retrieve content_type from MIME
            $finfo = new \finfo(FILEINFO_MIME);
            $content_type = explode(';', $finfo->buffer($content))[0];
            $om->write(__CLASS__, $oid,
                array(
                        'size'		        => $size,
                        'type'		        => $content_type,
                        'hash'              => md5($oid.substr($content, 0, 128)),
                ),
                $lang);
        }
    }
    public static function getLink($om, $oids, $lang) {
        $res = $om->read(__CLASS__, $oids, ['hash']);
        $result = [];
        foreach($res as $oid=>$ohash) {
            $content = $ohash['hash'];    
            $result[$oid] = '/document/'.$content;
        }
        return $result;
    }
}