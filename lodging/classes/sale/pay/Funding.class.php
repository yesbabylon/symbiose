<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace lodging\sale\pay;

class Funding extends \sale\pay\Funding {

    public static function getColumns() {

        return [

            'invoice_id' => [
                'type'              => 'many2one',
                'foreign_object'    => \lodging\finance\accounting\Invoice::getType(),
                'description'       => 'The invoice targeted by the funding, if any.',
                'visible'           => [ ['type', '=', 'invoice'] ]
            ],

            'center_office_id' => [
                'type'              => 'many2one',
                'foreign_object'    => \lodging\identity\CenterOffice::getType(),
                'description'       => "The center office the booking relates to.",
                'required'          => true
            ],

            'payments_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => Payment::getType(),
                'foreign_field'     => 'funding_id'
            ]

        ];
    }

}