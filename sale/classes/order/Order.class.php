<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2024
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace sale\order;
use equal\orm\Model;
use sale\customer\Customer;
use identity\Identity;
class Order extends Model {

    public static function getDescription() {
        return "Orders consolidate all the information needed to track the ordering process.";
    }

    public static function getColumns() {
        return [

            'name' => [
                'type'              => 'computed',
                'result_type'       => 'string',
                'description'       => "Code to serve as reference (might not be unique)",
                'function'          => 'calcName',
                'store'             => true
            ],

            'description' => [
                'type'              => 'string',
                'usage'             => 'text/plain',
                'description'       => "Reason or comments about the order, if any (for internal use).",
                'default'           => ''
            ],

            'customer_reference' => [
                'type'              => 'string',
                'description'       => "Code or short string given by the customer as own reference, if any.",
                'default'           => ''
            ],

            'customer_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\customer\Customer',
                'description'       => "The customer whom the order relates to (computed)."
            ],

            'customer_identity_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'identity\Identity',
                'description'       => "The Customer identity."
            ],

            'total' => [
                'type'              => 'computed',
                'result_type'       => 'float',
                'usage'             => 'amount/money:4',
                'function'          => 'calcTotal',
                'description'       => 'Total tax-excluded price of the order.',
                'store'             => true
            ],

            'price' => [
                'type'              => 'computed',
                'result_type'       => 'float',
                'usage'             => 'amount/money:2',
                'function'          => 'calcPrice',
                'description'       => 'Final tax-included price of the order.',
                'store'             => true
            ],

            'has_contract' => [
                'type'              => 'boolean',
                'description'       => "Has a contract been generated yet? Flag is reset in case of changes before the sojourn.",
                'default'           => false
            ],

            'contracts_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'sale\order\Contract',
                'foreign_field'     => 'order_id',
                'sort'              => 'desc',
                'description'       => 'List of contacts related to the order, if any.'
            ],

            'order_lines_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'sale\order\OrderLine',
                'foreign_field'     => 'order_id',
                'description'       => 'Detailed lines of the order.',
                'dependents'        => ['total', 'price']
            ],

            'order_lines_groups_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'sale\order\OrderLineGroup',
                'foreign_field'     => 'order_id',
                'description'       => 'Grouped lines of the order.',
                'ondetach'          => 'delete',
                'dependents'        => ['total', 'price']
            ],

            'status' => [
                'type'              => 'string',
                'selection'         => [
                    'quote',                    // order is just informative: nothing has been booked in the planning
                    'option',                   // order has been placed in the planning for 10 days
                    'confirmed',                // order has been placed in the planning without time limit
                    'validated',                // signed contract and first installment have been received
                    'checkedin',                // host is currently occupying the booked rental unit
                    'checkedout',               // host has left the booked rental unit
                    'invoiced',
                    'debit_balance',            // customer still has to pay something
                    'credit_balance',           // a reimbusrsement to customer is required
                    'balanced'                  // order is over and balance is cleared
                ],
                'description'       => 'Status of the order.',
                'default'           => 'quote'
            ],

            'is_cancelled' => [
                'type'              => 'boolean',
                'description'       => "Flag marking the order as cancelled (impacts status).",
                'default'           => false
            ],

            'is_noexpiry' => [
                'type'              => 'boolean',
                'description'       => "Flag marking an option as never expiring.",
                'default'           => false,
                'visible'           => ['status', '=', 'option']
            ],

            'date_expiry' => [
                'type'              => 'date',
                'description'       => 'Order expiration date in Option',
                'visible'           => [["status", "=", "option"],["is_noexpiry", "=", false]],
                'default'           => time()
            ],

            'cancellation_reason' => [
                'type'              => 'string',
                'selection'         => [
                    'other',                    // customer cancelled for a non-listed reason or without mentioning the reason (cancellation fees might apply)
                    'duplicate',                // several contacts of the same company made distinct orders for the same shipment
                    'internal_impediment',      // cancellation due to an incident impacting the production
                    'external_impediment',      // cancellation due to external delivery failure (organisation, means of transport, ...)
                ],
                'description'       => "Reason for which the customer cancelled the order.",
                'default'           => 'generic'
            ],

            'payment_status' => [
                'type'              => 'computed',
                'result_type'       => 'string',
                'selection'         => [
                    'due',                       // some due payment have not been received yet
                    'paid'                       // all expected payments have been received
                ],
                'function'          => 'calcPaymentStatus',
                'store'             => true
            ],

            'delivery_date' => [
                'type'              => 'date',
                'default'           => time()
            ],

            'fundings_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'sale\order\Funding',
                'foreign_field'     => 'order_id',
                'description'       => 'Fundings that relate to the order.',
                'ondetach'          => 'delete'
            ],

            'invoices_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'sale\order\Invoice',
                'foreign_field'     => 'order_id',
                'description'       => 'Invoices that relate to the order.'
            ]

        ];
    }

    public static function onchange($event) {
        $result = [];

        if(isset($event['customer_id'])) {
            $customer = Customer::id($event['customer_id'])->read(['partner_identity_id' => ['id', 'name']])->first(true);
            $result['customer_identity_id'] = $customer['partner_identity_id'];
        }

        if(isset($event['status']) && ($event['status'] == 'confirmed')) {
            $result['has_contract'] = true;
        }

        return $result;
    }

    public static function calcName($self) {
        $result = [];
        $self->read(['id','modified', 'customer_id', 'customer_identity_id']);
        foreach($self as $id => $order) {
            $customer = Customer::id($order['customer_id'])->read(['partner_identity_id' => ['id', 'name']])->first(true);
            $increment = 1 ;
            $orders = Order::search([['customer_id','=', $order['customer_id']]])->ids();
            if($orders){
                $increment = count($orders);
            }
            $result[$id] = sprintf("%s-%08d-%02d", date("ymd", $order['modified']), $customer['partner_identity_id']['id'], $increment);

        }
        return $result;
    }


    /**
     * Payment status tells if a given order is in order regarding the expected payment up to now.
     */
    public static function calcPaymentStatus($self) {
        // #todo
        $result = [];
        return $result;
    }

    public static function calcPrice($self): array {
        $result = [];
        $self->read(['order_lines_groups_ids' => ['price']]);
        foreach($self as $id => $group) {
            $result[$id] = array_reduce($group['order_lines_groups_ids']->get(true), function ($c, $a) {
                return $c + $a['price'];
            }, 0.0);
        }

        return $result;
    }


    public static function calcTotal($self): array {
        $result = [];
        $self->read(['order_lines_groups_ids' => ['total']]);
        foreach($self as $id => $group) {
            $result[$id] = array_reduce($group['order_lines_groups_ids']->get(true), function ($c, $a) {
                return $c + $a['total'];
            }, 0.0);
        }

        return $result;
    }

    public static function updateStatusFromFundings($ids) {
        $orders = Order::ids($ids)->read(['status', 'fundings_ids'])->get();
        foreach($orders as $bid => $order) {
            $diff = 0.0;
            $fundings = Funding::ids($order['fundings_ids'])->read(['due_amount', 'paid_amount'])->get(true);
            foreach($fundings as $fid => $funding) {
                $diff += $funding['due_amount'] - $funding['paid_amount'];
            }
            if(!in_array($order['status'], ['invoiced', 'debit_balance', 'credit_balance'])) {
                continue;
            }
            if($diff > 0.0001 ) {
                Order::id($bid)->update(['status' => 'debit_balance']);
            }
            elseif($diff < 0) {
                Order::id($bid)->update(['status' => 'credit_balance']);
            }
            else {
                Order::id($bid)->update(['status' =>'balanced']);
            }
        }
    }

    public static function candelete($self, $values) {
        $self->read(['status']);
        foreach($self as $order) {
            if($order['status'] != 'quote') {
                return ['status' => ['non_editable' => 'Non-quote orders cannot be deleted manually.']];
            }
        }

        return parent::candelete($self, $values);
    }

    public static function canupdate($self, $values) : array {
        $self->read(['status']);

        $authorized_fields = ['description'];
        $fields = array_keys($values);
        if (count($fields) === 1 && in_array($fields[0], $authorized_fields)) {
            return parent::canupdate($self, $values);
        }
        foreach($self as $order) {
            if (in_array($order['status'], ['invoiced', 'debit_balance', 'credit_balance', 'balanced'])) {
                if (array_diff($fields, ['status'])) {
                    return ['status' => ['non_editable' => 'The order edition is limited.']];
                }
            }

        }

        return parent::canupdate($self, $values);
    }

}