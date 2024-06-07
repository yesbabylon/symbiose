<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2024
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

namespace communication;

use documents\Document;
use equal\orm\Model;

class TemplateAttachment extends Model {

    public static function getColumns() {
        return [

            'name' => [
                'type'              => 'string',
                'description'       => "Code of the attachment.",
                'required'          => true
            ],

            'document_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'documents\Document',
                'description'       => "The document that the attachment points to."
            ],

            'template_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'communication\Template',
                'description'       => "The template the part belongs to.",
                'required'          => true,
                'ondelete'          => 'cascade'
            ],

            'lang_id' => [
                'type'              => 'computed',
                'result_type'       => 'many2one',
                'foreign_object'    => 'core\Lang',
                'description'       => "Language of the attachment (related document).",
                'store'             => true,
                'function'          => 'calcLangId'
            ]

        ];
    }

    public static function calcLangId($self): array {
        $result = [];
        $self->read(['document_id' => ['lang_id']]);
        foreach($self as $id => $attachment) {
            if($attachment['document_id']['lang_id']) {
                $result[$id] = $attachment['document_id']['lang_id'];
            }
        }

        return $result;
    }

    public static function onchange($event, $values) {
        $result = [];

        if(isset($event['document_id'])) {
            $document = Document::id($event['document_id'])
                ->read(['lang_id'])
                ->first();

            $result['lang_id'] = $document['lang_id'] ?? 1;
        }

        return $result;
    }
}
