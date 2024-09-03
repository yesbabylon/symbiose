<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2024
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace sale\order;
use sale\customer\Customer;

use sale\accounting\invoice\Invoice as SaleInvoice;

class Invoice extends SaleInvoice {

    public static function getColumns() {

        return [

            'order_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\order\Order',
                'description'       => 'Order the invoice relates to.',
                'required'          => true
            ],

            'funding_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\order\Funding',
                'description'       => 'The funding the invoice originates from, if any.'
            ],

            'fundings_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'sale\order\Funding',
                'foreign_field'     => 'invoice_id',
                'description'       => 'List of fundings relating to the invoice.'
            ],

            'customer_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\customer\Customer',
                'description'       => 'The counter party organization the invoice relates to.',
                'required'          => true,
                'onupdate'          => 'onupdateCustomer'
            ],

            'balance' => [
                'type'              => 'computed',
                'result_type'       => 'float',
                'function'          => 'calcBalance',
                'usage'             => 'amount/money:2',
                'description'       => 'Amount left to be paid by customer.',
                'instant'           => true
            ]
        ];
    }

    public static function calcBalance($self) {
        $result = [];
        $self->read(['order_id', 'invoice_type', 'status', 'is_downpayment', 'fundings_ids', 'price']);
        foreach($self as $id => $invoice) {
            if($invoice['status'] == 'cancelled') {
                $result[$id] = 0;
            }
            else {
                if($invoice['is_downpayment'] || $invoice['invoice_type'] == 'credit_note') {
                    $fundings = Funding::ids($invoice['fundings_ids'])->read(['paid_amount'])->get();
                    if (!empty($fundings)) {
                        $result[$id] = $invoice['invoice_type'] == 'credit_note' ? -$invoice['price'] : $invoice['price'];
                        if (!empty($fundings)) {
                            $result[$id] -= array_sum(array_column($fundings, 'paid_amount'));
                        }
                        $result[$id] = round($result[$id], 2);
                    }
                }
                else {
                    $fundings = Funding::search(['order_id', '=', $invoice['order_id']])
                                        ->read(['invoice_id', 'paid_amount'])
                                        ->get(true);
                    if (!empty($fundings)) {
                        $result[$id] = $invoice['price'];
                        foreach($fundings as $fid => $funding) {
                            if( $funding['invoice_id'] != $id) {
                                continue;
                            }
                            $result[$id] -= $funding['paid_amount'];
                        }
                        $result[$id] = round($result[$id], 2);
                    }
                }
            }
        }
        return $result;
    }


    public static function onupdateCustomer($self): void {
        $self->read(['id', 'customer_id','status']);
        foreach($self as $id => $invoice) {
            if(isset($invoice['customer_id'], $invoice['status']) && $invoice['status'] == 'proforma'){
                $customer = Customer::id($invoice['customer_id'])
                    ->read(['name'])
                    ->first();
                self::id($id)->update(['invoice_number' => '[proforma]['.$customer['name'].']['.date('Y-m-d').']']);
            }
        }
    }

}