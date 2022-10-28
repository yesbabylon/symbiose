<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace talentlead\sale\customer;

class Customer extends \sale\customer\Customer {

    public static function getColumns() {
        return [

            // field for retrieving all partners related to the identity
            'campaigns_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'talentlead\campaign\Campaign',
                'foreign_field'     => 'customer_identity_id',
                'description'       => 'Customers related to a campaign.',
                // 'domain'            => ['owner_identity_id', '<>', 'object.id']
            ]

        ];
    }

}