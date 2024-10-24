<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace lodging\identity;

class CenterOffice extends \identity\Establishment {

    public static function getName() {
        return 'Center management Office';
    }

    public static function getDescription() {
        return 'Allow support for management of Centers by distinct Offices.';
    }

    public function getTable() {
        // force table name to use distinct tables and ID columns
        return 'lodging_identity_centeroffice';
    }

    public static function getColumns() {

        return [

            'name' => [
                'type'              => 'string',
                'description'       => 'Name of the Office.'
            ],

            'code' => [
                'type'              => 'string',
                'description'       => 'Numeric identifier of group (1 hex. digit).',
                'usage'             => 'numeric/hexadecimal:1'
            ],

            'centers_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => Center::getType(),
                'foreign_field'     => 'center_office_id',
                'description'       => 'List of centers attached to the office.'
            ],

            'contacts_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'lodging\identity\CenterOfficeContact',
                'foreign_field'     => 'center_office_id',
                'description'       => 'List of contactsattached to the office.'
            ],

            'users_ids' => [
                'type'              => 'many2many',
                'foreign_object'    => 'lodging\identity\User',
                'foreign_field'     => 'center_offices_ids',
                'rel_table'         => 'lodging_identity_rel_center_office_user',
                'rel_foreign_key'   => 'user_id',
                'rel_local_key'     => 'center_office_id'
            ],

            'signature' => [
                'type'              => 'string',
                'usage'             => 'text/html',
                'description'       => 'Office signature to append to communications.',
                'multilang'         => true
            ],

            'docs_default_mode' => [
                'type'              => 'string',
                'selection'         => [
                    'simple',
                    'grouped',
                    'detailed'
                ],
                'description'       => 'Default mode to use when rendering official documents.',
                'default'           => 'simple'
            ],

            'rentalunits_manual_assignment' => [
                'type'              => 'boolean',
                'description'       => 'Flag for forcing manual assignment of the rental units during booking.',
                'default'           => false
            ],

            'freebies_manual_assignment' => [
                'type'              => 'boolean',
                'description'       => 'Flag for forcing manual assignment of the freebies during booking.',
                'default'           => false
            ],

            'accounting_journals_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'lodging\finance\accounting\AccountingJournal',
                'foreign_field'     => 'center_office_id',
                'description'       => 'List of accounting journals of the office.'
            ],

            'product_favorites_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'lodging\sale\catalog\ProductFavorite',
                'foreign_field'     => 'center_office_id',
                'order'             => 'order',
                'description'       => 'List of product favorites of the office.'
            ]

        ];
    }
}