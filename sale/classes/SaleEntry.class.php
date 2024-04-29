<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace sale;
use equal\orm\Model;
use sale\price\Price;
use sale\price\PriceList;

class SaleEntry extends Model {

    public static function getDescription() {
        return "Sale entries are used to describe sales (the action of selling a good or a service).
            In addition, this class is meant to be used as an OOP interface for entities meant to describe something that can be sold.";
    }

    public static function getColumns() {

        return [

            'code' => [
                'type'              => 'computed',
                'result_type'       => 'string',
                'description'       => 'Entry code',
                'function'          => 'calcCode'
            ],

            'description' => [
                'type'              => 'string',
                'description'       => 'Description of the entry.',
                'dependencies'      => ['name']
            ],

            'name' => [
                'type'              => 'computed',
                'result_type'       => 'string',
                'description'       => 'Short readable identifier of the entry.',
                'store'             => true,
                'function'          => 'calcName'
            ],

            'detailed_description' => [
                'type'              => 'string',
                'usage'             => 'text/plain',
                'description'       => 'Detailed description of the entry.'
            ],

            'has_receivable' => [
                'type'              => 'boolean',
                'description'       => 'The entry is linked to a receivable entry.',
                'default'           => false
            ],

            'receivable_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\receivable\Receivable',
                'description'       => 'The receivable entry the sale entry is linked to.',
                'visible'           => ['has_receivable', '=', true]
            ],

            'is_billable' => [
                'type'              => 'boolean',
                'description'       => 'Can be billed to the customer.',
                'default'           => true
            ],

            'customer_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\customer\Customer',
                'description'       => 'The Customer to who refers the item.'
            ],

            'product_id'=> [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\catalog\Product',
                'description'       => 'Product of the catalog sale.'
            ],

            'price_id'=> [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\price\Price',
                'description'       => 'Price of the sale.',
                'dependencies'      => ['unit_price']
            ],

            'unit_price' => [
                'type'              => 'computed',
                'result_type'       => 'float',
                'usage'             => 'amount/money:4',
                'description'       => 'Unit price of the product related to the entry.',
                'function'          => 'calcUnitPrice',
                'store'             => true
            ],

            'qty' => [
                'type'              => 'float',
                'description'       => 'Quantity of product.',
                'default'           => 0
            ],

            'object_class' => [
                'type'              => 'string',
                'description'       => 'Class of the object object_id points to.',
                'dependencies'      => ['subscription_id', 'project_id']
            ],

            'object_id' => [
                'type'              => 'integer',
                'description'       => 'Identifier of the object the sale entry originates from.',
                'dependencies'      => ['subscription_id', 'project_id']
            ],

            'subscription_id' => [
                'type'              => 'computed',
                'result_type'       => 'many2one',
                'foreign_object'    => 'inventory\service\Subscription',
                'function'          => 'calcSubscriptionId',
                'description'       => 'Identifier of the subscription the sale entry originates from.',
                'store'             => true
            ],

            'project_id' => [
                'type'              => 'computed',
                'result_type'       => 'many2one',
                'foreign_object'    => 'timetrack\Project',
                'function'          => 'calcProjectId',
                'description'       => 'Identifier of the Project the sale entry originates from.',
                'store'             => true
            ],

            'receivable_name' => [
                'type'              => 'computed',
                'result_type'       => 'string',
                'description'       => 'Name given to the generated receivable.',
                'function'          => 'calcReceivableName',
                'store'             => false
            ]

        ];
    }

    public static function onchange($event, $values) {
        $result = [];

        if(isset($event['product_id'])) {
            $price_lists_ids = PriceList::search([
                    [
                        ['date_from', '<=', time()],
                        ['date_to', '>=', time()],
                        ['status', '=', 'published'],
                    ]
                ])
                ->ids();

            $result['price_id'] = Price::search([
                    ['product_id', '=', $event['product_id']],
                    ['price_list_id', 'in', $price_lists_ids]
                ])
                ->read(['id', 'name', 'price', 'vat_rate'])
                ->first();

            if(isset($result['price_id']['price'])) {
                $result['unit_price'] = $result['price_id']['price'];
            }
        }

        return $result;
    }

    public static function calcCode($self) {
        $result = [];
        foreach($self->ids() as $id) {
            $result[$id] = str_pad($id, 5, '0', STR_PAD_LEFT);
        }
        return $result;
    }

    public static function calcName($self) {
        $result = [];
        $self->read(['code', 'description']);
        foreach($self as $id => $entry) {
            $result[$id] = '['.$entry['code'].']';
            if(
                isset($entry['description'])
                && strlen($entry['description']) > 0
            ) {
                $result[$id] .= ' '.$entry['description'];
            }
        }
        return $result;
    }

    public static function calcUnitPrice($self) {
        $result = [];
        $self->read(['price_id' => ['price']]);
        foreach($self as $id => $entry) {
            if(!isset($entry['price_id']['price'])) {
                continue;
            }

            $result[$id] = $entry['price_id']['price'];
        }
        return $result;
    }

    public static function calcSubscriptionId($self) {
        $result = [];
        $self->read(['object_class', 'object_id']);
        foreach($self as $id => $entry) {
            if($entry['object_class'] !== 'inventory\service\Subscription') {
                continue;
            }

            $result[$id] = $entry['object_id'];
        }
        return $result;
    }

    public static function calcProjectId($self) {
        $result = [];
        $self->read(['object_class', 'object_id']);
        foreach($self as $id => $entry) {
            if($entry['object_class'] !== 'timetrack\Project') {
                continue;
            }

            $result[$id] = $entry['object_id'];
        }
        return $result;
    }

    public static function calcReceivableName($self): array {
        $result = [];
        $self->read(['object_class', 'description', 'product_id' => ['name']]);

        foreach($self as $id => $entry) {
            $receivable_name = $entry['product_id']['name'];
            if($entry['object_class'] === 'timetrack\Project') {
                $receivable_name .= ' ' . $entry['description'];
            }

            $result[$id] = trim($receivable_name);
        }

        return $result;
    }
}
