<?php
namespace symbiose\finance\tax;
use qinoa\orm\Model;

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