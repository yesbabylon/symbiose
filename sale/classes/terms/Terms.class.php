<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2024
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

namespace sale\terms;

use equal\orm\Model;

class Terms extends Model {
    
    public static function getDescription() {
        return 'Conditions under which a seller will complete a sale, typically specifying when payment is due and any discounts or penalties for early or late payments.';
    }

    public static function getColumns() {
        return [

            'name' => [
                'type'              => 'string',
                'description'       => 'Name of the terms.',
            ],

            'organisation_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'identity\Organisation',
                'description'       => 'Entity that sets the conditions of the sale.',
                'default'           => 1,
                'required'          => true
            ],

            'publication_date' => [
                'type'              => 'date',
                'description'       => 'Date of publication.',
                'default'           => time()
            ],

            'lang_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'core\Lang',
                'description'       => 'Language of the terms (related document).',
                'default'           => 1
            ],

            'document_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'documents\Document',
                'description'       => 'The document the explaining the payment terms.'
            ],
            
            'url' => [
                'type'              => 'string',
                'usage'             => 'url',
                'description'       => 'Access link to the the payment terms.'
            ]

        ];
    }
}
