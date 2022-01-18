<?php
namespace documents;

use equal\orm\Model;

class Document extends Model {

    public static function getColumns() {
        return array(
            'name'		    => array('type' => 'string'),
            'data'			=> array('type' => 'file', 'onchange' => 'documents\Document::onchangeContent'),
            'type'	        => array('type' => 'string'),
            'size'		    => array('type' => 'integer'),
            'readable_size' =>[
                'type'              => 'computed',
                'description'       => "Readable size",
                'function'          => 'documents\Document::formatBytes',
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
                'foreign_field'     => 'categories_ids',
                'foreign_object'    => 'documents\DocumentCategory',
                'description'       => "Product models which current product belongs to the family."
            ],
            'tag_ids' => [
                'type'              => 'many2many',
                'foreign_object'    => 'documents\Document',
                'foreign_field'     => 'document_ids',
                'rel_table'         =>'documents_rel_documents_tag',
                'rel_foreign_key'   => 'tag_id',
                'rel_local_key'     => 'document_id',
                'description'       => 'List of product models assigned to this tag.'
            ],
            
        );
    }


    function formatBytes($om, $oids, $lang)
    {
        $res = $om->read(__CLASS__, $oids, ['size']);
        $precision = 1;
        $suffixes = array('B', 'KB', 'MB', 'GB');   
        $result = [];
        foreach($res as $oid=>$osize) {
            $content = $osize['size']; 
            $base = log($content, 1024);   
            $result[$oid] = round(pow(1024, $base - floor($base)), $precision) . ' '. $suffixes[floor($base)];
        }
        return $result;
    }

    public static function onchangeContent($om, $oids, $lang) {
        $res = $om->read(__CLASS__, $oids, ['data']);

        foreach($res as $oid => $odata) {
            $content = $odata['data'];
            $size = strlen($content);
            // $readable_size = Document::formatBytes($size);
            // retrieve content_type from MIME
            $finfo = new \finfo(FILEINFO_MIME);
            $content_type = explode(';', $finfo->buffer($content))[0];
            $om->write(__CLASS__, $oid,
                array(
                        'size'		        => $size,
                        'type'		        => $content_type,
                        'hash'              => md5($oid.substr($content, 0, 128)),
                        'readable_size'     => $readable_size
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