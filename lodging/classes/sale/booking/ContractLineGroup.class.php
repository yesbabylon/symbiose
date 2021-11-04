<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace lodging\sale\booking;

class ContractLineGroup extends \sale\booking\ContractLineGroup {

    public static function getColumns() {

        return [
            'name' => [
                'type'              => 'string',
                'description'       => 'The display name of the contract.'
            ],

            'is_pack' => [
                'type'              => 'boolean',
                'description'       => 'Does the line relates to a pack?',
                'default'           => false
            ],

            /* if group relates to a fixed pack, there is an additional line for holding the price details */
            'contract_line_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'lodging\sale\booking\ContractLine',
                'description'       => 'Contract lines that belong to the contract.',
                'visible'           => ['is_pack', '=', true]
            ],

            'contract_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'lodging\sale\booking\Contract',
                'description'       => 'The contract the line relates to.',
            ],

            'contract_lines_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'lodging\sale\booking\ContractLine',
                'foreign_field'     => 'contract_line_group_id',
                'description'       => 'Contract lines that belong to the contract.',
                'ondetach'          => 'delete'
            ]

        ];
    }

}