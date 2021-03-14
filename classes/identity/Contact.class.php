<?php
namespace symbiose\identity;
use qinoa\orm\Model;

class Contact extends Model {
    public static function getColumns() {
        /**
         *
         */

        return [
            'name' => [
                'type'              => 'string',
                'description'       => "Full name of the contact (must be a person, not a role)",
                'required'          => true                
            ],
            'email' => [
                'type'              => 'string',
                'usage'             => 'email',                
                'description'       => "Email address of the contact",
                'required'          => true
            ],
            'phone' => [
                'type'              => 'string',
                'usage'             => 'phone',
                'description'       => "Phone number of the contact, if any"
            ],            
             'organisation_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'symbiose\identity\Organisation',
                'description'       => "The organisation the contact belongs to",
                'required'          => true
            ]
        ];
    }
}