<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace lodging\identity;
use equal\orm\Model;

class CenterOffice extends Model {

    public static function getName() {
        return 'Center management office';
    }

    public static function getDescription() {
        return 'Allow support for management of centers by distinct offices.';
    }

    public static function getColumns() {

        return [

            'name' => [
                'type'              => 'string',
                'description'       => 'Group name.'
            ],

            'code' => [
                'type'              => 'string',
                'description'       => 'Numeric identifier of group (1 hex. digit).',
                'usage'             => 'numeric/hexadecimal:1'
            ],

            'centers_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'lodging\identity\Center',
                'foreign_field'     => 'center_office_id',
                'description'       => 'List of centers attached to the group.'
            ],

            'signature' => [
                'type'              => 'string',
                'usage'             => 'markup/html',
                'description'       => 'Group signature to append to communications.',
                'multilang'         => true
            ],

            'bank_account_iban' => [
                'type'              => 'string',
                'description'       => 'Number of the bank account of the group.'
            ],

            'bank_account_bic' => [
                'type'              => 'string',
                'description'       => 'Identitifer of the Bank related to the bank account.'
            ],

            'phone' => [
                'type'              => 'string',
                'usage'             => 'phone',
                'description'       => 'Official contact phone number.'
            ],

            'fax' => [
                'type'              => 'string',
                'usage'             => 'phone',
                'description'       => "Identity main fax number."
            ],

            'email' => [
                'type'              => 'string',
                'description'       => 'Official contact email address for the group.'
            ],

            'address_street' => [
                'type'              => 'string',
                'description'       => 'Street and number of the group.',
                'required'          => true
            ],

            'address_city' => [
                'type'              => 'string',
                'description'       => 'City in which the management group is located.'
            ],

            'address_zip' => [
                'type'              => 'string',
                'description'       => 'Postal code of the management group address.'
            ]

        ];
    }
}