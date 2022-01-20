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
                'description'       => 'Readable size',
                'function'          => 'documents\Document::getReadable_size',
                'result_type'       => 'string',
                'store'             => true,
            ],
            'hash'			=> array('type' => 'string'),
            'link' => [
                'type'              => 'computed',
                'description'       => 'URL to visual edior of the module.',
                'function'          => 'documents\Document::getLink',
                'result_type'       => 'string',
                'usage'             => 'uri/url',
                'store'             => true,
            ],
            'categories_ids' => [
                'type'              => 'many2one',
                'foreign_object'    => 'documents\DocumentCategory',
                'description'       => 'Documents that belong to that category'
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
                'description'       => 'Accessibility of the document.',
                'default'           => false,
            ], 
            // 'preview_Image' => [
            //     'type'              => 'computed',
            //     'description'       => 'Image preview',
            //     'function'          => 'documents\Document::getPreview_Image',
            //     'result_type'       => 'string',
            //     'store'             => true,
            // ] 
            
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
    public static function getLink($om, $oids) {
        $res = $om->read(__CLASS__, $oids, ['hash']);
        $result = [];
        foreach($res as $oid=>$ohash) {
            $content = $ohash['hash'];    
            $result[$oid] = '/document/'.$content;
        }
        return $result;
    }
    public static function getPreview_Image($om, $oids) {
        $res = $om->read(__CLASS__, $oids);
        $result = [];
        foreach($res as $oid=>$odoc) {
            $new_width = 15;
            $new_height = 15;
            list($old_width, $old_height) = getimagesize($odoc);

            $new_image = imagecreatetruecolor($new_width, $new_height);
            $old_image = imagecreatefromjpeg($odoc);

            imagecopyresampled($new_image, $old_image, 0, 0, 0, 0, $new_width, $new_height, $old_width, $old_height);

            $result[$oid] = imagejpeg($new_image);
            echo(imagejpeg($new_image));
        }
        return $result; 
    }
}