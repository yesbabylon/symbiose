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
    public static function _getDefaultImage(){
        return base64_encode('data:image/jpeg;base64,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');
    }
    public static function getPreviewImage($om, $oids) {
        
        $res = $om->read(__CLASS__, $oids, ['type', 'data']);
        $result = [];
        foreach($res as $oid=>$odoc) {

           try{

            
            $file = $odoc['data'];
           
            
            // list($width, $height) = getimagesizefromstring($file);

            $old_image = imagecreatefromstring($file);
            if(!$old_image){ 
                throw new Exception('any');
            }

            $old_x = imageSX($old_image);
            $old_y = imageSY($old_image);

            
            $target_width = 150;
            $target_height = 150;
            $newImage = imagecreatetruecolor($target_width, $target_height);
            if(($old_x/$old_y)<($target_width/$target_height)){
                $hNew = $old_y * $target_width/$old_x;
                $wNew = $target_width;
              }else{
                $hNew = $target_height;
                $wNew = $old_x * $target_height/$old_y;
              }

            
            $wDiff  = round(abs($target_width-$wNew)/0.5);
            $hDiff  = round(abs($target_height-$hNew)/2);
            
            // // imagecreatefromstring() retourne un identifiant d'image représentant l'image obtenue depuis la chaîne data. Le type de l'image sera automatiquement détecté si vous avez compilé PHP avec les supports : JPEG, PNG, GIF, BMP, WBMP, GD2, et WEBP.
            
            imagecopyresized($newImage, $old_image, 0, 0, $wDiff, $hDiff, $wNew, $hNew, $old_x, $old_y);

            ob_start();
            imagejpeg($newImage, null, 75);
            $buffer = ob_get_clean();
            
            $result[$oid] = $buffer;
        }catch(Exception $e){
            
            // $ImageText1Small = imagecreate( 148, 16 );
            // $ImageText1Large = imagecreate( 148, 16 );
            // $ImageText2Small = imagecreate( 308, 40 );
            // $ImageText2Large = imagecreate( 308, 40 );
            // $im = imagecreate($target_width, $target_height);
            // imagecolorallocate($im, 220,220,220);
            // $text_color = imagecolorallocate($im,35,35,35);

            // $backgroundColor1 = imagecolorallocate($ImageText1Small, 255,255,255);
            // $textColor1 = imagecolorallocate($ImageText1Small, 0,0,0);

            // $backgroundColor2 = imagecolorallocate($ImageText2Small, 255,255,255);
            // $textColor2 = imagecolorallocate($ImageText2Small, 0,0,0);

            // imagestring( $ImageText1Small, 1, 1, 0, 'Stack Overflow',  $textColor1 );
            // imagestring( $ImageText2Small, 5, 1, 0, 'Harry Harry Harry',  $textColor2 );

            // imagecopyresampled($ImageText1Large, $ImageText1Small, 0, 0, 0, 0, 148, 16, 74, 8);
            // imagecopyresampled($ImageText2Large, $ImageText2Small, 0, 0, 0, 0, 308, 40, 154, 20);

            // $ImageText1W = imagesx($ImageText1Large);
            // $ImageText1H = imagesy($ImageText1Large);

            // $ImageText2W = imagesx($ImageText2Large);
            // $ImageText2H = imagesy($ImageText2Large);


            // $fw = imagefontwidth(10);     // width of a character
            // $fh = imagefontheight(10);
            // $l = strlen('?');          // number of characters
            // $tw = $l * $fw;
            // $th = $l * $fh;              // text width
            // $iw = imagesx($im);
            // $ih = imagesy($im);          // image width

            // $xpos = ($iw - $tw)/2;
            // $ypos = ($ih-$th)/2;

            // imagecopymerge($im, $ImageText1Large, 35, 20, $xpos, $ypos, $ImageText1W, $ImageText1H, 100);
            // imagecopymerge($im, $ImageText2Large, 20, 20, $xpos, $ypos, $ImageText2W, $ImageText2H, 100);
           

            //Getting the image in the center

            
            // putenv('GDFONTPATH=' . realpath('.'));
            // $fontSrc="arial";
            // $value = imagettftext($im,150,0,10,70,$text_color,'arial.ttf','?');
           
            // imagestring($im, 5, $xpos, $ypos,  "?", $text_color);
            // ob_start();
            // imagejpeg($im, null, 75);
            // $buffer = ob_get_clean(); 
            
            $result[$oid] = self::_getDefaultImage();
        }   
        }
        return $result; 
    }
}