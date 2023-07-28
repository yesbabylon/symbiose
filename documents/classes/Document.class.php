<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace documents;

use equal\orm\Model;
use Exception;

class Document extends Model {

    public static function getLink() {
        return "/documents/#/document/object.id";
    }

    public static function getColumns() {
        return [
            'name' => [
                'type'              => 'string',
                'required'          => true
            ],

            'data' => [
                'type'              => 'binary',
                'onupdate'          => 'onupdateData'
            ],

            'type' => [
                'type'              => 'string',
                'readonly'          => true,
                'description'        => 'Content type of the document (from data).'
            ],

            'size'		    => [
                'type'              => 'integer',
                'readonly'          => true,
                'description'        => 'Size of the document, in octets (from data).'
            ],

            'readable_size' => [
                'type'              => 'computed',
                'description'       => 'Readable size',
                'function'          => 'calcReadableSize',
                'result_type'       => 'string',
                'store'             => true,
                'readonly'          => true
            ],

            'hash' => [
                'type'              => 'string',
                'readonly'          => true,
                'default'           => ''
            ],

            'link' => [
                'type'              => 'computed',
                'result_type'       => 'string',
                'usage'             => 'uri/url',
                'description'       => 'Direct URL to the document (uses hash).',
                'function'          => 'calcLink',
                'store'             => true,
                'readonly'          => true
            ],

            'category_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'documents\DocumentCategory',
                'description'       => 'Category of the document.'
            ],

            'tags_ids' => [
                'type'              => 'many2many',
                'foreign_object'    => 'documents\DocumentTag',
                'foreign_field'     => 'documents_ids',
                'rel_table'         => 'documents_rel_document_tag',
                'rel_foreign_key'   => 'tag_id',
                'rel_local_key'     => 'document_id',
                'description'       => 'List of product models assigned to this tag.'
            ],

            'public' => [
                'type'              => 'boolean',
                'description'       => 'Accessibility of the document.',
                'default'           => false
            ],

            'preview_image' => [
                'type'              => 'computed',
                'result_type'       => 'binary',
                'usage'             => 'image/jpeg',
                'function'          => 'calcPreviewImage',
                'description'       => 'Thumbnail of the document.',
                'store'             => true
            ],

            'template_attachments_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'communication\TemplateAttachment',
                'foreign_field'     => 'document_id',
                'description'       => "The links between document and templates."
            ]

        ];
    }

    function calcReadableSize($om, $oids, $lang) {
        $res = $om->read(__CLASS__, $oids, ['size']);
        $precision = 1;
        $suffixes = array('B', 'KB', 'MB', 'GB');
        $result = [];
        foreach($res as $oid => $odoc) {
            $content = $odoc['size'];
            $base = log($content, 1024);
            $result[$oid] = round(pow(1024, $base - floor($base)), $precision) . ' '. $suffixes[floor($base)];
        }
        return $result;
    }

    public static function calcLink($om, $oids) {
        $res = $om->read(__CLASS__, $oids, ['hash']);

        $result = [];
        foreach($res as $oid => $odata) {
            $result[$oid] = '/document/'.$odata['hash'];
        }

        return $result;
    }

    public static function onupdateData($om, $oids, $values, $lang) {
        $res = $om->read(self::getType(), $oids, ['hash', 'data']);

        foreach($res as $oid => $odata) {
            $content = $odata['data'];
            $size = strlen($content);

            // retrieve content_type from MIME
            $finfo = new \finfo(FILEINFO_MIME);
            $content_type = explode(';', $finfo->buffer($content))[0];
            $om->update(self::getType(), $oid, [
                'size'  => $size,
                'type'	=> $content_type
            ]);

            // set hash if not assigned yet
            if(strlen($odata['hash']) <= 0) {
                $om->update(self::getType(), $oid, ['hash'=> md5($oid.substr($content, 0, 128))]);
            }
        }
        // reset preview image
        $om->update(self::getType(), $oids, ['preview_image' => null, 'readable_size' => null]);
    }

    /**
     * Retrieve and validate an extension from a content type and a filename.
     *
     * @param $content_type string  The content_type found for for the file.
     * @param $name string  (optional) The name of the file, if any.
     *
     * @return string | bool    In case of success, the extesnion is return. If no extension matches the content type, it returns false.
     */
    public static function _getExtensionFromType($content_type, $name = '') {

        static $extension_map = [
            '3g2'   =>	'video/3gpp2',
            '3gp'   =>	['video/3gp', 'video/3gpp'],
            '7z'	=>	['application/x-7z-compressed', 'application/x-compressed', 'application/x-zip-compressed', 'application/zip', 'multipart/x-zip'],
            '7zip'	=>	['application/x-7z-compressed', 'application/x-compressed', 'application/x-zip-compressed', 'application/zip', 'multipart/x-zip'],
            'aac'   =>	['audio/x-aac', 'audio/aac'],
            'ac3'   =>	'audio/ac3',
            'ai'	=>	['application/pdf', 'application/postscript', 'application/vnd.adobe.illustrator'],
            'aif'	=>	['audio/x-aiff', 'audio/aiff'],
            'aifc'	=>	'audio/x-aiff',
            'aiff'	=>	['audio/x-aiff', 'audio/aiff'],
            'au'    =>	'audio/x-au',
            'avi'	=>	['video/x-msvideo', 'video/msvideo', 'video/avi', 'application/x-troff-msvideo'],
            'bin'	=>	['application/macbinary', 'application/mac-binary', 'application/octet-stream', 'application/x-binary', 'application/x-macbinary'],
            'bmp'	=>	['image/bmp', 'image/x-bmp', 'image/x-bitmap', 'image/x-xbitmap', 'image/x-win-bitmap', 'image/x-windows-bmp', 'image/ms-bmp', 'image/x-ms-bmp', 'application/bmp', 'application/x-bmp', 'application/x-win-bitmap'],
            'cdr'	=>	['application/cdr', 'application/coreldraw', 'application/x-cdr', 'application/x-coreldraw', 'image/cdr', 'image/x-cdr', 'zz-application/zz-winassoc-cdr'],
            'cer'   =>	['application/pkix-cert', 'application/x-x509-ca-cert'],
            'class'	=>	'application/octet-stream',
            'cpt'	=>	'application/mac-compactpro',
            'crl'   =>	['application/pkix-crl', 'application/pkcs-crl'],
            'crt'   =>	['application/x-x509-ca-cert', 'application/x-x509-user-cert', 'application/pkix-cert'],
            'csr'   =>	'application/octet-stream',
            'css'	=>	['text/css', 'text/plain'],
            'csv'	=>	['text/x-comma-separated-values', 'text/comma-separated-values', 'application/octet-stream', 'application/vnd.ms-excel', 'application/x-csv', 'text/x-csv', 'text/csv', 'application/csv', 'application/excel', 'application/vnd.msexcel', 'text/plain'],
            'dcr'	=>	'application/x-director',
            'der'   =>	'application/x-x509-ca-cert',
            'dir'	=>	'application/x-director',
            'dll'	=>	'application/octet-stream',
            'dms'	=>	'application/octet-stream',
            'doc'	=>	['application/msword', 'application/vnd.ms-office'],
            'docx'	=>	['application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/zip', 'application/msword', 'application/x-zip'],
            'dot'	=>	['application/msword', 'application/vnd.ms-office'],
            'dotx'	=>	['application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/zip', 'application/msword'],
            'dvi'	=>	'application/x-dvi',
            'dxr'	=>	'application/x-director',
            'eml'	=>	'message/rfc822',
            'eps'	=>	'application/postscript',
            'exe'	=>	['application/octet-stream', 'application/x-msdownload'],
            'f4v'   =>	['video/mp4', 'video/x-f4v'],
            'flac'  =>	'audio/x-flac',
            'flv'	=>	'video/x-flv',
            'gif'	=>	'image/gif',
            'gpg'   =>	'application/gpg-keys',
            'gtar'	=>	'application/x-gtar',
            'gz'	=>	'application/x-gzip',
            'gzip'  =>	'application/x-gzip',
            'heic' 	=>	'image/heic',
            'heif' 	=>	'image/heif',
            'hqx'	=>	['application/mac-binhex40', 'application/mac-binhex', 'application/x-binhex40', 'application/x-mac-binhex40'],
            'htm'	=>	['text/html', 'text/plain'],
            'html'	=>	['text/html', 'text/plain'],
            'ical'	=>	'text/calendar',
            'ico'	=>	['image/x-icon', 'image/x-ico', 'image/vnd.microsoft.icon'],
            'ics'	=>	'text/calendar',
            'j2k'	=>	['image/jp2', 'video/mj2', 'image/jpx', 'image/jpm'],
            'jar'	=>	['application/java-archive', 'application/x-java-application', 'application/x-jar', 'application/x-compressed'],
            'jp2'	=>	['image/jp2', 'video/mj2', 'image/jpx', 'image/jpm'],
            'jpe'	=>	['image/jpeg', 'image/pjpeg'],
            'jpeg'	=>	['image/jpeg', 'image/pjpeg'],
            'jpf'	=>	['image/jp2', 'video/mj2', 'image/jpx', 'image/jpm'],
            'jpg'	=>	['image/jpeg', 'image/pjpeg'],
            'jpg2'	=>	['image/jp2', 'video/mj2', 'image/jpx', 'image/jpm'],
            'jpm'	=>	['image/jp2', 'video/mj2', 'image/jpx', 'image/jpm'],
            'jpx'	=>	['image/jp2', 'video/mj2', 'image/jpx', 'image/jpm'],
            'js'	=>	['application/x-javascript', 'text/plain'],
            'json'  =>	['application/json', 'text/json'],
            'kdb'   =>	'application/octet-stream',
            'kml'	=>	['application/vnd.google-earth.kml+xml', 'application/xml', 'text/xml'],
            'kmz'	=>	['application/vnd.google-earth.kmz', 'application/zip', 'application/x-zip'],
            'lha'	=>	'application/octet-stream',
            'log'	=>	['text/plain', 'text/x-log'],
            'lzh'	=>	'application/octet-stream',
            'm3u'   =>	'text/plain',
            'm4a'   =>	'audio/x-m4a',
            'm4u'   =>	'application/vnd.mpegurl',
            'mid'	=>	'audio/midi',
            'midi'	=>	'audio/midi',
            'mif'	=>	'application/vnd.mif',
            'mj2'	=>	['image/jp2', 'video/mj2', 'image/jpx', 'image/jpm'],
            'mjp2'	=>	['image/jp2', 'video/mj2', 'image/jpx', 'image/jpm'],
            'mov'	=>	'video/quicktime',
            'movie'	=>	'video/x-sgi-movie',
            'mp2'	=>	'audio/mpeg',
            'mp3'	=>	['audio/mpeg', 'audio/mpg', 'audio/mpeg3', 'audio/mp3'],
            'mp4'   =>	'video/mp4',
            'mpe'	=>	'video/mpeg',
            'mpeg'	=>	'video/mpeg',
            'mpg'	=>	'video/mpeg',
            'mpga'	=>	'audio/mpeg',
            'oda'	=>	'application/oda',
            'odc'	=>	'application/vnd.oasis.opendocument.chart',
            'odf'	=>	'application/vnd.oasis.opendocument.formula',
            'odg'	=>	'application/vnd.oasis.opendocument.graphics',
            'odi'	=>	'application/vnd.oasis.opendocument.image',
            'odm'	=>	'application/vnd.oasis.opendocument.text-master',
            'odp'	=>	'application/vnd.oasis.opendocument.presentation',
            'ods'	=>	'application/vnd.oasis.opendocument.spreadsheet',
            'odt'	=>	'application/vnd.oasis.opendocument.text',
            'ogg'   =>	['audio/ogg', 'video/ogg', 'application/ogg'],
            'otc'	=>	'application/vnd.oasis.opendocument.chart-template',
            'otf'	=>	'application/vnd.oasis.opendocument.formula-template',
            'otg'	=>	'application/vnd.oasis.opendocument.graphics-template',
            'oth'	=>	'application/vnd.oasis.opendocument.text-web',
            'oti'	=>	'application/vnd.oasis.opendocument.image-template',
            'otp'	=>	'application/vnd.oasis.opendocument.presentation-template',
            'ots'	=>	'application/vnd.oasis.opendocument.spreadsheet-template',
            'ott'	=>	'application/vnd.oasis.opendocument.text-template',
            'p10'   =>	['application/x-pkcs10', 'application/pkcs10'],
            'p12'   =>	'application/x-pkcs12',
            'p7a'   =>	'application/x-pkcs7-signature',
            'p7c'   =>	['application/pkcs7-mime', 'application/x-pkcs7-mime'],
            'p7m'   =>	['application/pkcs7-mime', 'application/x-pkcs7-mime'],
            'p7r'   =>	'application/x-pkcs7-certreqresp',
            'p7s'   =>	'application/pkcs7-signature',
            'pdf'	=>	['application/pdf', 'application/force-download', 'application/x-download', 'binary/octet-stream'],
            'pem'   =>	['application/x-x509-user-cert', 'application/x-pem-file', 'application/octet-stream'],
            'pgp'   =>	'application/pgp',
            'php'	=>	['application/x-httpd-php', 'application/php', 'application/x-php', 'text/php', 'text/x-php', 'application/x-httpd-php-source'],
            'php3'	=>	'application/x-httpd-php',
            'php4'	=>	'application/x-httpd-php',
            'phps'	=>	'application/x-httpd-php-source',
            'phtml'	=>	'application/x-httpd-php',
            'png'	=>	['image/png', 'image/x-png'],
            'ppt'	=>	['application/powerpoint', 'application/vnd.ms-powerpoint', 'application/vnd.ms-office', 'application/msword'],
            'pptx'	=> 	['application/vnd.openxmlformats-officedocument.presentationml.presentation', 'application/x-zip', 'application/zip'],
            'ps'	=>	'application/postscript',
            'psd'	=>	['application/x-photoshop', 'image/vnd.adobe.photoshop'],
            'qt'	=>	'video/quicktime',
            'ra'	=>	'audio/x-realaudio',
            'ram'	=>	'audio/x-pn-realaudio',
            'rar'	=>	['application/x-rar', 'application/rar', 'application/x-rar-compressed'],
            'rm'	=>	'audio/x-pn-realaudio',
            'rpm'	=>	'audio/x-pn-realaudio-plugin',
            'rsa'   =>	'application/x-pkcs7',
            'rtf'	=>	'text/rtf',
            'rtx'	=>	'text/richtext',
            'rv'	=>	'video/vnd.rn-realvideo',
            'sea'	=>	'application/octet-stream',
            'shtml'	=>	['text/html', 'text/plain'],
            'sit'	=>	'application/x-stuffit',
            'smi'	=>	'application/smil',
            'smil'	=>	'application/smil',
            'so'	=>	'application/octet-stream',
            'srt'	=>	['text/srt', 'text/plain'],
            'sst'   =>	'application/octet-stream',
            'svg'	=>	['image/svg+xml', 'image/svg', 'application/xml', 'text/xml'],
            'swf'	=>	'application/x-shockwave-flash',
            'tar'	=>	'application/x-tar',
            'text'	=>	'text/plain',
            'tgz'	=>	['application/x-tar', 'application/x-gzip-compressed'],
            'tif'	=>	'image/tiff',
            'tiff'	=>	'image/tiff',
            'txt'	=>	'text/plain',
            'vcf'	=>	'text/x-vcard',
            'vlc'   =>	'application/videolan',
            'vtt'	=>	['text/vtt', 'text/plain'],
            'wav'	=>	['audio/x-wav', 'audio/wave', 'audio/wav'],
            'wbxml'	=>	'application/wbxml',
            'webm'	=>	'video/webm',
            'wma'	=>	['audio/x-ms-wma', 'video/x-ms-asf'],
            'wmlc'	=>	'application/wmlc',
            'wmv'   =>	['video/x-ms-wmv', 'video/x-ms-asf'],
            'word'	=>	['application/msword', 'application/octet-stream'],
            'xht'	=>	'application/xhtml+xml',
            'xhtml'	=>	'application/xhtml+xml',
            'xl'	=>	'application/excel',
            'xls'	=>	['application/vnd.ms-excel', 'application/msexcel', 'application/x-msexcel', 'application/x-ms-excel', 'application/x-excel', 'application/x-dos_ms_excel', 'application/xls', 'application/x-xls', 'application/excel', 'application/download', 'application/vnd.ms-office', 'application/msword'],
            'xlsx'	=>	['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/vnd.ms-excel', 'application/msword', 'application/zip', 'application/x-zip'],
            'xml'	=>	['application/xml', 'text/xml', 'text/plain'],
            'xsl'	=>	['application/xml', 'text/xsl', 'text/xml'],
            'xspf'  =>	'application/xspf+xml',
            'z'	    =>	'application/x-compress',
            'zip'	=>	['application/x-zip', 'application/zip', 'application/x-zip-compressed', 'application/s-compressed', 'multipart/x-zip'],
            'zsh'	=>	'text/x-scriptzsh'
        ];

        // if we have a name holding an extension, check if the given content_type is amongst the valid MIME of the map
        if(strlen($name)) {
            $extension = strtolower( ( ($n = strrpos($name, ".")) === false) ? "" : substr($name, $n+1) );
            if(strlen($extension)) {
                if(isset($extension_map[$extension])) {
                    $map = (array) $extension_map[$extension];
                    foreach($map as $mime) {
                        if($mime == $content_type) {
                            return $extension;
                        }
                    }
                }
            }
        }
        // find the first extension that matches the given content_type
        foreach($extension_map as $extension => $map) {
            $map = (array) $map;
            foreach($map as $mime) {
                if($mime == $content_type) {
                    return $extension;
                }
            }
        }
        return false;
    }

    /**
     * Generate the preview image.
     *
     * By convention, generated thumbnail is always a JPEG image.
     *
     */
    public static function calcPreviewImage($om, $oids) {

        $res = $om->read(__CLASS__, $oids, ['name', 'type', 'data']);
        $result = [];

        foreach($res as $oid => $odoc) {

            try {

                if(substr($odoc['type'], 0, 5) != 'image') {
                    throw new Exception('not an image');
                }

                $parts = explode('/', $odoc['type']);

                if(count($parts) < 2) {
                    throw new Exception('invalid content type');
                }

                $image_type = $parts[1];

                if(!in_array($image_type, ['bmp', 'png', 'gif', 'jpeg', 'webp'])) {
                    throw new Exception('non supported image format');
                }

                $src_image = imagecreatefromstring($odoc['data']);

                if(!$src_image) {
                    throw new Exception();
                }

                $src_width = imageSX($src_image);
                $src_height = imageSY($src_image);

                $target_width = 150;
                $target_height = 150;

                $dst_image = imagecreatetruecolor($target_width, $target_height);

                if( ($src_width/$src_height) < ($target_width/$target_height) ){
                    $new_height = $src_height * $target_width / $src_width;
                    $new_width = $target_width;
                }
                else {
                    $new_height = $target_height;
                    $new_width = $src_width * $target_height/$src_height;
                }

                $min = min($src_width, $src_height);

                $offset_x  = round( ($src_width - $min) / 2 );
                $offset_y  = round( ($src_height - $min) / 2 );

                imagecopyresized($dst_image, $src_image, 0, 0, $offset_x, $offset_y, $new_width, $new_height, $src_width, $src_height);

                // get binary value of generated image
                ob_start();
                imagejpeg($dst_image, null, 80);
                $buffer = ob_get_clean();

                $result[$oid] = $buffer;
            }
            catch(Exception $e){
                // unknown image type : fallback to hardcoded default thumbnail
                $result[$oid] = base64_decode('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');

                $extension = self::_getExtensionFromType($odoc['type'], $odoc['name']);
                if($extension) {
                    $filename = QN_BASEDIR.'/packages/documents/assets/img/extensions/'.$extension.'.jpg';
                    if(!is_file($filename)) {
                        // non-resolved extension
                        $filename = QN_BASEDIR.'/packages/documents/assets/img/extensions/unknown.jpg';
                    }
                    if(is_file($filename)) {
                        $result[$oid] =  file_get_contents($filename);
                    }
                }
            }
        }
        return $result;
    }
}