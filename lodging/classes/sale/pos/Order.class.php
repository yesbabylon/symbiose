<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace lodging\sale\pos;

class Order extends \sale\pos\Order {

    public static function getColumns() {

        return [

            'funding_id' => [
                'type'              => 'many2one',
                'foreign_object'    => \lodging\sale\pay\Funding::getType(),
                'description'       => 'The booking funding that relates to the order, if any.',
                'visible'           => ['has_funding', '=', true],
                'onupdate'          => 'onupdateFundingId'
            ],

            'invoice_id' => [
                'type'              => 'many2one',
                'foreign_object'    => \lodging\finance\accounting\Invoice::getType(),
                'description'       => 'The invoice that relates to the order, if any.',
                'visible'           => ['has_invoice', '=', true]
            ],

            'session_id' => [
                'type'              => 'many2one',
                'foreign_object'    => CashdeskSession::getType(),
                'description'       => 'The session the order belongs to.',
                'onupdate'          => 'onupdateSessionId',
                'required'          => true
            ],

            'center_id' => [
                'type'              => 'many2one',
                'foreign_object'    => \lodging\identity\Center::getType(),
                'description'       => "The center the desk relates to (from session)."
            ],

            'order_lines_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => OrderLine::getType(),
                'foreign_field'     => 'order_id',
                'ondetach'          => 'delete',
                'onupdate'          => 'onupdateOrderLinesIds',
                'description'       => 'The lines that relate to the order.'
            ],

            'order_payments_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => OrderPayment::getType(),
                'foreign_field'     => 'order_id',
                'ondetach'          => 'delete',
                'description'       => 'The payments that relate to the order.'
            ],

            'order_payment_parts_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => OrderPaymentPart::getType(),
                'foreign_field'     => 'order_id',
                'ondetach'          => 'delete',
                'description'       => 'The payments parts that relate to the order.'
            ],

            // override onupdate event (uses local onupdateStatus)
            'status' => [
                'type'              => 'string',
                'selection'         => [
                    'pending',           // consumptions (lines) are being added to the order
                    'payment',           // a waiter is proceeding to the payement
                    'paid'               // order is closed and payment has been received
                ],
                'description'       => 'Current status of the order.',
                'onupdate'          => 'onupdateStatus',
                'default'           => 'pending'
            ]

        ];
    }


    /**
     * @param \equal\orm\ObjectManager  $om Instance of the ObjectManager service.
     */
    public static function onupdateStatus($om, $ids, $values, $lang) {
        // upon payment of the order, update related funding and invoice, if any
        if(isset($values['status']) && $values['status'] == 'paid') {
            $orders = $om->read(self::getType(), $ids, ['has_invoice', 'has_funding', 'funding_id.type', 'funding_id.invoice_id', 'center_id.center_office_id'], $lang);
            if($orders > 0) {
                foreach($orders as $oid => $order) {
                    if($order['has_funding']) {
                        if($order['funding_id.type'] == 'invoice') {
                            $om->update(Invoice::getType(), $order['funding_id.invoice_id'], ['status' => 'invoice', 'is_paid' => null], $lang);
                        }
                    }
                    // no funding and no invoice: generate stand alone accounting entries
                    else if(!$order['has_invoice']) {

                        // filter lines that do not relate to a booking (added as 'extra' services)
                        $order_lines_ids = $om->search(OrderLine::getType(), [ ['order_id', '=', $oid], ['has_booking', '=', false] ]);

                        // generate accounting entries
                        $orders_accounting_entries = self::_generateAccountingEntries($om, $ids, $order_lines_ids, $lang);

                        // create new entries objects and assign to the bank_cash journal relating to the center_office_id
                        foreach($orders as $oid => $order) {

                            $res = $om->search(\lodging\finance\accounting\AccountingJournal::getType(), [['center_office_id', '=', $order['center_id.center_office_id']], ['type', '=', 'bank_cash']]);
                            $journal_id = reset($res);

                            if($journal_id && isset($orders_accounting_entries[$oid])) {
                                $accounting_entries = $orders_accounting_entries[$oid];
                                // create new entries objects and assign to the sale journal relating to the center_office_id
                                foreach($orders_accounting_entries as $oid => $accounting_entries) {
                                    foreach($accounting_entries as $entry) {
                                        $entry['journal_id'] = $journal_id;
                                        $om->create(\finance\accounting\AccountingEntry::getType(), $entry);
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * Assign default customer_id based on the center that the session relates to.
     */
    public static function onupdateSessionId($om, $oids, $values, $lang) {
        // retrieve default customers assigned to centers
        $orders = $om->read(__CLASS__, $oids, ['session_id.center_id', 'session_id.center_id.pos_default_customer_id'], $lang);

        if($orders > 0) {
            foreach($orders as $oid => $order) {
                $om->update(__CLASS__, $oid, ['center_id' => $order['session_id.center_id'], 'customer_id' => $order['session_id.center_id.pos_default_customer_id'] ], $lang);
            }
        }

        $om->callonce(parent::getType(), 'onupdateSessionId', $oids, $values, $lang);
    }

    public static function onupdateFundingId($om, $ids, $values, $lang) {
        $orders = $om->read(self::getType(), $ids, ['funding_id'], $lang);
        if($orders > 0) {
            foreach($orders as $oid => $order) {
                $om->update(self::getType(), $oid, ['has_funding' => ($order['funding_id'] > 0)], $lang);
            }
        }
    }
}