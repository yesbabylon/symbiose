<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace lodging\sale\booking;

class ContractLine extends \sale\booking\ContractLine {

    public static function getName() {
        return "Contract line";
    }

    public static function getColumns() {

        return [

            'contract_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'lodging\sale\booking\Contract',
                'description'       => 'The contract the line relates to.',
            ],

            'contract_line_group_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'lodging\sale\booking\ContractLineGroup',
                'description'       => 'The group the line relates to.',
            ],

            'product_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'lodging\sale\catalog\Product',
                'description'       => 'The product (SKU) the line relates to.',
                'required'          => true
            ]

        ];
    }

}