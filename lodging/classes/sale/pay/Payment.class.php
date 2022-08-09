<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace lodging\sale\pay;

class Payment extends \sale\pay\Payment {

    public static function getColumns() {

        return [

            'funding_id' => [
                'type'              => 'many2one',
                'foreign_object'    => Funding::getType(),
                'description'       => 'The funding the payement relates to, if any.',
                'onupdate'          => 'sale\pay\Payment::onupdateFundingId'
            ]

        ];
    }

}