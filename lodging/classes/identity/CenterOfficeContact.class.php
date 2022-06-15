<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace lodging\identity;
use equal\orm\Model;

class CenterOfficeContact extends Model {

    public static function getName() {
        return 'Contact details of a Center management Office';
    }

    public static function getDescription() {
        return 'Allow support for mulitple contact details for a Center management Office.';
    }

    public static function getColumns() {

        return [

            'name' => [
                'type'              => 'alias',
                'alias'             => 'email',
                'description'       => 'Name of the contact.'
            ],

            'center_office_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'lodging\identity\CenterOffice',
                'description'       => 'Management Group to which the center belongs.',
                'required'          => true
            ],

            'type' => [
                'type'              => 'string',
                'selection'         => [
                    'booking',          // contact related to quotes and bookings
                    'invoice',          // contact related to invoices management
                    'contract'          // contact related to legal / contractual matter
                ],
                'description'       => 'The kind of contact, based on its responsibilities.',
                'default'           => 'booking'
            ],

            'email' => [
                'type'              => 'string',
                'usage'             => 'email',
                'description'       => "Identity main email address.",
                'required'          => true
            ]

        ];
    }

    public function getUnique() {
        return [
            ['center_office_id', 'email']
        ];
    }
}