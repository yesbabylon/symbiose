<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace sale\pay;
use equal\orm\Model;

class Payment extends Model {

    public static function getColumns() {

        return [

            'partner_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'identity\Partner',
                'description'       => "The partner to whom the payment relates."
            ],

            'amount' => [
                'type'              => 'float',
                'usage'             => 'amount/money:2',
                'description'       => 'Amount paid (whatever the origin).'
            ],

            'communication' => [
                'type'              => 'string',
                'description'       => "Message from the payer.",
            ],

            'receipt_date' => [
                'type'              => 'datetime',
                'description'       => "Time of reception of the payment.",
                'default'           => time()
            ],

            'payment_origin' => [
                'type'              => 'string',
                'selection'         => [
                    'cashdesk',             // money was received at the cashdesk
                    'bank'                  // money was received on a bank account
                ],
                'description'       => "Origin of the received money.",
                'default'           => 'bank'
            ],

            'payment_method' => [
                'type'              => 'string',
                'selection'         => [
                    'voucher',              // gift, coupon, or tour-operator voucher
                    'cash',                 // cash money
                    'bank_card',            // electronic payment with credit or debit card
                    'wire_transfer'         // transfer between bank account
                ],
                'description'       => "The method used for payment at the cashdesk.",
                'visible'           => [ ['payment_origin', '=', 'cashdesk'] ],
                'default'           => 'cash'
            ],

            'operation_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\pos\Operation',
                'description'       => 'The operation the payment relates to.',
                'visible'           => [ ['payment_origin', '=', 'cashdesk'] ]
            ],

            'statement_line_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\pay\BankStatementLine',
                'description'       => 'The bank statement line the payment relates to.',
                'visible'           => [ ['payment_origin', '=', 'bank'] ]
            ],

            'voucher_ref' => [
                'type'              => 'string',
                'description'       => 'The reference of the voucher the payment relates to.',
                'visible'           => [ ['payment_method', '=', 'voucher'] ]
            ],

            'funding_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\pay\Funding',
                'description'       => 'The funding the payement relates to, if any.',
                'onupdate'          => 'onupdateFundingId'
            ],

            'invoice_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'finance\accounting\Invoice',
                'description'       => 'The invoice targeted by the payment, if any.'
            ],

            'is_exported' => [
                'type'              => 'boolean',
                'description'       => 'Mark the payment as exported (part of an export to elsewhere).',
                'default'           => false
            ],

            'status' => [
                'type'              => 'string',
                'selection'         => [
                    'pending',
                    'paid'
                ],
                'description'       => 'Current status of the payment.',
                'default'           => 'paid'
            ]

        ];
    }

    /**
     * Assign partner_id and invoice_id from invoice relating to funding, if any.
     * Force recomputing of target funding computed fields (is_paid and paid_amount).
     *
     */
    public static function onupdateFundingId($om, $ids, $values, $lang) {
        trigger_error("ORM::calling sale\pay\Payment::onupdateFundingId", QN_REPORT_DEBUG);

        $payments = $om->read(self::getType(), $ids, ['funding_id', 'partner_id']);

        if($payments > 0) {
            // $fundings_ids = [];
            foreach($payments as $pid => $payment) {

                if($payment['funding_id']) {
                    // make sure a partner_id is assigned to the payment
                    if(!$payment['partner_id']) {
                        $fundings = $om->read('sale\pay\Funding', $payment['funding_id'], [
                                'type',
                                'due_amount',
                                'invoice_id',
                                'invoice_id.partner_id.id',
                                'invoice_id.partner_id.name'
                            ],
                            $lang);

                        if($fundings > 0 && count($fundings) > 0) {
                            $funding = reset($fundings);
                            if($funding['type'] == 'invoice') {
                                $values['partner_id'] = $funding['invoice_id.partner_id.id'];
                                $values['invoice_id'] = $funding['invoice_id'];
                            }
                            $om->update(self::getType(), $pid, $values);
                        }
                    }

                    $om->update('sale\pay\Funding', $payment['funding_id'], ['is_paid' => null, 'paid_amount' => null]);
                    // $fundings_ids[] = $payment['funding_id'];
                }
            }
            // force immediate re-computing of the is_paid field
            // $om->read('sale\pay\Funding', array_unique($fundings_ids), ['is_paid', 'paid_amount']);
        }
    }

    /**
     * Check wether the payment can be updated, and perform some additional operations if necessary.
     * This method can be overridden to define a more precise set of tests.
     *
     * @param  Object   $om         ObjectManager instance.
     * @param  Array    $ids        List of objects identifiers.
     * @param  Array    $values     Associative array holding the new values to be assigned.
     * @param  String   $lang       Language in which multilang fields are being updated.
     * @return Array    Returns an associative array mapping fields with their error messages. En empty array means that object has been successfully processed and can be updated.
     */
    public static function canupdate($om, $ids, $values, $lang='en') {
        $payments = $om->read(self::getType(), $ids, ['state', 'is_exported', 'payment_origin', 'amount', 'statement_line_id.amount', 'statement_line_id.remaining_amount'], $lang);
        foreach($payments as $pid => $payment) {
            if($payment['is_exported']) {
                return ['is_exported' => ['non_editable' => 'Once exported a payment can no longer be updated.']];
            }
            if($payment['payment_origin'] == 'bank') {
                if(isset($values['amount'])) {
                    $sign_line = intval($payment['statement_line_id.amount'] > 0) - intval($payment['statement_line_id.amount'] < 0);
                    $sign_payment = intval($values['amount'] > 0) - intval($values['amount'] < 0);
                    // #memo - we prevent creating payment that do not decrease the remaining amount
                    if($sign_line != $sign_payment) {
                        return ['amount' => ['incompatible_sign' => "Payment amount ({$values['amount']}) and statement line amount ({$payment['statement_line_id.amount']}) must have the same sign."]];
                    }
                    // #memo - when state is still draft, it means that reconcile is made manually
                    if($payment['state'] == 'draft') {
                        if(round($payment['statement_line_id.amount'], 2) < 0) {
                            if(round($payment['statement_line_id.remaining_amount'] - $values['amount'], 2) > 0) {
                                return ['amount' => ['excessive_amount' => "Payment amount ({$values['amount']}) cannot be higher than statement line remaining amount ({$payment['statement_line_id.remaining_amount']}) (err#1)."]];
                            }
                        }
                        else {
                            if(round($payment['statement_line_id.remaining_amount'] - $values['amount'], 2) < 0) {
                                return ['amount' => ['excessive_amount' => "Payment amount ({$values['amount']}) cannot be higher than statement line remaining amount ({$payment['statement_line_id.remaining_amount']}) (err#2)."]];
                            }
                        }
                    }
                    else  {
                        if(round($payment['statement_line_id.amount'], 2) < 0) {
                            if(round($payment['statement_line_id.remaining_amount'] + $payment['amount'] - $values['amount'], 2) > 0) {
                                return ['amount' => ['excessive_amount' => "Payment amount ({$values['amount']}) cannot be higher than statement line remaining amount ({$payment['statement_line_id.remaining_amount']}) (err#3)."]];
                            }
                        }
                        else {
                            if(round($payment['statement_line_id.remaining_amount'] + $payment['amount'] - $values['amount'], 2) < 0) {
                                return ['amount' => ['excessive_amount' => "Payment amount ({$values['amount']}) cannot be higher than statement line remaining amount ({$payment['statement_line_id.remaining_amount']}) (err#4)."]];
                            }
                        }
                    }
                }
            }
        }
        return parent::canupdate($om, $ids, $values, $lang);
    }


    /**
     * Hook invoked before object deletion for performing object-specific additional operations.
     *
     * @param  \equal\orm\ObjectManager     $om         ObjectManager instance.
     * @param  array                        $oids       List of objects identifiers.
     * @return void
     */
    public static function ondelete($om, $oids) {
        // set back related statement line status to 'pending'
        $payments = $om->read(__CLASS__, $oids, ['statement_line_id', 'funding_id']);
        if($payments > 0) {
            foreach($payments as $pid => $payment) {
                $om->update('sale\pay\BankStatementLine', $payment['statement_line_id'], ['status' => 'pending']);
                $om->update('sale\pay\Funding', $payment['funding_id'], ['is_paid' => false]);
            }
        }
        return parent::ondelete($om, $oids);
    }

    /**
     * Check wether the payments can be deleted.
     *
     * @param  \equal\orm\ObjectManager    $om        ObjectManager instance.
     * @param  array                       $ids       List of objects identifiers.
     * @return array                       Returns an associative array mapping fields with their error messages. An empty array means that object has been successfully processed and can be deleted.
     */
    public static function candelete($om, $ids) {
        $payments = $om->read(self::getType(), $ids, [ 'status' ]);

        if($payments > 0) {
            foreach($payments as $id => $payment) {
                if($payment['status'] == 'paid') {
                    return ['status' => ['non_removable' => 'Paid payment cannot be removed.']];
                }
            }
        }
        return parent::candelete($om, $ids);
    }
}