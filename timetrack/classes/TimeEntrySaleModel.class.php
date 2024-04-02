<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2024
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

namespace timetrack;

use equal\orm\Model;

class TimeEntrySaleModel extends Model {

    public static function getName(): string {
        return 'Time entry sale model';
    }

    public static function getDescription(): string {
        return 'A time entry sale model allows to auto set a time entry sale related fields (product_id, price_id and unit_price),'
            .' when the time entry origin and project are matching a sale model.';
    }

    public static function getColumns(): array {
        return [

            'name' => [
                'type'            => 'string',
                'description'     => 'Name of the sale model.',
                'required'        => true,
                'unique'          => true
            ],

            'origin' => [
                'type'            => 'string',
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
                'foreign_field'   => 'time_entry_sale_models_ids',
                'rel_table'       => 'timetrack_project_rel_time_entry_sale_model',
                'rel_foreign_key' => 'project_id',
                'rel_local_key'   => 'time_entry_sale_model_id'
            ]

        ];
    }

    public static function calcUnitPrice($self): array {
        $result = [];
        $self->read(['price_id' => ['price']]);
        foreach($self as $id => $sale_model) {
            if(!isset($sale_model['price_id']['price'])) {
                continue;
            }

            $result[$id] = $sale_model['price_id']['price'];
        }

        return $result;
    }

    public static function getModelToApply(string $origin, int $project_id): ?self {
        $sale_models = self::search(['origin', '=', $origin])
            ->read([
                'name',
                'projects_ids',
                'product_id',
                'price_id',
                'unit_price'
            ]);

        $sale_model_to_apply = null;
        foreach($sale_models as $model) {
            if(empty($model['projects_ids']) && is_null($sale_model_to_apply)) {
                $sale_model_to_apply = $model;
            }
            elseif(in_array($project_id, $model['projects_ids'])) {
                $sale_model_to_apply = $model;
                break;
            }
        }

        return $sale_model_to_apply;
    }

}
