<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
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
                'description'       => "The Customer identity.",
                'onupdate'          => 'onupdateCustomerIdentityId'
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
                'description'       => 'Detailed lines of the order.'
            ],

            'order_lines_groups_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'sale\order\OrderLineGroup',
                'foreign_field'     => 'order_id',
                'description'       => 'Grouped lines of the order.',
                'ondetach'          => 'delete',
                'onupdate'          => 'onupdateOrderLinesGroupsIds'
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
                'default'           => 'quote',
                'onupdate'          => 'onupdateStatus'
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

    public static function onchange($event, $values) {
        $result = [];

        if(isset($event['customer_id'])) {
            $customer = Customer::id($event['customer_id'])->read(['partner_identity_id' => ['id', 'name']])->first(true);
            $result['customer_identity_id'] = $customer['partner_identity_id'];
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
    public static function calcPaymentStatus($om, $oids, $lang) {
        // #todo
        $result = [];
        return $result;
    }

    public static function calcPrice($om, $oids, $lang) {
        $result = [];
        $orders = $om->read(get_called_class(), $oids, ['order_lines_groups_ids.price']);
        if($orders > 0) {
            foreach($orders as $id => $order) {
                $price = array_reduce($order['order_lines_groups_ids.price'], function ($c, $group) {
                    return $c + $group['price'];
                }, 0.0);
                $result[$id] = round($price, 2);
            }
        }
        return $result;
    }

    public static function calcTotal($om, $oids, $lang) {
        $result = [];
        $orders = $om->read(get_called_class(), $oids, ['order_lines_groups_ids.total']);
        if($orders > 0) {
            foreach($orders as $id => $order) {
                $total = array_reduce($order['order_lines_groups_ids.total'], function ($c, $a) {
                    return $c + $a['total'];
                }, 0.0);
                $result[$id] = round($total, 4);
            }
        }
        return $result;
    }

    /**
     * #memo - fundings can be partially paid.
     */
    public static function _updateStatusFromFundings($om, $oids, $values, $lang) {
        $orders = $om->read(self::getType(), $oids, ['status', 'fundings_ids'], $lang);
        if($orders > 0) {
            foreach($orders as $bid => $order) {
                $diff = 0.0;
                $fundings = $om->read(Funding::gettype(), $order['fundings_ids'], ['due_amount', 'paid_amount'], $lang);
                foreach($fundings as $fid => $funding) {
                    $diff += $funding['due_amount'] - $funding['paid_amount'];
                }

                if(!in_array($order['status'], ['invoiced', 'debit_balance', 'credit_balance'])) {
                    continue;
                }
                if($diff > 0.0001 ) {
                    // an unpaid amount remains
                    $om->update(self::getType(), $bid, ['status' => 'debit_balance']);
                }
                elseif($diff < 0) {
                    // a reimbursement is due
                    $om->update(self::getType(), $bid, ['status' => 'credit_balance']);
                }
                else {
                    // everything has been paid : order can be archived
                    $om->update(self::getType(), $bid, ['status' => 'balanced']);
                }
            }
        }
    }

    // #todo - this should be part of the onupdate() hook
    public static function _resetPrices($om, $oids, $values, $lang) {
        $om->update(__CLASS__, $oids, ['total' => null, 'price' => null]);
    }

    public static function onupdateOrderLinesGroupsIds($om, $oids, $values, $lang) {
        $om->callonce(__CLASS__, '_resetPrices', $oids, [], $lang);
    }

    public static function onupdateStatus($om, $oids, $values, $lang) {
        $orders = $om->read(get_called_class(), $oids, ['status'], $lang);
        if($orders > 0) {
            foreach($orders as $bid => $order) {
                if($order['status'] == 'confirmed') {
                    $om->update(get_called_class(), $bid, ['has_contract' => true], $lang);
                }
            }
        }
    }

    public static function onupdateCustomerIdentityId($om, $oids, $values, $lang) {
        trigger_error("ORM::calling sale\order\Order:onupdateCustomerIdentityId", QN_REPORT_DEBUG);
        // reset name
        $om->write(__CLASS__, $oids, ['name' => null]);
        $orders = $om->read(__CLASS__, $oids, ['customer_identity_id', 'customer_id']);

        if($orders > 0) {
            foreach($orders as $oid => $order) {
                if(!$order['customer_id']) {
                    $partner_id = null;

                    // find the partner that related to this identity, if any
                    $partners_ids = $om->search('sale\customer\Customer', [
                        ['relationship', '=', 'customer'],
                        ['owner_identity_id', '=', 1],
                        ['partner_identity_id', '=', $order['customer_identity_id']]
                    ]);
                    if(count($partners_ids)) {
                        $partner_id = reset($partners_ids);
                    }
                    else {
                        // read Identity [type_id]
                        $identities = $om->read('identity\Identity', $order['customer_identity_id'], ['type_id']);
                        if($identities > 0) {
                            $identity = reset($identities);
                            $partner_id = $om->create('sale\customer\Customer', [
                                'partner_identity_id'   => $order['customer_identity_id'],
                                'customer_type_id'      => $identity['type_id']
                            ]);
                        }
                    }
                    if($partner_id) {
                        $om->update(__CLASS__, $oid, ['customer_id' => $partner_id]);
                    }
                }
            }
        }
    }

    public static function candelete($om, $oids, $lang='en') {
        $res = $om->read(get_called_class(), $oids, [ 'status' ]);

        if($res > 0) {
            foreach($res as $oids => $odata) {
                if($odata['status'] != 'quote') {
                    return ['status' => ['non_editable' => 'Non-quote orders cannot be deleted manually.']];
                }
            }
        }
        return parent::candelete($om, $oids, $lang);
    }

    /**
     * Check wether an object can be updated, and perform some additional operations if necessary.
     * This method can be overridden to define a more precise set of tests.
     *
     * @param  object   $om         ObjectManager instance.
     * @param  array    $oids       List of objects identifiers.
     * @param  array    $values     Associative array holding the new values to be assigned.
     * @param  string   $lang       Language in which multilang fields are being updated.
     * @return array    Returns an associative array mapping fields with their error messages. An empty array means that object has been successfully processed and can be updated.
     */
    public static function canupdate($om, $oids, $values, $lang) {
        $res = $om->read(get_called_class(), $oids, [ 'status', 'customer_id', 'customer_identity_id' ]);

        // fields that can always be updated
        $authorized_fields = ['description'];

        if($res > 0) {
            $fields = array_keys($values);
            if(count($values) == 1 && in_array($fields[0], $authorized_fields))  {
                // allowed update
            }
            else {
                // check for accepted changes based on status
                foreach($res as $oids => $odata) {
                    if(in_array($odata['status'], ['invoiced','debit_balance','credit_balance','balanced'])) {
                        // fields that can be updated when the status has those values
                        $authorized_fields = ['status'];
                        foreach($values as $field => $value) {
                            if(!in_array($field, $authorized_fields)) {
                                return ['status' => ['non_editable' => 'Invoiced orders edition is limited.']];
                            }
                        }
                    }
                    if( !$odata['customer_id'] && !$odata['customer_identity_id'] && !isset($values['customer_id']) && !isset($values['customer_identity_id']) ) {
                        return ['customer_id' => ['missing_mandatory' => 'Customer is mandatory.']];
                    }
                }

            }

        }

        return parent::canupdate($om, $oids, $values, $lang);
    }

}