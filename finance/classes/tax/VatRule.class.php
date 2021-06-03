<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace finance\tax;
use equal\orm\Model;

class VatRule extends Model {
    
    public static function getName() {
        return "VAT Rule";
    }

    public static function getDescription() {
        return "VAT rules allow to specify which VAT rate applies for a given kind of operation.";
    }

    public static function getColumns() {

        return [
            'name' => [
                'type'              => 'string',
                'description'       => "Name of the VAT rule.",
                'required'          => true
            ],
            'rate' => [
                'type'              => 'float',
                'description'       => "Name of the VAT rule.",
                'usage'             => 'amount/percentage',
                'required'          => true
            ],
            'type' => [
                'type'              => 'string',
                'description'       => "Kind of operation this rule relates to.",
                'selection'         => ['purchase', 'sale'],
                'required'          => true
            ]
        ];
    }

}