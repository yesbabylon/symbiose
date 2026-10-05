<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2024
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

namespace purchase\supplier;

class SupplierContact extends \identity\Contact {

    public static function getName() {
        return 'Supplier Contact';
    }

    public static function getDescription() {
        return 'Supplier contacts are persons that represent the supplier or provide a link for information about the supplier.';
    }

    public static function getColumns() {
        return [
            'supplier_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'purchase\supplier\Supplier',
                'description'       => 'Customer the contact relates to.',
                'required'          => true
            ]

        ];
    }

}
