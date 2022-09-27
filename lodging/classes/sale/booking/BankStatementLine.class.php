<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace lodging\sale\booking;

class BankStatementLine extends \sale\booking\BankStatementLine {

    public static function getColumns() {

        return [

            'bank_statement_id' => [
                'type'              => 'many2one',
                'foreign_object'    => BankStatement::getType(),
                'description'       => 'The bank statement the line relates to.'
            ],

            'center_office_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'lodging\identity\CenterOffice',
                'description'       => 'Center office related to the satement (based on account number).',
                'onupdate'          => 'onupdateCenterOfficeId'
            ],

            'customer_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\customer\Customer',
                'description'       => 'The customer the line relates to, if known (set at status change).',
                'readonly'          => true
            ],

            'payments_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => Payment::getType(),
                'foreign_field'     => 'statement_line_id',
                'description'       => 'The list of payments this line relates to .',
                'onupdate'          => 'sale\pay\BankStatementLine::onupdatePaymentsIds',
                'ondetach'          => 'delete'
            ]

        ];
    }


    /**
     * Handler for center_office_id updates.
     *
     */
    public static function onupdateCenterOfficeId($om, $oids, $values, $lang) {
        trigger_error("QN_DEBUG_ORM::calling lodging\sale\booking\BankStatementLine::onupdateCenterOfficeId", QN_REPORT_DEBUG);

        $om->call(self::getType(), 'reconcile', $oids, $values, $lang);
    }


    /**
     * Try to automatically reconcile a newly created statement line with a funding.
     * This method is called by current class (onupdateCenterOfficeId) and controller `lodging_sale_pay_bankstatementline_do-reconcile`
     */
    public static function reconcile($om, $oids, $values, $lang) {
        $lines = $om->read(self::getType(), $oids, ['amount', 'center_office_id', 'structured_message', 'bank_statement_id']);

        if($lines > 0) {
            foreach($lines as $lid => $line) {
                $processed_fundings_ids = [];
                $remaining_amount = $line['amount'];
                $candidates_fundings_ids = $om->search(Funding::getType(), [ ['payment_reference', '=', $line['structured_message']] ]);

                // if there's no match, fall back to using message as reference
                if($candidates_fundings_ids <= 0 && !count($candidates_fundings_ids)) {
                    $candidates_fundings_ids = $om->search(Funding::getType(), [ ['payment_reference', '=', preg_replace('/[^0-9]/', '', $line['message'])] ]);
                }

                if($candidates_fundings_ids > 0 && count($candidates_fundings_ids)) {

                    $fundings = $om->read(Funding::getType(), $candidates_fundings_ids, ['id', 'is_paid', 'due_amount', 'paid_amount', 'booking_id.fundings_ids']);
                    if($fundings > 0 && count($fundings)) {

                        foreach($fundings as $fid => $funding) {

                            // candidate 1: exact match with payment_reference AND still waiting for payment (whatever the due_amount)
                            if($funding['is_paid'] == false && !in_array($fid, $processed_fundings_ids)) {
                                $processed_fundings_ids[] = $fid;
                                $due_amount = $funding['due_amount']-$funding['paid_amount'];
                                $assigned_amount = min($due_amount, $remaining_amount);
                                if($assigned_amount > 0) {
                                    $remaining_amount -= $assigned_amount;
                                    // create a new payment with assigned amount
                                    $om->create(Payment::getType(), [
                                            'funding_id'        => $fid,
                                            'statement_line_id' => $lid,
                                            'amount'            => $assigned_amount,
                                            'payment_method'    => 'bank'
                                        ],
                                        $lang);
                                    // force recomputing paid_amount
                                    $om->update(Funding::getType(), $fid, ['paid_amount' => null]);
                                }
                            }
                            // candidates bis: un(fully)paid fundings from the booking targeted by the VCS of the funding
                            // #memo - this supports secondary payments made with the reference of a previous funding
                            if($remaining_amount > 0) {
                                $sibling_fundings = $om->read(Funding::getType(), $funding['booking_id.fundings_ids'], ['is_paid', 'paid_amount', 'due_amount']);
                                if($sibling_fundings > 0) {
                                    foreach($sibling_fundings as $sfid => $sibling_funding) {
                                        // ignore funding itself
                                        if(in_array($sfid, $processed_fundings_ids)) {
                                            continue;
                                        }
                                        if($sibling_funding['is_paid'] == false) {
                                            $processed_fundings_ids[] = $sfid;
                                            $due_amount = $sibling_funding['due_amount']-$sibling_funding['paid_amount'];
                                            $assigned_amount = min($due_amount, $remaining_amount);
                                            if($assigned_amount > 0) {
                                                $remaining_amount -= $assigned_amount;
                                                // create a new payment with assigned amount
                                                $om->create(Payment::getType(), [
                                                        'funding_id'        => $sfid,
                                                        'statement_line_id' => $lid,
                                                        'amount'            => $assigned_amount,
                                                        'payment_method'    => 'bank'
                                                    ],
                                                    $lang);
                                                // force recomputing paid_amount
                                                $om->update(Funding::getType(), $sfid, ['paid_amount' => null]);
                                                if($remaining_amount <= 0) {
                                                    break;
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }

                if($remaining_amount > 0) {
                    // at least one funding has been (partly) credited
                    if(count($processed_fundings_ids)) {
                        // error: a part or all of the amount has already been paid
                        // #todo - notify accountant that a reimbursment is due
                        $om->update(self::getType(), $lid, ['status' => 'to_refund']);
                    }
                    else {
                        // no candidates : invalid VCS
                        // reconciliation must be done manually, could be necessary to refund
                        // #todo - notify user about this
                    }
                }
                else {
                    // mark the line as successfully reconciled
                    $om->update(self::getType(), $lid, ['status' => 'reconciled']);
                    // recompute parent statement status
                    $om->update(BankStatement::getType(), $line['bank_statement_id'], ['status' => null]);
                }

            } /* end foreach */
        }
    }

}