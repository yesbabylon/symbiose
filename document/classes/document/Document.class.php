<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2024
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

namespace document\document;

use document\navigation\Node;
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
                'required'          => true,
                'onupdate'          => 'onupdateName'
            ],

            'data' => [
                'type'              => 'binary',
                'dependents'        => ['hash', 'hash_sha256', 'content_type', 'content_size', 'extension', 'readable_size', 'preview_image']
            ],

            'content_type' => [
                'type'              => 'computed',
                'result_type'       => 'string',
                'function'          => 'calcContentType',
                'store'             => true,
                'instant'           => true,
                'description'       => 'Content type of the document (from data).'
            ],

            'type' => [
                'type'              => 'alias',
                'alias'             => 'content_type'
            ],

            'content_size' => [
                'type'              => 'computed',
                'result_type'       => 'integer',
                'function'          => 'calcContentSize',
                'store'             => true,
                'instant'           => true,
                'dependents'        => ['readable_size'],
                'description'       => 'Size of the document, in bytes (from data).'
            ],

            'size' => [
                'type'              => 'alias',
                'alias'             => 'content_size'
            ],

            'extension' => [
                'type'              => 'computed',
                'result_type'       => 'string',
                'function'          => 'calcExtension',
                'store'             => true,
                'instant'           => true,
                'description'       => 'Filename extension of the document (from data).'
            ],

            'readable_size' => [
                'type'              => 'computed',
                'description'       => 'Human-readable document size.',
                'function'          => 'calcReadableSize',
                'result_type'       => 'string',
                'store'             => true,
                'instant'           => true,
                'readonly'          => true
            ],

            'hash' => [
                'type'              => 'computed',
                'result_type'       => 'string',
                'usage'             => 'text/plain:32',
                'store'             => true,
                'instant'           => true,
                'dependents'        => ['link'],
                'function'          => 'calcHash',
                'description'       => 'MD5 hash used to retrieve the document.',
                'help'              => 'Includes the document identifier so documents with identical content remain individually addressable.'
            ],

            'hash_sha256' => [
                'type'              => 'computed',
                'result_type'       => 'string',
                'usage'             => 'text/plain:64',
                'function'          => 'calcHashSha256',
                'description'       => 'SHA-256 hash of the exact binary content.',
                'help'              => 'Intended for document identification and future digital-signature support.',
                'store'             => true,
                'instant'           => true,
                'readonly'          => true
            ],

            'link' => [
                'type'              => 'computed',
                'result_type'       => 'string',
                'usage'             => 'uri/url',
                'description'       => 'URL for visualizing the document.',
                'function'          => 'calcLink',
                'store'             => true,
                'readonly'          => true
            ],

            'lang_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'core\Lang',
                'description'       => 'Language used in the document.',
                'default'           => 1
            ],

            'category_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'document\DocumentCategory',
                'description'       => 'Category of the document.',
                'default'           => 1
            ],

            'tags_ids' => [
                'type'              => 'many2many',
                'foreign_object'    => 'document\document\DocumentTag',
                'foreign_field'     => 'documents_ids',
                'rel_table'         => 'document_rel_document_tag',
                'rel_foreign_key'   => 'tag_id',
                'rel_local_key'     => 'document_id',
                'description'       => 'Tags of the document.'
            ],

            'tags' => [
                'type'              => 'computed',
                'result_type'       => 'string',
                'description'       => 'Short tags listing, max 50 characters.',
                'store'             => false,
                'function'          => 'calcTags',
                'readonly'          => true
            ],

            'parent_node_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'document\navigation\Node',
                'description'       => 'Folder in which the document node is placed.',
                'help'              => 'Assigning a folder creates or moves the navigation node associated with the document.',
                'domain'            => [['node_type', '=', 'folder']],
                'ondelete'          => 'null',
                'onupdate'          => 'onupdateParentNodeId'
            ],

            'node_id' => [
                'type'              => 'computed',
                'result_type'       => 'many2one',
                'foreign_object'    => 'document\navigation\Node',
                'description'       => 'Navigation node associated with the document.',
                'help'              => 'The node is managed automatically from the selected parent folder.',
                'function'          => 'calcNodeId',
                'readonly'          => true,
                'domain'            => [['node_type', '=', 'document']]
            ],

            'document_visibility' => [
                'type'              => 'string',
                'selection'         => [
                    'organization',
                    'external',
                    'public'
                ],
                'default'           => 'organization',
                'description'       => 'Defines who can access the document.',
                'help'              => 'This field is synchronized with related document nodes. Organization and external documents require authenticated access; public documents are accessible without authentication.',
                'onupdate'          => 'onupdateDocumentVisibility'
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
                'foreign_object'    => 'communication\template\TemplateAttachment',
                'foreign_field'     => 'document_id',
                'description'       => "The links between document and templates."
            ],

            'assignments_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'document\DocumentRoleAssignment',
                'foreign_field'     => 'object_id',
                'domain'            => [ ['object_class', '=', 'document\document\Document'], ['object_id', '=', 'object.id'] ],
                'description'       => "Links between document and related roles assignments."
            ]
        ];
    }

    public static function getActions(): array {
        return array_merge(parent::getActions(), [
            'sync_node' => [
                'description'   => 'Create or update document nodes from their parent folder.',
                'policies'      => [],
                'function'      => 'doSyncNode'
            ],
            'sync_node_name' => [
                'description'   => 'Synchronize associated node names with document names.',
                'policies'      => [],
                'function'      => 'doSyncNodeName'
            ],
            'sync_node_visibility' => [
                'description'   => 'Synchronize related nodes with their document visibility.',
                'policies'      => [],
                'function'      => 'doSyncNodeVisibility'
            ]
        ]);
    }

    public function getIndexes(): array {
        return [
            ['hash'],
            ['hash_sha256']
        ];
    }

    public static function getRoles() {
        return [
            "owner" => [
                "description" => "Creator of the document. Has full control over it.",
                "rights" => EQ_R_READ | EQ_R_WRITE | EQ_R_DELETE | EQ_R_MANAGE
            ],
            "editor" => [
                "description" => "Editor of the document. Has read and write access on it. Cannot remove it.",
                "rights" => EQ_R_READ | EQ_R_WRITE,
                "implied_by" => ['owner']
            ],
            "viewer" => [
                "description" => "Viewer of the document. Cannot edit the content.",
                "rights" => EQ_R_READ,
                "implied_by" => ['editor']
            ]
        ];
    }

    protected static function onaftercreate($self) {
        $self->do('sync_node');
    }

    protected static function onupdateName($self) {
        $self->do('sync_node_name');
    }

    protected static function doSyncNodeName($self) {
        $map_names = [];

        $self->read(['name']);
        foreach($self as $id => $document) {
            $map_names[$id] = $document['name'];
        }

        if(empty($map_names)) {
            return;
        }

        $nodes = Node::search([
                ['document_id', 'in', array_keys($map_names)],
                ['node_type', '=', 'document']
            ])
            ->read(['document_id', 'name']);

        foreach($nodes as $id => $node) {
            $name = $map_names[$node['document_id']] ?? null;
            if($name !== null && $node['name'] !== $name) {
                Node::id($id)->update(['name' => $name]);
            }
        }
    }

    protected static function onupdateParentNodeId($self) {
        $self->do('sync_node');
    }

    protected static function doSyncNode($self) {
        $self->read(['name', 'parent_node_id', 'document_visibility']);

        foreach($self as $id => $document) {
            $nodes = Node::search(['document_id', '=', $id])
                ->read(['name', 'node_type', 'parent_id', 'node_visibility']);

            if(!$document['parent_node_id']) {
                if($nodes->count() > 0) {
                    $nodes->delete();
                }
                continue;
            }

            if($nodes->count() === 0) {
                Node::create([
                    'name'              => $document['name'],
                    'node_type'         => 'document',
                    'parent_id'         => $document['parent_node_id'],
                    'document_id'       => $id,
                    'node_visibility'   => $document['document_visibility']
                ]);
                continue;
            }

            foreach($nodes as $node_id => $node) {
                $values = [];

                if($node['name'] !== $document['name']) {
                    $values['name'] = $document['name'];
                }
                if($node['node_type'] !== 'document') {
                    $values['node_type'] = 'document';
                }
                if($node['parent_id'] !== $document['parent_node_id']) {
                    $values['parent_id'] = $document['parent_node_id'];
                }
                if($node['node_visibility'] !== $document['document_visibility']) {
                    $values['node_visibility'] = $document['document_visibility'];
                }

                if(!empty($values)) {
                    Node::id($node_id)->update($values);
                }
            }
        }
    }

    protected static function calcNodeId($self) {
        $result = [];

        foreach($self as $id => $document) {
            $node = Node::search([
                    ['document_id', '=', $id],
                    ['node_type', '=', 'document']
                ])
                ->first();

            $result[$id] = $node ? $node['id'] : null;
        }

        return $result;
    }

    protected static function onupdateDocumentVisibility($self) {
        $self->do('sync_node_visibility');
    }

    protected static function doSyncNodeVisibility($self) {
        $map_visibility = [];

        $self->read(['document_visibility']);
        foreach($self as $id => $document) {
            $map_visibility[$id] = $document['document_visibility'];
        }

        if(empty($map_visibility)) {
            return;
        }

        $nodes = Node::search([
                ['document_id', 'in', array_keys($map_visibility)],
                ['node_type', '=', 'document']
            ])
            ->read(['document_id', 'node_visibility']);

        foreach($nodes as $id => $node) {
            $visibility = $map_visibility[$node['document_id']] ?? null;
            if($visibility !== null && $node['node_visibility'] !== $visibility) {
                Node::id($id)->update(['node_visibility' => $visibility]);
            }
        }
    }

    protected static function onbeforedelete($self) {
        $document_ids = $self->ids();
        if(empty($document_ids)) {
            return;
        }

        Node::search(['document_id', 'in', $document_ids])->delete();
    }

    protected static function calcTags($self) {
        $result = [];
        $self->read(['tags_ids' => ['name']]);
        foreach($self as $id => $document) {
            $tags = implode(', ', array_column($document['tags_ids']->get(true), 'name'));
            $result[$id] = strlen($tags) > 50 ? (substr($tags, 0, 50) . '...') : $tags;
        }

        return $result;
    }

    protected static function calcHash($self) {
        $result = [];
        $self->read(['data']);
        foreach($self as $id => $document) {
            if(empty($document['data'])) {
                continue;
            }
            $result[$id] = md5($id . $document['data']);
        }
        return $result;
    }

    protected static function calcHashSha256($self) {
        $result = [];
        $self->read(['data']);
        foreach($self as $id => $document) {
            if(isset($document['data'])) {
                $result[$id] = hash('sha256', $document['data']);
            }
        }
        return $result;
    }

    protected static function calcContentSize($self) {
        $result = [];
        $self->read(['data']);

        foreach($self as $id => $document) {
            if(isset($document['data'])) {
                $result[$id] = strlen($document['data']);
            }
        }

        return $result;
    }

    protected static function calcReadableSize($self) {
        $result = [];
        $self->read(['content_size']);
        $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];
        foreach($self as $id => $document) {
            $size = $document['content_size'];
            if($size) {
                $power = $size > 0 ? floor(log($size, 1024)) : 0;
                $result[$id] = number_format($size / pow(1024, $power), 1, '.', '') . ' ' . $units[$power];
            }
        }
        return $result;
    }

    protected static function calcLink($self) {
        $result = [];
        $self->read(['hash']);
        foreach($self as $id => $document) {
            $result[$id] = '/document/'.$document['hash'];
        }
        return $result;
    }

    protected static function calcContentType($self) {
        $result = [];
        $self->read(['data']);

        foreach($self as $id => $document) {
            if(!isset($document['data'])) {
                continue;
            }

            try {
                $finfo = new \finfo(FILEINFO_MIME_TYPE);
                $mime = $finfo->buffer($document['data']);

                if(empty($mime)) {
                    $result[$id] = 'application/octet-stream';
                    continue;
                }

                $result[$id] = $mime;

            }
            catch(\Exception $e) {
                $result[$id] = 'application/octet-stream';
            }

        }

        return $result;
    }

    protected static function calcExtension($self) {
        $result = [];
        $self->read(['content_type']);

        foreach($self as $id => $document) {
            if(isset($document['content_type'])) {
                $result[$id] = self::computeExtensionFromType($document['content_type']);
            }
        }

        return $result;
    }

    /**
     * Retrieve and validate an extension from a content type and a filename.
     *
     * @param $content_type string  The content_type found for for the file.
     * @param $name string  (optional) The name of the file, if any.
     *
     * @return string | bool    In case of success, the extension is returned. If no extension matches the content type, the method returns false.
     */
    private static function computeExtensionFromType($content_type, $name = '') {

        static $map_extensions = [
            '3g2'   => 'video/3gpp2',
            '3gp'   => 'video/3gpp',
            '7z'    => 'application/x-7z-compressed',
            'aac'   => 'audio/aac',
            'ac3'   => 'audio/ac3',
            'ai'    => 'application/vnd.adobe.illustrator',
            'aif'   => 'audio/aiff',
            'aifc'  => 'audio/x-aiff',
            'aiff'  => 'audio/aiff',
            'apng'  => 'image/apng',
            'au'    => 'audio/basic',
            'avi'   => 'video/x-msvideo',
            'avif'  => 'image/avif',
            'bin'   => 'application/octet-stream',
            'bmp'   => 'image/bmp',
            'cdr'   => 'application/vnd.corel-draw',
            'cer'   => 'application/pkix-cert',
            'class' => 'application/java-vm',
            'cpt'   => 'application/mac-compactpro',
            'crl'   => 'application/pkix-crl',
            'crt'   => 'application/x-x509-ca-cert',
            'csr'   => 'application/pkcs10',
            'css'   => 'text/css',
            'csv'   => 'text/csv',
            'dcr'   => 'application/x-director',
            'der'   => 'application/x-x509-ca-cert',
            'dir'   => 'application/x-director',
            'dll'   => 'application/x-msdownload',
            'dms'   => 'application/octet-stream',
            'doc'   => 'application/msword',
            'docx'  => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'dot'   => 'application/msword',
            'dotx'  => 'application/vnd.openxmlformats-officedocument.wordprocessingml.template',
            'dvi'   => 'application/x-dvi',
            'dxr'   => 'application/x-director',
            'eml'   => 'message/rfc822',
            'eps'   => 'application/postscript',
            'exe'   => 'application/x-msdownload',
            'f4v'   => 'video/x-f4v',
            'flac'  => 'audio/flac',
            'flv'   => 'video/x-flv',
            'gif'   => 'image/gif',
            'gpg'   => 'application/pgp-encrypted',
            'gtar'  => 'application/x-gtar',
            'gz'    => 'application/gzip',
            'heic'  => 'image/heic',
            'heif'  => 'image/heif',
            'hqx'   => 'application/mac-binhex40',
            'htm'   => 'text/html',
            'html'  => 'text/html',
            'ical'  => 'text/calendar',
            'ico'   => 'image/vnd.microsoft.icon',
            'ics'   => 'text/calendar',
            'j2k'   => 'image/jp2',
            'jar'   => 'application/java-archive',
            'jp2'   => 'image/jp2',
            'jpe'   => 'image/jpeg',
            'jpeg'  => 'image/jpeg',
            'jpf'   => 'image/jpx',
            'jpg'   => 'image/jpeg',
            'jpg2'  => 'image/jp2',
            'jpm'   => 'image/jpm',
            'jpx'   => 'image/jpx',
            'js'    => 'application/javascript',
            'json'  => 'application/json',
            'kdb'   => 'application/octet-stream',
            'kml'   => 'application/vnd.google-earth.kml+xml',
            'kmz'   => 'application/vnd.google-earth.kmz',
            'lha'   => 'application/octet-stream',
            'log'   => 'text/plain',
            'lzh'   => 'application/octet-stream',
            'm3u'   => 'audio/x-mpegurl',
            'm4a'   => 'audio/mp4',
            'm4u'   => 'video/vnd.mpegurl',
            'mid'   => 'audio/midi',
            'midi'  => 'audio/midi',
            'mif'   => 'application/vnd.mif',
            'mj2'   => 'video/mj2',
            'mjp2'  => 'video/mj2',
            'mov'   => 'video/quicktime',
            'movie' => 'video/x-sgi-movie',
            'mp2'   => 'audio/mpeg',
            'mp3'   => 'audio/mpeg',
            'mp4'   => 'video/mp4',
            'mpe'   => 'video/mpeg',
            'mpeg'  => 'video/mpeg',
            'mpg'   => 'video/mpeg',
            'mpga'  => 'audio/mpeg',
            'oda'   => 'application/oda',
            'odc'   => 'application/vnd.oasis.opendocument.chart',
            'odf'   => 'application/vnd.oasis.opendocument.formula',
            'odg'   => 'application/vnd.oasis.opendocument.graphics',
            'odi'   => 'application/vnd.oasis.opendocument.image',
            'odm'   => 'application/vnd.oasis.opendocument.text-master',
            'odp'   => 'application/vnd.oasis.opendocument.presentation',
            'ods'   => 'application/vnd.oasis.opendocument.spreadsheet',
            'odt'   => 'application/vnd.oasis.opendocument.text',
            'ogg'   => 'application/ogg',
            'otc'   => 'application/vnd.oasis.opendocument.chart-template',
            'otf'   => 'application/x-font-otf',
            'otg'   => 'application/vnd.oasis.opendocument.graphics-template',
            'oth'   => 'application/vnd.oasis.opendocument.text-web',
            'oti'   => 'application/vnd.oasis.opendocument.image-template',
            'otp'   => 'application/vnd.oasis.opendocument.presentation-template',
            'ots'   => 'application/vnd.oasis.opendocument.spreadsheet-template',
            'ott'   => 'application/vnd.oasis.opendocument.text-template',
            'p10'   => 'application/pkcs10',
            'p12'   => 'application/x-pkcs12',
            'p7a'   => 'application/x-pkcs7-signature',
            'p7c'   => 'application/pkcs7-mime',
            'p7m'   => 'application/pkcs7-mime',
            'p7r'   => 'application/x-pkcs7-certreqresp',
            'p7s'   => 'application/pkcs7-signature',
            'pdf'   => 'application/pdf',
            'pem'   => 'application/x-pem-file',
            'pgp'   => 'application/pgp-encrypted',
            'php'   => 'application/x-httpd-php',
            'php3'  => 'application/x-httpd-php',
            'php4'  => 'application/x-httpd-php',
            'phps'  => 'application/x-httpd-php-source',
            'phtml' => 'application/x-httpd-php',
            'png'   => 'image/png',
            'ppt'   => 'application/vnd.ms-powerpoint',
            'pptx'  => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'ps'    => 'application/postscript',
            'psd'   => 'image/vnd.adobe.photoshop',
            'qt'    => 'video/quicktime',
            'ra'    => 'audio/x-pn-realaudio',
            'ram'   => 'audio/x-pn-realaudio',
            'rar'   => 'application/x-rar-compressed',
            'rm'    => 'application/vnd.rn-realmedia',
            'rpm'   => 'audio/x-pn-realaudio-plugin',
            'rsa'   => 'application/x-pkcs7',
            'rtf'   => 'application/rtf',
            'rtx'   => 'text/richtext',
            'rv'    => 'video/vnd.rn-realvideo',
            'sea'   => 'application/octet-stream',
            'shtml' => 'text/html',
            'sit'   => 'application/x-stuffit',
            'smi'   => 'application/smil',
            'smil'  => 'application/smil',
            'so'    => 'application/octet-stream',
            'srt'   => 'application/x-subrip',
            'sst'   => 'application/octet-stream',
            'svg'   => 'image/svg+xml',
            'swf'   => 'application/x-shockwave-flash',
            'tar'   => 'application/x-tar',
            'text'  => 'text/plain',
            'tgz'   => 'application/gzip',
            'tif'   => 'image/tiff',
            'tiff'  => 'image/tiff',
            'txt'   => 'text/plain',
            'vcf'   => 'text/vcard',
            'vlc'   => 'application/videolan',
            'vtt'   => 'text/vtt',
            'wav'   => 'audio/wav',
            'wbxml' => 'application/vnd.wap.wbxml',
            'webm'  => 'video/webm',
            'wma'   => 'audio/x-ms-wma',
            'wmlc'  => 'application/vnd.wap.wmlc',
            'wmv'   => 'video/x-ms-wmv',
            'word'  => 'application/msword',
            'xht'   => 'application/xhtml+xml',
            'xhtml' => 'application/xhtml+xml',
            'xl'    => 'application/excel',
            'xls'   => 'application/vnd.ms-excel',
            'xlsx'  => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'xml'   => 'application/xml',
            'xsl'   => 'application/xml',
            'xspf'  => 'application/xspf+xml',
            'z'     => 'application/x-compress',
            'zip'   => 'application/zip',
            'zsh'   => 'application/x-zsh'
        ];

        static $map_mime_commons = [
            'image/jpeg'                => 'jpg',
            'image/pjpeg'               => 'jpg',
            'image/png'                 => 'png',
            'image/gif'                 => 'gif',
            'image/webp'                => 'webp',
            'image/avif'                => 'avif',
            'image/tiff'                => 'tiff',
            'image/bmp'                 => 'bmp',
            'image/x-ms-bmp'            => 'bmp',
            'image/heif'                => 'heic',
            'image/heic'                => 'heic',
            'image/svg+xml'             => 'svg',
            'image/vnd.microsoft.icon'  => 'ico',
            'image/x-icon'              => 'ico',
            'image/x-adobe-dng'         => 'dng',
            'application/pdf'           => 'pdf',
            'application/msword'        => 'doc',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
            'application/vnd.ms-excel'  => 'xls',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx',
            'application/vnd.ms-powerpoint' => 'ppt',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation' => 'pptx',
            'text/plain'                => 'txt',
            'text/csv'                  => 'csv',
            'text/html'                 => 'html',
            'application/rtf'           => 'rtf',
            'application/xml'           => 'xml',
            'application/json'          => 'json',
            'application/vnd.oasis.opendocument.text' => 'odt',
            'application/vnd.oasis.opendocument.spreadsheet' => 'ods',
            'application/vnd.oasis.opendocument.presentation' => 'odp',
            'application/zip'           => 'zip',
            'application/x-7z-compressed' => '7z',
            'application/x-rar-compressed' => 'rar',
            'application/x-tar'         => 'tar',
            'application/gzip'          => 'gz',
            'application/postscript'    => 'eps'
        ];

        if(isset($map_mime_commons[$content_type])) {
            return $map_mime_commons[$content_type];
        }

        // if $name holds an extension, check if the given content_type matches a valid MIME
        if(strlen($name)) {
            $extension = strtolower( ( ($n = strrpos($name, ".")) === false) ? "" : substr($name, $n + 1) );
            if(strlen($extension) && isset($map_extensions[$extension])) {
                if($map_extensions[$extension] == $content_type) {
                    return $extension;
                }
            }
        }
        // fallback to the first extension that matches $content_type
        foreach($map_extensions as $extension => $mime) {
            if($mime == $content_type) {
                return $extension;
            }
        }
        // unknown or invalid content type
        return false;
    }

    /**
     * Generate the preview image.
     *
     * By convention, generated thumbnail is always a JPEG image.
     *
     */
    protected static function calcPreviewImage($self) {
        $result = [];
        $target_width = 150;
        $target_height = 150;
        $self->read(['name', 'content_type', 'data']);
        foreach($self as $id => $document) {
            if(!$document['content_type']) {
                continue;
            }
            try {
                if(!$document['data'] || substr($document['content_type'], 0, 5) != 'image') {
                    throw new Exception('not_an_image');
                }

                $parts = explode('/', $document['content_type']);

                if(count($parts) < 2) {
                    throw new Exception('invalid_content_type');
                }

                $image_type = strtolower($parts[1]);

                if(!in_array($image_type, ['avif', 'apng', 'bmp', 'png', 'gif', 'jpeg', 'svg+xml', 'webp', 'x-icon'])) {
                    throw new Exception('non_supported_format');
                }

                $src_image = imagecreatefromstring($document['data']);

                if(!$src_image) {
                    throw new Exception('malformed_image_data');
                }

                $src_width = imageSX($src_image);
                $src_height = imageSY($src_image);

                $dst_image = imagecreatetruecolor($target_width, $target_height);

                // preserve transparency
                if ($image_type == 'png' || $image_type == 'gif') {
                    imagealphablending($dst_image, false);
                    imagesavealpha($dst_image, true);
                    $transparent = imagecolorallocatealpha($dst_image, 255, 255, 255, 127);
                    imagefilledrectangle($dst_image, 0, 0, $target_width, $target_height, $transparent);
                }
                else {
                    // fill background with white for non-transparent images
                    $white = imagecolorallocate($dst_image, 255, 255, 255);
                    imagefilledrectangle($dst_image, 0, 0, $target_width, $target_height, $white);
                }

                if( ($src_width / $src_height) < ($target_width / $target_height) ) {
                    $new_height = $target_height;
                    $new_width  = $src_width * $target_height / $src_height;
                }
                else {
                    $new_height = $src_height * $target_width / $src_width;
                    $new_width  = $target_width;
                }

                $offset_x  = round( ($target_width - $new_width) / 2 );
                $offset_y  = round( ($target_height - $new_height) / 2 );
                imagecopyresampled($dst_image, $src_image, $offset_x, $offset_y, 0, 0, $new_width, $new_height, $src_width, $src_height);

                // get binary value of generated image
                ob_start();
                imagejpeg($dst_image, null, 80);
                $buffer = ob_get_clean();

                // free mem
                imagedestroy($dst_image);
                imagedestroy($src_image);

                $result[$id] = $buffer;
            }
            // non-supported image type or non-image document: fallback to hardcoded default thumbnail
            catch(Exception $e) {
                trigger_error("APP:unable to generate dynamic thumbnail: " . $e->getMessage(), EQ_REPORT_INFO);
                $found = false;
                $extension = self::computeExtensionFromType($document['content_type'], $document['name']);
                if($extension) {
                    $filename = EQ_BASEDIR . '/packages/document/assets/img/extensions/' . $extension . '.jpg';
                    if(is_file($filename)) {
                        $found = true;
                        $result[$id] = file_get_contents($filename);
                    }
                }
                if(!$found) {
                    $filename = EQ_BASEDIR . '/packages/document/assets/img/extensions/unknown.jpg';
                    $result[$id] = file_get_contents($filename);
                }
            }
        }
        return $result;
    }

    public static function onchange($event, $values) {
        $result = [];

        if(isset($event['data']['name'])) {
            $result['name'] = $event['data']['name'];
        }

        return $result;
    }
}
