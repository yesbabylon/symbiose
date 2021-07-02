<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace sale\discount;
use equal\orm\Model;

class Category extends Model {

    public static function getName() {
        return "Discount category";
    }

    public static function getDescription() {
        return "Discount lists can be arranged by categories.";
    }
    

    public static function getColumns() {
        /**
         */

        return [
            'name' => [
                'type'              => 'string',
                'description'       => 'Short name of the booking type.',
                'required'          => true
            ],
            
            'description' => [
                'type'              => 'string',
                'description'       => "Reason of the booking."
            ]

        ];
    }

}