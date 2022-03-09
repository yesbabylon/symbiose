<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace sale\autosale;
use equal\orm\Model;

class AutosaleLine extends Model {

    public static function getColumns() {

        return [

            'name' => [
                'type'              => 'alias',
                'alias'             => 'description'
            ],

            'description' => [
                'type'              => 'string',
                'description'       => "Reason of the automatic sale.",
                'multilang'         => true
            ],

            'has_own_qty' => [
                'type'              => 'boolean',
                'description'       => "Item quantity is independent from the booking context.",
                'default'           => false
            ],

            'qty' => [
                'type'              => 'integer',
                'description'       => "Quantity of products that is sold automatically.",
                'visible'           => ['has_own_qty', '=', true],
                'default'           => 1
            ],

            'scope' => [
                'type'              => 'string',
                'selection'         => [
                    'booking',
                    'group'
                ],
                'description'       => 'The scope on which the autosale has to be applied.',
                'default'           => 'booking'
            ],

            'product_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\catalog\Product',
                'description'       => 'The product targeted by the line.'
            ],

            'autosale_list_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\autosale\AutosaleList',
                'description'       => 'The list the line belongs to.'
            ],

            'conditions_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'sale\autosale\Condition',
                'foreign_field'     => 'autosale_line_id',
                'description'       => 'The conditions that apply to the auto-sale.'
            ],


        ];
    }

}