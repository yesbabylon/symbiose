<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace sale\price;
use equal\orm\Model;

class PriceList extends Model {

    public static function getColumns() {
        return [
            'name' => [
                'type'              => 'string',
                'description'       => "Short label to ease identification of the list.",
                'dependents'        => ['prices_ids' => 'name']
            ],

            'date_from' => [
                'type'              => 'date',
                'description'       => "Start of validity period.",
                'required'          => true
            ],

            'date_to' => [
                'type'              => 'date',
                'description'       => "End of validity period.",
                'required'          => true
            ],

            'duration' => [
                'type'              => 'computed',
                'result_type'       => 'integer',
                'function'          => 'calcDuration',
                'store'             => true,
                'description'       => "Pricelist validity duration, in days."
            ],

            // #memo - once published, a pricelist shouldn't be editable
            'status' => [
                'type'              => 'string',
                'selection'         => [
                    'pending',              // list is "under construction" (to be confirmed)
                    'published',            // completed and ready to be used
                    'paused',               // (temporarily) on hold (not to be used)
                    'closed'                // can no longer be used (similar to archive)
                ],
                'description'       => 'Status of the list.',
                'dependents'        => ['is_active', 'prices_ids' => 'is_active'],
                'default'           => 'pending'
            ],

            // needed for retrieving prices without checking the dates
            'is_active' => [
                'type'              => 'computed',
                'result_type'       => 'boolean',
                'function'          => 'calcIsActive',
                'description'       => "Is the pricelist currently applicable? ",
                'help'              => "When this flag is set to true, it means the list is eligible for future bookings. i.e. with a 'date_to' in the future and 'published'.",
                'store'             => true,
                'readonly'          => true
            ],

            // #todo - make this field persistent
            'prices_count' => [
                'type'              => 'computed',
                'result_type'       => 'integer',
                'function'          => 'calcPricesCount',
                // 'store'             => true,
                'description'       => "Number of prices defined in list."
            ],

            'prices_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'sale\price\Price',
                'foreign_field'     => 'price_list_id',
                'description'       => "Prices that are related to this list, if any.",
            ],

            'price_list_category_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\price\PriceListCategory',
                'description'       => "Category this list is related to, if any.",
            ]

        ];
    }

    public static function calcDuration($self) {
        $result = [];
        $self->read(['date_from', 'date_to']);
        foreach($self as $id => $list) {
            $result[$id] = round( ($list['date_to'] - $list['date_from']) / (60 * 60 * 24));
        }
        return $result;
    }

    public static function calcIsActive($self) {
        $result = [];
        $self->read(['prices_ids', 'date_from', 'date_to', 'status']);
        $now = time();
        foreach($self as $id => $list) {
            $result[$id] = boolval( $list['date_from'] <= $now && $list['date_to'] >= $now && $list['status'] == 'published' );
            Price::ids($list['prices_ids'])->update(['is_active' => null]);
        }
        return $result;
    }


    public static function calcPricesCount($self) {
        $result = [];
        $self->read(['prices_ids']);
        foreach($self as $id => $list) {
            $result[$id] = count($list['prices_ids']);
        }
        return $result;
    }

}
