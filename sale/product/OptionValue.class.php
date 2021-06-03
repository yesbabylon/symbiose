<?php
namespace symbiose\sale\product;
use qinoa\orm\Model;

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
                'foreign_object'    => 'symbiose\sale\product\Option',
                'description'       => "Product Option this value relates to.",
                'required'          => true
            ]
        ];
    }
}