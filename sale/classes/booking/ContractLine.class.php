<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace sale\booking;

class ContractLine extends \sale\contract\ContractLine {

    public static function getName() {
        return "Contract line";
    }

    public static function getColumns() {

        return [

            'contract_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\booking\Contract',
                'description'       => 'The contract the line relates to.',
            ]

        ];
    }

    public static function canupdate($om, $oids, $values, $lang='en') {
        $allowed_fields = ['total', 'price'];
        foreach($values as $field => $value) {
            if(!in_array($field, $allowed_fields)) {
                return ['contract_id' => ['not_allowed' => 'Contract cannot be manually updated.']];
            }
        }
    }

}