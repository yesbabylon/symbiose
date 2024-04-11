<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace sale\identity;

class Organisation extends \identity\Organisation {

    public static function getColumns(): array {
        return [
            'invoice_image_document_id' => [
                'type'           => 'many2one',
                'foreign_object' => 'documents\Document',
                'description'    => 'Organisation invoice image.'
            ]
        ];
    }
}
