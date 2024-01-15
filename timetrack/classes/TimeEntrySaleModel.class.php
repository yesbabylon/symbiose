<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

namespace timetrack;

use equal\orm\Model;

class TimeEntrySale extends Model {

    public static function getColumns(): array {
        return [

            'origin' => [
                'type'            => 'integer',
                'selection'       => TimeEntry::ORIGIN_MAP,
                'description'     => 'Origin of the this time entry creation.',
                'default'         => TimeEntry::ORIGIN_EMAIL
            ],

            'product_id' => [
                'type'            => 'many2one',
                'foreign_object'  => 'sale\catalog\Product',
                'description'     => 'The product to assign to TimeEntry.'
            ],

            'price_id' => [
                'type'            => 'many2one',
                'foreign_object'  => 'sale\price\Price',
                'description'     => 'The price to assign to TimeEntry.'
            ],

            'unit_price' => [
                'type'            => 'computed',
                'result_type'     => 'float',
                'usage'           => 'amount/money:4',
                'description'     => 'Unit price to assign to TimeEntry.',
                'function'        => 'calcUnitPrice',
                'store'           => true
            ],

            'projects_ids' => [
                'type'            => 'many2many',
                'foreign_object'  => 'timetrack\Project',
                'foreign_field'   => 'time_entry_sales_ids',
                'rel_table'       => 'timetrack_project_rel_time_entry_sale',
                'rel_foreign_key' => 'project_id',
                'rel_local_key'   => 'time_entry_sale_id'
            ],

        ];
    }

    public static function calcUnitPrice($self): array {
        $result = [];
        $self->read(['price_id' => ['price']]);
        foreach($self as $id => $receivable) {
            if(!isset($receivable['price_id']['price'])) {
                continue;
            }

            $result[$id] = $receivable['price_id']['price'];
        }

        return $result;
    }

}
