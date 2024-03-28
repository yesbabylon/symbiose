<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace sale\receivable;
use \equal\orm\Model;
use sale\price\Price;
use sale\price\PriceList;
use sale\catalog\Product;

class Receivable extends Model {

    public static function getDescription() {
        return "A Sale Receivable represent a good or a service that has been sold to a Customer, and whose amount must be received.";
    }

    public static function getColumns() {

        return [

            'receivables_queue_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\receivable\ReceivablesQueue',
                'description'       => 'The parent Queue the item is attached to.',
                'required'          => true,
                'dependencies'      => ['customer_id']
            ],

            'date' => [
                'type'              => 'datetime',
                'description'       => 'The entry is linked to a Receivable entry.',
                'required'          => true,
                'default'           => time(),
                'dependencies'      => ['price_id']
            ],

            'invoice_line_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'finance\accounting\InvoiceLine',
                'description'       => 'The invoice line that has been generated based on the item.'
            ],

            'customer_id' => [
                'type'              => 'computed',
                'result_type'       => 'many2one',
                'foreign_object'    => 'sale\customer\Customer',
                'description'       => 'The Customer to who refers the item (from ReceivableQueue).',
                'store'             => true,
                'function'          => 'calcCustomerId'
            ],

            'name' => [
                'type'              => 'computed',
                'result_type'       => 'string',
                'description'       => 'Default label of the line, based on product.',
                'function'          => 'calcName',
                'store'             => true
            ],

            'status' => [
                'type'              => 'string',
                'description'       => 'Version of the receivable.',
                'selection'         => ['pending', 'invoiced', 'cancelled'],
                'default'           => 'pending'
            ],

            'description' => [
                'type'              => 'string',
                'description'       => 'Complementary description of the line.'
            ],

            'invoice_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\accounting\invoice\Invoice',
                'description'       => 'Invoice the line is related to.',
                'ondelete'          => 'null'
            ],

            'product_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\catalog\Product',
                'description'       => 'The product (SKU) the line relates to.',
                'required'          => true,
                'dependencies'      => ['name', 'price_id']
            ],

            'price_id' => [
                'type'              => 'computed',
                'result_type'       => 'many2one',
                'foreign_object'    => 'sale\price\Price',
                'description'       => 'The price the line relates to (assigned at line creation).',
                'dependencies'      => ['unit_price'],
                'function'          => 'calcPriceId',
                'store'             => true
            ],

            'unit_price' => [
                'type'              => 'computed',
                'result_type'       => 'float',
                'usage'             => 'amount/money:4',
                'description'       => 'Unit price of the product related to the line.',
                'dependencies'      => ['total', 'price'],
                'function'          => 'calcUnitPrice',
                'store'             => true
            ],

            'vat_rate' => [
                'type'              => 'float',
                'usage'             => 'amount/rate',
                'description'       => 'VAT rate to be applied.',
                'default'           => 0.0,
                'dependencies'      => ['total', 'price']
            ],

            'qty' => [
                'type'              => 'float',
                'description'       => 'Quantity of product.',
                'default'           => 0,
                'dependencies'      => ['total', 'price']
            ],

            'free_qty' => [
                'type'              => 'integer',
                'description'       => 'Free quantity.',
                'default'           => 0,
                'dependencies'      => ['total', 'price']
            ],

            // #memo - important: to allow maximum flexibility, percent values can hold 4 decimal digits (must not be rounded, except for display)
            'discount' => [
                'type'              => 'float',
                'usage'             => 'amount/rate',
                'description'       => 'Total amount of discount to apply, if any.',
                'default'           => 0.0,
                'dependencies'      => ['total', 'price']
            ],

            'total' => [
                'type'              => 'computed',
                'result_type'       => 'float',
                'usage'             => 'amount/money:4',
                'description'       => 'Total tax-excluded price of the line (computed).',
                'function'          => 'calcTotal',
                'store'             => true
            ],

            'price' => [
                'type'              => 'computed',
                'result_type'       => 'float',
                'usage'             => 'amount/money:2',
                'description'       => 'Final tax-included price of the line (computed).',
                'function'          => 'calcPrice',
                'store'             => true
            ]

        ];
    }

    public static function onchange($event, $values) {
        $result = [];

        if(isset($event['receivables_queue_id']) && strlen($event['receivables_queue_id']) > 0 ){
            $receivables_queue = ReceivablesQueue::id($event['receivables_queue_id'])->read('customer_id')->first();
            $result['customer_id'] = $receivables_queue['customer_id'];
        }

        if(isset($event['product_id'])) {
            $product = Product::id($event['product_id'])->read('name')->first();
            $result['name'] =$product['name'];
        }
        if(isset($event['product_id']) && isset($values['date'])) {

            $price_lists_ids = PriceList::search([
                    [
                        ['date_from', '<=', $values['date']],
                        ['date_to', '>=', $values['date']],
                        ['status', '=', 'published'],
                    ]
                ] )
                ->ids();

            $price = Price::search([
                ['product_id', '=', $event['product_id']],
                ['price_list_id', 'in', $price_lists_ids]
                ])->read(['id','name','price','vat_rate'])->first();

                $result['price_id'] = $price;
                $result['unit_price'] = $price['price'];
                $result['vat_rate'] = $price['vat_rate'];

        }

        if(isset($event['unit_price']) || isset($event['qty']) || isset($event['free_qty']) || isset($event['discount']) || isset($event['vat_rate'])) {

            $unit_price =(float) isset($event['unit_price'])?$event['unit_price']:$values['unit_price'];
            $qty = isset($event['qty'])?$event['qty']:$values['qty'];
            $free_qty = isset($event['free_qty'])?$event['free_qty']:$values['free_qty'];
            $discount = isset($event['discount'])?$event['discount']:$values['discount'];
            $vat_rate = (float) isset($event['vat_rate'])?$event['vat_rate']:$values['vat_rate'];
            $total = $unit_price * (1.0 - $discount) * ($qty  - $free_qty);
            $result['total'] =$total;
            $result['price'] = round($total * (1.0 + $vat_rate), 2);

        }

        if(isset($event['price_id']) || strlen($event['price_id']) > 0 ){
            $price =Price::id($event['price_id'])->read(['price','vat_rate'])->first();
            $result['unit_price'] = $price['price'];
            $result['vat_rate'] = $price['vat_rate'];
        }

        return $result;
    }

    public static function calcPriceId($self) {
        $result = [];
        $self->read(['date', 'product_id']);
        foreach($self as $id => $receivable) {
            $price_lists_ids = PriceList::search([
                [
                    ['date_from', '<=', $receivable['date']],
                    ['date_to', '>=', $receivable['date']],
                    ['status', '=', 'published'],
                ]
            ])
            ->ids();

            $price = Price::search([
                    ['product_id', '=', $receivable['product_id']],
                    ['price_list_id', 'in', $price_lists_ids]
                ])
                ->read(['id'])
                ->first();

            $result[$id] = $price['id'];
        }
        return $result;
    }

    public static function calcUnitPrice($self) {
        $result = [];
        $self->read(['price_id' => ['price']]);
        foreach($self as $id => $receivable) {
            $result[$id] = $receivable['price_id']['price'];
        }
        return $result;
    }

    public static function calcName($self) {
        $result = [];
        $self->read(['product_id' => ['name']]);
        foreach($self as $id => $receivable) {
            $result[$id] = $receivable['product_id']['name'];
        }
        return $result;
    }

    public static function calcCustomerId($self) {
        $result = [];
        $self->read(['receivables_queue_id' => ['customer_id']]);
        foreach($self as $id => $receivable) {
            if($receivable['receivables_queue_id']) {
                $result[$id] = $receivable['receivables_queue_id']['customer_id'];
            }
        }
        return $result;
    }

    /**
     * Get total tax-excluded price of the receivable.
     *
     */
    public static function calcTotal($self) {
        $result = [];
        $self->read(['qty','unit_price','free_qty','discount']);
        foreach($self as $id => $receivable) {
            $result[$id] = $receivable['unit_price'] * (1.0 - $receivable['discount']) * ($receivable['qty'] - $receivable['free_qty']);
        }
        return $result;
    }

    /**
     * Get final tax-included price of the receivable.
     *
     */
    public static function calcPrice($self) {
        $result = [];
        $self->read(['total','vat_rate']);
        foreach($self as $id => $receivable) {
            $total = (float) $receivable['total'];
            $vat = (float) $receivable['vat_rate'];
            $result[$id] = round($total * (1.0 + $vat), 2);
        }
        return $result;
    }
}