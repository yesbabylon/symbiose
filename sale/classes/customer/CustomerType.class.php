<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace sale\customer;

class CustomerType {

    public static function getColumns() {
        return [
            'name' => [
                'type'              => 'string',
                'multilang'         => true
            ],

            'description' => [
                'type'              => 'string',
                'description'       => "Short description of the customer type.",
                'multilang'         => true
            ],

            'rate_class_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\customer\RateClass',
                'description'       => "The rate class that applies to this type of customer.",
                // #memo - when using Natures, the rate class to apply is set in CustomerNature
                'required'          => false
            ],
        ];
    }

}