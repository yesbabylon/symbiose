<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace communication;
use equal\orm\Model;

class TemplateAttachment extends Model {
    public static function getColumns() {
        /**
         */

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
                'required'          => true
            ],

            // we use a lang_id since one2many relations cannot be multilang
            'lang_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'core\Lang',
                'description'       => "Language of the attachment (related document).",
                'default'           => 1
            ]

        ];
    }

}