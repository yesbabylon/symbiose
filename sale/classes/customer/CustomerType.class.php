<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace sale\customer;
use equal\orm\Model;

class CustomerType extends Model {
    public static function getColumns() {
        /**
         */

        return [
            'name' => [
                'type'              => 'string',
                'description'       => "Mnemonic of the customer type.",
                'required'          => true
            ],
            'description' => [
                'type'              => 'string',
                'description'       => "Short description of the customer type."
            ],
            'rate_class_id' => [
                'type'              => 'many2one',                
                'foreign_object'    => 'sale\customer\RateClass',
                'description'       => "The rate class that applies to this type of customer.",
                'required'          => true
            ],            
        ];
    }

}