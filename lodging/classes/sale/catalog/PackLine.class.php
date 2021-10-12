<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace lodging\sale\catalog;

class PackLine extends \sale\catalog\PackLine {

    public static function getColumns() {

        return [
            
            'parent_product_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'lodging\sale\catalog\Product',
                'description'       => "The Product this line belongs to.",
                'required'          => true
            ],

            'child_product_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'lodging\sale\catalog\Product',
                'description'       => "The Product this line refers to.",
                'required'          => true,
                'onchange'          => 'sale\catalog\PackLine::onchangeChildProductId'                
            ]            

        ];
    }

}