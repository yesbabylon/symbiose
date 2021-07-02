<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace sale\catalog;
use equal\orm\Model;

class OptionValue extends Model {
    public static function getColumns() {
        /**
         * OptionValue objects are the possible values to which an option, for a given Product Attriubute, can be set to.
         */
        return [
            'value' => [
                'type'              => 'string',
                'description'       => "The possible value for the related option."
            ],
            'description' => [
                'type'              => 'string',
                'description'       => "Short description of the value."
            ],
            'option_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\catalog\Option',
                'description'       => "Product Option this value relates to.",
                'required'          => true
            ]
        ];
    }
}