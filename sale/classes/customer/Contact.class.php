<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace sale\customer;

class Contact extends \identity\Contact {

    public static function getName() {
        return "Customer Contact";
    }

    public static function getDescription() {
        return "Customer contacts are persons, external to the organisation, that represent the customer or provide a link for information about the customer.";
    }

    public function getTable() {
        // force table name to use distinct tables and ID columns
        return 'sale_customer_contact';
    }

    public static function getColumns() {

        return [
            'customer_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\customer\Customer',
                'description'       => 'Customer the contact relates to.',
                'required'          => true
            ],

            'relationship' => [
                'type'              => 'string',
                'default'           => 'contact',
                'description'       => "The partnership should remain 'contact'."
            ]

        ];
    }

}
