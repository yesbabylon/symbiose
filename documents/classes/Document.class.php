<?php
namespace documents;

use BadFunctionCallException;
use equal\orm\Model;
use Exception;

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
            'preview_image' => [
                'type'              => 'computed',
                'function'          => 'documents\Document::getPreviewImage',
                'description'       => 'Image preview',
                'result_type'       => 'binary',
                'usage'             => 'image/jpeg',
                'store'             => true
            ]  
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
    public static function getPreviewImage($om, $oids) {
        
        $res = $om->read(__CLASS__, $oids, ['type', 'data']);
        $result = [];
        foreach($res as $oid=>$odoc) {

           try{

            
            $file = $odoc['data'];
            $target_width = 150;
            $target_height = 150;
            list($width, $height) = getimagesizefromstring($file);

            $new_image = imagecreatetruecolor($target_width, $target_height);
            $old_image = imagecreatefromstring($file);
            if(!$old_image){ 
                throw new Exception('any');
            }
            
            // imagecreatefromstring() retourne un identifiant d'image représentant l'image obtenue depuis la chaîne data. Le type de l'image sera automatiquement détecté si vous avez compilé PHP avec les supports : JPEG, PNG, GIF, BMP, WBMP, GD2, et WEBP.

            imagecopyresampled($new_image, $old_image, 0, 0, 0, 0, $target_width, $target_height, $width, $height);

            ob_start();
            imagejpeg($new_image, null, 75);
            $buffer = ob_get_clean();
            
            $result[$oid] = $buffer;
        }catch(Exception $e){
            
            $im = imagecreate($target_width, $target_height);
            imagecolorallocate($im, 220,220,220);
            $text_color = imagecolorallocate($im,35,35,35);

            //Getting the image in the center

            $fw = imagefontwidth(5);     // width of a character
            $fh = imagefontheight(5);
            $l = strlen('?');          // number of characters
            $tw = $l * $fw;
            $th = $l * $fh;              // text width
            $iw = imagesx($im);
            $ih = imagesy($im);          // image width

            $xpos = ($iw - $tw)/2;
            $ypos = ($ih-$th)/2;
            // putenv('GDFONTPATH=' . realpath('.'));
            // $fontSrc="arial";
            // imagettftext($im,150,15,$xpos,$ypos,$text_color,$fontSrc,'?');
            imagestring($im, 5, $xpos, $ypos,  "?", $text_color);
            ob_start();
            imagejpeg($im, null, 75);
            $buffer = ob_get_clean(); 
            $result[$oid] = $buffer; 
        }   
        }
        return $result; 
    }
}